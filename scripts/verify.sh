#!/bin/bash
# Phase 3 — verification. Safe to run any time; reports rather than changes.
#
# Split into what can be checked from here and what cannot, because the honest
# answer to "is it working" is different for each. Everything in the first
# section is a fact about this machine. Everything in the second is a fact about
# the outside world's opinion of this machine, which no amount of local checking
# can establish.

DOMAIN="arezoo.me"
MAILDOM="mail.${DOMAIN}"
# Resolved from this script's own location rather than hard-coded, so a checkout
# anywhere on disk works. The scripts are documentation that executes: a path
# pointing at one machine's home directory would make every instruction here a
# lie on any other machine, which is how deployments get half-run.
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(dirname -- "$SCRIPT_DIR")"
COMPOSE="docker compose --env-file ${REPO_ROOT}/.env.production -f ${REPO_ROOT}/compose.production.yaml"

pass=0; fail=0; warn=0
ok()   { echo "  PASS  $1"; pass=$((pass+1)); }
no()   { echo "  FAIL  $1"; fail=$((fail+1)); }
w()    { echo "  WARN  $1"; warn=$((warn+1)); }

echo "=============================================================="
echo " 1. Containers"
echo "=============================================================="
cd "${REPO_ROOT}" || exit 1
if ${COMPOSE} ps --format '{{.Service}}\t{{.State}}\t{{.Status}}' 2>/dev/null | column -t; then :; else
    no "could not read compose status"
fi

app_state="$(${COMPOSE} ps --status running -q app 2>/dev/null)"
[ -n "${app_state}" ] && ok "app container is running" || no "app container is not running"

# Healthy, not merely running: the entrypoint refuses to open port 9000 until the
# manifest is found and caches are compiled, so a listening socket is a stronger
# statement than a container existing.
if ${COMPOSE} exec -T app php -r 'exit(@fsockopen("127.0.0.1",9000)?0:1);' 2>/dev/null; then
    ok "php-fpm is listening on 9000 inside the app container"
else
    no "php-fpm is not answering on 9000 (app not healthy, or not up)"
fi

echo
echo "=============================================================="
echo " 2. TLS and HTTP"
echo "=============================================================="
for host in "${DOMAIN}" "www.${DOMAIN}"; do
    code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 "https://${host}/" 2>/dev/null)
    if [ "${code}" = "200" ]; then ok "https://${host}/ returns 200"
    else no "https://${host}/ returned ${code:-no response}"; fi
done

# The redirect is how a certificate is renewed, and how http:// links in mail
# resolve. If this is not a 301, renewal is about to fail.
code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 "http://${DOMAIN}/" 2>/dev/null)
[ "${code}" = "301" ] && ok "http:// redirects to https (301)" \
                      || no "http:// returned ${code:-no response}, expected 301"

echo
echo "  -- what the world sees --"
for host in "${DOMAIN}" "www.${DOMAIN}"; do
    enddate=$(echo | timeout 15 openssl s_client -servername "${host}" -connect "${host}:443" 2>/dev/null \
              | openssl x509 -noout -enddate 2>/dev/null | cut -d= -f2)
    [ -n "${enddate}" ] && ok "${host} certificate valid until ${enddate}" \
                        || no "could not read the certificate for ${host}"
done

# The security headers, checked because their absence is invisible until
# something is actually wrong.
hdrs=$(curl -sI --max-time 15 "https://${DOMAIN}/" 2>/dev/null)
echo "${hdrs}" | grep -qi "strict-transport-security" && ok "HSTS is set" || w "no Strict-Transport-Security header"
echo "${hdrs}" | grep -qi "x-frame-options"           && ok "X-Frame-Options is set" || w "no X-Frame-Options header"
echo "${hdrs}" | grep -qi "x-content-type-options"    && ok "X-Content-Type-Options is set" || w "no X-Content-Type-Options header"

echo
echo "  -- the header that decides whether emails contain working links --"
# X-Forwarded-Proto is the whole reason this check exists. If Laravel believes the
# request was plain HTTP, session cookies lose the Secure flag and every
# verification and password-reset link is emitted as http://, which mail clients
# refuse to open. Nothing on the page shows this; only a sent email does.
cookie=$(echo "${hdrs}" | grep -i "^set-cookie:" | head -1)
if echo "${cookie}" | grep -qi "secure"; then
    ok "session cookie carries the Secure flag"
