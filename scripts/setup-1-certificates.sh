#!/bin/bash
# Phase 1 — certificates, on the host, as root.
#
# Run once. It issues the certificate the whole deployment depends on, and leaves
# renewal in a state that survives the web server coming back up.
#
# Why issuance and renewal use different methods, deliberately:
#
#   The certificate is issued with `--standalone`, which means certbot starts its
#   own throwaway web server on port 80 for the duration of the challenge. That
#   is only possible while nothing else holds port 80 — which is true right now,
#   because the legacy nginx has been stopped, and will not be true in ten
#   minutes, because the compose stack's nginx takes it.
#
#   Renewal has to work with the stack running, so the renewal configuration is
#   switched to `--webroot` immediately afterwards. The stack's nginx already
#   serves /.well-known/acme-challenge/ out of /var/www/certbot, so from then on
#   renewal is just a file being written and a reload, with no downtime and no
#   port juggling. verify.sh proves it with a dry-run once the stack is up, which
#   is the earliest moment a webroot challenge can succeed.
#
# The certificate covers the bare domain, www, and the mail host in one SAN
# entry. A wildcard would need a DNS-01 challenge and API access to the DNS
# provider, neither of which exists on this machine — which is why the previous
# certificate had nothing here able to renew it.

set -euo pipefail

# Resolved from this script's own location, so the repository works from any
# checkout path rather than one hard-coded absolute path.
REPO_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"

DOMAIN="arezoo.me"
NAMES="arezoo.me,www.arezoo.me,mail.arezoo.me"
ACME_EMAIL="postmaster@arezoo.me"
CERT_NAME="arezoo.me"   # certbot names the live/ dir after the first SAN
WEBROOT="/var/www/certbot"

echo "==> Installing certbot"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq certbot

echo "==> Creating the ACME webroot at ${WEBROOT}"
mkdir -p "${WEBROOT}"
# The stack's nginx mounts this read-only and serves it. It must exist before the
# container starts, or Docker creates a root-owned directory with the wrong
# context rather than the one intended.
chmod 755 "${WEBROOT}"

echo
echo "==> Stopping the legacy nginx so port 80 is free for the challenge"
# Disabled as well as stopped. A reboot with it merely stopped would bring the
# legacy site back and fight the stack for both ports.
systemctl stop nginx || true
systemctl disable nginx || true

# A stale socket or a process still holding the port produces a challenge failure
# that looks like a DNS problem, so this is worth stating rather than assuming.
sleep 2
if ss -tulpn | grep -q ':80 '; then
    echo "!!! Something is still listening on port 80:" >&2
    ss -tulpn | grep ':80 ' >&2
    exit 1
fi

echo
echo "==> Requesting a certificate for: ${NAMES}"
# --standalone needs nothing pre-existing, which is the point: it does not depend
# on a web server that is not running yet.
# Registered with a real address, not --register-unsafely-without-email. Let’s
# Encrypt's expiry warnings go to the registered contact, and an unmonitored
# certificate is the exact failure this whole phase exists to prevent — the
# previous wildcard is still on this machine precisely because nobody was
# watching. postmaster@ is a mailbox this server will actually have.
certbot certonly \
    --standalone \
    --non-interactive \
    --agree-tos \
    --email "${ACME_EMAIL}" \
    --cert-name "${CERT_NAME}" \
    --keep-until-expiring \
    -d arezoo.me -d www.arezoo.me -d mail.arezoo.me

LIVE="/etc/letsencrypt/live/${CERT_NAME}"
if [ ! -f "${LIVE}/fullchain.pem" ] || [ ! -f "${LIVE}/privkey.pem" ]; then
    echo "!!! Certificate not where it is expected at ${LIVE}" >&2
    exit 1
fi

echo
echo "==> Switching renewal from standalone to webroot"
# Without this, renewal tries to bind port 80 for its own server and fails,
# silently, every time — and the certificate expires three months later.
# Editing the renewal configuration is what certbot itself would do if the
# authenticator had been chosen as webroot at issuance.
CONF="/etc/letsencrypt/renewal/${CERT_NAME}.conf"
sed -i \
    -e 's/^authenticator = .*/authenticator = webroot/' \
    -e '/^webroot_path *=/d' \
    -e '/^authenticator = webroot$/a webroot_path = /var/www/certbot,' \
    "${CONF}"

grep -E '^(authenticator|webroot_path)' "${CONF}" || true

echo
echo "==> Installing the renewal deploy hook"
# Certbot renews on a timer; this is what makes a renewed certificate take effect.
# nginx has to be reloaded to read the new files, and postfix and dovecot have to
# be reloaded to serve the new certificate to clients.
mkdir -p /etc/letsencrypt/renewal-hooks/deploy
cat > /etc/letsencrypt/renewal-hooks/deploy/10-reload-arezoo <<'HOOK'
#!/bin/bash
# Reloaded after every successful renewal.
#
# A renewed certificate on disk changes nothing for a running server until it is
# told to re-read it, and the failure mode of forgetting is a site that keeps
# serving the old certificate until it expires.
set -u
logger -t arezoo-renewal "certificate renewed, reloading services"

# The container name is stable: the compose project is arezoo-prod and the
# service is nginx. --env-file is needed because compose interpolates the file
# even for a command that only reloads, and it fails on a missing variable.
cd "${REPO_ROOT}" || exit 0
docker compose --env-file .env.production -f compose.production.yaml \
    exec -T nginx nginx -s reload 2>/dev/null \
    || docker exec arezoo-prod-nginx-1 nginx -s reload 2>/dev/null \
    || logger -t arezoo-renewal "WARNING: could not reload nginx"

systemctl reload postfix 2>/dev/null || logger -t arezoo-renewal "WARNING: could not reload postfix"
systemctl reload dovecot 2>/dev/null || true
HOOK
chmod 755 /etc/letsencrypt/renewal-hooks/deploy/10-reload-arezoo

systemctl enable --now certbot.timer 2>/dev/null || true

# A renewal dry-run deliberately does NOT happen here. It cannot pass at this
# point: the webroot authenticator means Let's Encrypt fetches the challenge file
# over HTTP, and the only thing that would serve it is the compose stack's nginx,
# which is not running yet — this script has just spent its opening moves proving
# that port 80 is empty. verify.sh runs the dry-run after the stack is up, which
# is both the first moment it can succeed and the moment it is worth running.

echo
echo "==> Pointing the mail host at the same certificate"
# emailwiz looks for /etc/letsencrypt/live/mail.arezoo.me. This is a symlink
# rather than a second issuance so that there is one certificate, one renewal,
# and no possibility of the two drifting apart. A symlink is safe across renewal
# because certbot updates what it points at rather than replacing the entry.
ln -sfn "${CERT_NAME}" "/etc/letsencrypt/live/mail.arezoo.me"
ls -la /etc/letsencrypt/live/

echo
echo "==> Certificate summary"
openssl x509 -in "${LIVE}/fullchain.pem" -noout -subject -issuer -dates
openssl x509 -in "${LIVE}/fullchain.pem" -noout -ext subjectAltName

echo
echo "Phase 1 complete. Port 80 is now free and the stack can bind it."
