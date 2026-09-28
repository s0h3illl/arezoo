#!/bin/bash
# Phase 2 — the self-hosted mail server (emailwiz), on the host, as root.
#
# Run after Phase 1. It needs the certificate Phase 1 issued, which is why the two
# are ordered this way: emailwiz would otherwise try to run certbot's standalone
# authenticator itself and fail, because by now the stack's nginx holds port 80.
#
# What this produces, and what it does not:
#
#   Postfix, Dovecot, SpamAssassin, OpenDKIM and fail2ban, configured for an
#   internet-facing mail server for arezoo.me, with a DKIM key generated fresh.
#
#   It does NOT make mail deliverable on its own. Four DNS records have to be
#   published before a message reaches anyone's inbox, and two of them cannot be
#   set from here at all — the reverse DNS entry and confirmation that inbound port
#   25 is open are both the hosting provider's to do. The records are printed at
#   the end of the run and saved to ${HOME}/mail-dns-records.txt.

set -euo pipefail

# sudo's env_reset — the Debian default — sets HOME to the *target* user's home,
# so under `sudo bash setup-2-emailwiz.sh` this variable is /root. The mailbox
# password, the emailwiz checkout and the DNS records the user is meant to read
# all live in the deploy user's home instead, so $HOME cannot be used for them.
# SUDO_USER names whoever invoked sudo; when it is unset the script is already
# running as that user and HOME is already right. The fallback stats this script,
# which sits inside their checkout, rather than assuming a username.
DEPLOY_USER="${SUDO_USER:-$(stat -c '%U' "${BASH_SOURCE[0]}")}"
DEPLOY_HOME="$(getent passwd "${DEPLOY_USER}" | cut -d: -f6)"
if [ -z "${DEPLOY_HOME}" ]; then
    echo "!!! Cannot determine the home directory of '${DEPLOY_USER}'." >&2
    exit 1
fi
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"

DOMAIN="arezoo.me"
# Two mailboxes, two different roles, and no third way:
#
#   noreply@ — the application's sender. This is what MAIL_USERNAME and
#              MAIL_FROM_ADDRESS point at, and emailwiz's
#              smtpd_sender_login_maps equation maps login -> From, so the app
#              is pinned to it: exactly one mailbox can be impersonated by the
#              credential the app holds.
#
#   info@    — the human mailbox, where a reply and a DMARC report both land.
MAIL_USER_NOREPLY="noreply"
MAIL_USER_INFO="info"
MAILPASSFILE="${DEPLOY_HOME}/.mailpass"
if [ ! -r "${MAILPASSFILE}" ]; then
    echo "!!! ${MAILPASSFILE} is missing or unreadable." >&2
    echo "    Generate one, mode 600, containing the noreply mailbox password." >&2
    exit 1
fi
MAILPASS_NOREPLY="$(cat "${MAILPASSFILE}")"

MAILPASSFILE_INFO="${DEPLOY_HOME}/.mailpass-info"
if [ ! -r "${MAILPASSFILE_INFO}" ]; then
    echo "!!! ${MAILPASSFILE_INFO} is missing or unreadable." >&2
    echo "    Generate one, mode 600, containing the info mailbox password." >&2
    exit 1
fi
MAILPASS_INFO="$(cat "${MAILPASSFILE_INFO}")"

echo "==> Preseeding debconf so the postfix install does not stop for an answer"
# emailwiz purges postfix and reinstalls it, and a purge takes its debconf answers
# with it. Without these the install opens the "Internet Site" dialog and waits
# forever on an unattended run. The values are the ones the README asks for.
echo "postfix postfix/main_mailer_type select Internet Site" | debconf-set-selections
echo "postfix postfix/mailname string ${DOMAIN}" | debconf-set-selections
echo "${DOMAIN}" > /etc/mailname

echo
echo "==> Checking the certificate Phase 1 left for the mail host"
CERTDIR="/etc/letsencrypt/live/mail.${DOMAIN}"
if [ ! -f "${CERTDIR}/fullchain.pem" ]; then
    echo "!!! ${CERTDIR} is missing or has no certificate." >&2
    echo "    Run setup-1-certificates.sh first." >&2
    exit 1
fi
# Present, and its own existence is what stops emailwiz running certbot. That is
# the intended path: one certificate, one renewal, and no chance of the web and
# mail certificates drifting apart or one expiring quietly on its own.
ls -la "${CERTDIR}"