else
    no "session cookie has no Secure flag — check X-Forwarded-Proto in the nginx config"
fi

echo
echo "  -- can the certificate actually be renewed? --"
# This is the check that matters most and fails silently when it is missing. A
# certificate that cannot be renewed is indistinguishable from a working one right
# up until it expires, and the last one on this machine sat unrenewed for its
# entire life because nothing was watching.
#
# It has to run here, after the stack is up, and not during certificate setup: the
# webroot authenticator means the challenge file is fetched over HTTP, so it can
# only succeed while something is serving /var/www/certbot.
if [ -f "/etc/letsencrypt/renewal/${DOMAIN}.conf" ]; then
    auth=$(grep -E '^authenticator *=' "/etc/letsencrypt/renewal/${DOMAIN}.conf" 2>/dev/null | cut -d= -f2 | tr -d ' ')
    if [ "${auth}" = "webroot" ]; then
        ok "renewal is configured for webroot (works with the stack running)"
    else
        no "renewal authenticator is '${auth}', not webroot — it will try to bind port 80 and fail every time"
    fi

    # The webroot the renewal will write to has to be the one nginx serves, or the
    # challenge 404s at Let's Encrypt and renewal fails against a healthy site.
    wpath=$(grep -E '^webroot_path *=' "/etc/letsencrypt/renewal/${DOMAIN}.conf" 2>/dev/null | cut -d= -f2 | tr -d ' ,')
    if [ "${wpath}" = "/var/www/certbot" ]; then
        ok "renewal webroot is /var/www/certbot, which nginx serves"
    else
        no "renewal webroot is '${wpath:-unset}' but nginx serves /var/www/certbot"
    fi

    if systemctl is-enabled certbot.timer >/dev/null 2>&1; then
        ok "certbot.timer is enabled, so renewal is scheduled"
    else
        no "certbot.timer is not enabled — nothing will attempt renewal"
    fi
else
    no "no renewal configuration at /etc/letsencrypt/renewal/${DOMAIN}.conf"
fi

if certbot renew --dry-run --cert-name "${DOMAIN}" >/tmp/certbot-dryrun.log 2>&1; then
    ok "certbot renew --dry-run succeeded — renewal is proven, not assumed"
else
    no "certbot renew --dry-run FAILED — see /tmp/certbot-dryrun.log"
    tail -12 /tmp/certbot-dryrun.log | sed 's/^/        /'
fi

echo
echo "=============================================================="
echo " 3. Database and migrations"
echo "=============================================================="
if ${COMPOSE} exec -T app php artisan migrate:status 2>/dev/null | grep -q "Pending"; then
    no "there are pending migrations — run: ${COMPOSE} exec app php artisan migrate --force"
else
    ok "no pending migrations"
fi

db_name=$(${COMPOSE} exec -T mariadb sh -c 'echo $MARIADB_DATABASE' 2>/dev/null | tr -d '\r')
tables=$(${COMPOSE} exec -T mariadb sh -c 'mysql -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE" -N -e "select count(*) from information_schema.tables where table_schema=database()"' 2>/dev/null | tr -d '\r')
[ -n "${tables}" ] && [ "${tables}" -gt 0 ] 2>/dev/null \
    && ok "database ${db_name} has ${tables} tables" \
    || no "could not count tables in ${db_name} — is the database initialised?"

echo
echo "=============================================================="
echo " 4. Mail server"
echo "=============================================================="
if [ "$(systemctl is-active postfix 2>/dev/null)" = "active" ]; then
    ok "postfix is active"
else
    no "postfix is $(systemctl is-active postfix 2>/dev/null || echo unknown)"
fi
[ "$(systemctl is-active dovecot 2>/dev/null)" = "active" ] \
    && ok "dovecot is active" || w "dovecot is not active"

for p in 25 465 587 993; do
    if ss -tuln 2>/dev/null | grep -q ":${p} "; then ok "port ${p} is listening"
    else no "port ${p} is not listening"; fi
done

# The application must be able to *reach* the mail server, which is a different
# question from the mail server being up, and the one that actually breaks signup.
if ${COMPOSE} exec -T app php -r '
    $h=getenv("MAIL_HOST"); $p=(int)getenv("MAIL_PORT");
    $e=0; $s=""; $fp=@fsockopen($h,$p,$e,$s,8);
    if(!$fp){fwrite(STDERR,"  cannot reach $h:$p — $s\n");exit(1);}
    fclose($fp); exit(0);
' 2>/dev/null; then
    ok "the app container can reach the mail server"
else
    no "the app container cannot reach the mail server — signup email will fail"
fi

dkim=$(grep -c . /etc/postfix/dkim/"${DOMAIN}"/mail.txt 2>/dev/null || echo 0)
[ "${dkim}" -gt 0 ] 2>/dev/null && ok "a DKIM key was generated" || w "no DKIM key found yet (emailwiz has not run)"

echo
echo "=============================================================="
echo " 5. DNS — the part only the outside world can answer"
echo "=============================================================="
echo "  Checked with DNS-over-HTTPS, so these are the real published records"
echo "  rather than whatever a local resolver has cached."
echo

# dig is not installed on this host, so this asks a public resolver over HTTPS.
dnsget() {
    curl -s --max-time 15 "https://dns.google/resolve?name=${1}&type=${2}" 2>/dev/null \
        | python3 -c "import sys,json;d=json.load(sys.stdin);print('\n'.join(a['data'] for a in d.get('Answer',[])))" 2>/dev/null
}

a=$(dnsget "${DOMAIN}" A)
echo "${a}" | grep -qE '^[0-9]+\.' && ok "A ${DOMAIN} -> $(echo ${a} | tr '\n' ' ')" \
                                   || no "no A record for ${DOMAIN}"

mx=$(dnsget "${DOMAIN}" MX)
echo "${mx}" | grep -q "${MAILDOM}" && ok "MX -> $(echo ${mx} | tr '\n' ' ')" \
                                    || no "no MX record pointing at ${MAILDOM} — mail to this domain is not deliverable"

# The record as it stands now is v=spf1 -all, which authorises nothing to send.
# It has to name the mail host or the SPF check fails at every receiver.
spf=$(dnsget "${DOMAIN}" TXT)
echo "${spf}" | grep -qi "v=spf1" && echo "${spf}" | grep -qi -- "-all" && echo "${spf}" | grep -qE "mx|a:${MAILDOM}|ip4:" \
    && ok "SPF authorises this host" \
    || no "SPF does not authorise this host: $(echo ${spf} | tr '\n' ' ')"

dk=$(dnsget "mail._domainkey.${DOMAIN}" TXT)
[ -n "${dk}" ] && ok "DKIM public key is published" || no "no DKIM key at mail._domainkey.${DOMAIN} — every message fails alignment"

# p=reject with strict alignment means one wrong DKIM detail discards mail rather
# than filing it as spam, which is a much worse place to discover the problem.
dm=$(dnsget "_dmarc.${DOMAIN}" TXT)
echo "${dm}" | grep -qi "p=none"  && ok "DMARC is p=none (monitoring — correct while tuning)" \
    || echo "${dm}" | grep -qi "p=reject" && w "DMARC is p=reject: any DKIM or SPF mistake discards mail outright" \
    || w "no DMARC record"

# Reverse DNS cannot be set from here at all. It is the hosting provider's, and
# every large receiver weighs it heavily.
echo
echo "  -- reverse DNS (set by your hosting provider, not possible from here) --"
ip=$(dnsget "${DOMAIN}" A | head -1)
ptr=$(curl -s --max-time 15 "https://dns.google/resolve?name=$(echo "${ip}" | awk -F. '{print $4"."$3"."$2"."$1}').in-addr.arpa&type=PTR" 2>/dev/null \
      | python3 -c "import sys,json;d=json.load(sys.stdin);print(' '.join(a['data'] for a in d.get('Answer',[])))" 2>/dev/null)
if [ -n "${ptr}" ]; then
    ok "PTR for ${ip} -> ${ptr}"
else
    no "no PTR record for ${ip} — Gmail and Outlook will reject or spam mail from it"
fi

echo
echo "  -- inbound port 25 (also the provider's to confirm) --"
if timeout 10 bash -c 'cat </dev/null >/dev/tcp/'"$(dnsget _dmarc.${DOMAIN} >/dev/null; echo ${MAILDOM})"'/25' 2>/dev/null; then
    echo "  NOTE  resolved and reachable locally; only an external test proves the provider allows it"
else
    w "could not resolve ${MAILDOM} to test port 25 — set its A record first"
fi

echo
echo "=============================================================="
printf ' %d passed, %d failed, %d warnings\n' "${pass}" "${fail}" "${warn}"
echo "=============================================================="
[ "${fail}" -eq 0 ] || echo "There are failures above. Each one names the fix."