echo
echo "==> Installing swaks, for testing submission by hand"
# Not a dependency of anything here. It is the only practical way to prove that
# submission authenticates and that outbound mail leaves, without going through
# the application and mistaking an application problem for a mail problem.
export DEBIAN_FRONTEND=noninteractive
apt-get install -y -qq swaks || echo "!!! swaks failed to install; tests will be manual"

echo
echo "==> Running emailwiz"
cd "${DEPLOY_HOME}/emailwiz"
chmod +x emailwiz.sh
# Not run through sudo: this script is already root, and letting sudo re-resolve
# the environment is a common source of a subtly different PATH mid-install.
# Nothing in emailwiz.sh reads from stdin or opens a dialog, so it needs no TTY.
./emailwiz.sh

echo
echo "==> Adapting emailwiz's dovecot config to the installed dovecot"
# emailwiz writes dovecot 2.3-era syntax, and the dovecot 2.4 that ships on this
# distro refuses to start with it. The migration is idempotent; it only edits
# the file when something of its own is found, and validates the result.
"${SCRIPT_DIR}/dovecot-24-migrate.py"
systemctl restart dovecot
if ! systemctl is-active --quiet dovecot; then
    echo "!!! dovecot failed to start with the adapted config." >&2
    echo "    See: systemctl status dovecot" >&2
    exit 1
fi

echo
echo "==> Creating the mailboxes for ${DOMAIN}"
# emailwiz authenticates against Unix accounts, so each mailbox is a system
# user. The two are deliberately different things: noreply is the application's
# identity and its password lives in .env.production; info is a person's and its
# password lives in ~/.mailpass-info.
for u in "${MAIL_USER_NOREPLY}" "${MAIL_USER_INFO}"; do
    if id -u "${u}" >/dev/null 2>&1; then
        echo "    ${u}@${DOMAIN}: already exists, resetting its password"
    else
        useradd -m -G mail "${u}"
        echo "    ${u}@${DOMAIN}: created"
    fi
done
echo "${MAIL_USER_NOREPLY}:${MAILPASS_NOREPLY}" | chpasswd
echo "${MAIL_USER_INFO}:${MAILPASS_INFO}" | chpasswd

# postmaster is mandatory for any domain that accepts mail (RFC 5321 4.5.1), and
# root receives the system's local cron and error mail; both are aliased to info
# so the person reading that mailbox sees them. Without this, postmaster@ and
# root@ bounce or vanish into unread system mail.
printf '\npostmaster: %s\nroot: %s\n' "${MAIL_USER_INFO}" "${MAIL_USER_INFO}" \
    >> /etc/aliases
newaliases

# A new Unix account is created without a password expiry, but an explicit long
# expiry is stated rather than assumed: an app credential that expires on its own
# is a signup form that silently stops delivering.
for u in "${MAIL_USER_NOREPLY}" "${MAIL_USER_INFO}"; do
    chage -M 99999 "${u}" 2>/dev/null || true
done

echo
echo "==> Collecting the DNS records for the domain"
# emailwiz writes them to $HOME/dns_emailwizard, and $HOME here is root's, because
# this script runs as root. Copied to the deploy user's home, which is where
# show-dns-records.sh and they will both look.
cp -f "${HOME}/dns_emailwizard" "${DEPLOY_HOME}/mail-dns-records.txt" 2>/dev/null || true
chmod 644 "${DEPLOY_HOME}/mail-dns-records.txt" 2>/dev/null || true
if [ -s "${DEPLOY_HOME}/mail-dns-records.txt" ]; then
    cat "${DEPLOY_HOME}/mail-dns-records.txt"
else
    echo "!!! dns_emailwizard was not produced; records may need reading from the"
    echo "    DKIM table by hand: /etc/postfix/dkim/${DOMAIN}/"
fi

echo
echo "==> Service status"
for svc in postfix dovecot opendkim fail2ban; do
    printf '  %-12s %s\n' "${svc}" "$(systemctl is-active "${svc}" 2>/dev/null || echo unknown)"
done

echo
echo "==> Listening ports"
ss -tulpn | grep -E ':(25|80|110|143|443|465|587|993|995)\s' || echo "  (none of the mail ports are listening)"

echo
echo "Phase 2 complete. The remaining work is DNS, which is published at the registrar."
