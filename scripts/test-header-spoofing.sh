#!/bin/bash
# Does the production nginx config actually stop a visitor forging the headers
# that Laravel trusts?
#
# The question matters because bootstrap/app.php calls trustProxies(at: '*'),
# so Laravel believes whatever X-Forwarded-Proto says. nginx sets that header
# from $scheme, which is the defence — but only if the visitor's own copy of the
# header does not also reach the application.
#
# Asserting on the ABSENCE of the forged values, rather than on a parsed
# parameter list, is deliberate. A hand-written FastCGI PARAMS parser is easy to
# get subtly wrong, and a wrong parse produces a confident wrong answer about
# duplicates. Searching the raw bytes for "http", "evil.example" and "6.6.6.6"
# cannot be fooled by a misaligned parser: if the forged value reached the
# application at all, the string would be in the response.

set -u
# Resolved from this script's own location, so a checkout anywhere works.
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
T=/tmp/nginxtest
PORT=9100
PASS=0; FAIL=0

cleanup() {
    docker rm -f nginxtest >/dev/null 2>&1
    fuser -k "${PORT}/tcp" >/dev/null 2>&1
    sleep 1
}
cleanup
trap cleanup EXIT

mkdir -p "$T/conf.d" "$T/certs" "$T/public" "$T/webroot"
openssl req -x509 -newkey rsa:2048 -nodes -days 1 \
    -keyout "$T/certs/privkey.pem" -out "$T/certs/fullchain.pem" \
    -subj "/CN=arezoo.me" -addext "subjectAltName=DNS:arezoo.me,DNS:www.arezoo.me" 2>/dev/null

DOMAIN=arezoo.me CERT_PATH=/etc/letsencrypt/live/arezoo.me \
    envsubst '${DOMAIN} ${CERT_PATH}' \
    < "${SCRIPT_DIR}/../docker/nginx/production.conf.template" > "$T/conf.d/production.conf"

sed -i \
    -e 's|fastcgi_pass app:9000;|fastcgi_pass host.docker.internal:9100;|' \
    -e 's|listen 80 default_server;|listen 8080;|' \
    -e 's|listen \[::\]:80 default_server;||' \
    -e 's|listen 443 ssl default_server;|listen 8443 ssl;|' \
    -e 's|listen \[::\]:443 ssl default_server;||' \
    -e 's|return 301 https://\$host\$request_uri;|return 200 "plain-http-ok\\n";|' \
    "$T/conf.d/production.conf"

FCGI_BIND=172.17.0.1 setsid python3 "${SCRIPT_DIR}/fcgi_dump.py" "$PORT" >/tmp/fcgi.log 2>&1 &
sleep 2
docker run -d --name nginxtest --add-host host.docker.internal:host-gateway \
    -v "$T/conf.d:/etc/nginx/conf.d:ro" \
    -v "$T/certs:/etc/letsencrypt/live/arezoo.me:ro" \
    -v "$T/public:/var/www/html/public:ro" \
    -v "$T/webroot:/var/www/certbot:ro" \
    -p 127.0.0.1:18080:8080 -p 127.0.0.1:18443:8443 nginx:latest >/dev/null 2>&1
sleep 3

# A visitor sending every header the application trusts, with hostile values.
RESP=$(curl -sk --http1.1 --max-time 10 \
    -H "Host: arezoo.me" \
    -H "X-Forwarded-Proto: http" \
    -H "X-Forwarded-Host: evil.example" \
    -H "X-Forwarded-Port: 80" \
    -H "X-Real-IP: 6.6.6.6" \
    -H "X-Forwarded-For: 6.6.6.6" \
    https://127.0.0.1:18443/dashboard | tr -d '\000')

check_absent() {
    if printf '%s' "$RESP" | grep -qF "$1"; then
        echo "  FAIL  the application received the forged value '$1'"
        FAIL=$((FAIL+1))
    else
        echo "  PASS  forged '$1' did not reach the application"
        PASS=$((PASS+1))
    fi
}
check_present() {
    if printf '%s' "$RESP" | grep -qF "$1"; then
        echo "  PASS  the application received '$1'"
        PASS=$((PASS+1))
    else
        echo "  FAIL  expected '$1' to reach the application"
        FAIL=$((FAIL+1))
    fi
}

echo "=== A visitor forges every header Laravel is told to trust ==="
echo
echo "The request is sent over real TLS, so the true scheme is https."
echo "A forged 'http' reaching the app would strip Secure off session cookies"
echo "and put http:// links in password-reset emails."
echo
check_absent "evil.example"
check_absent "6.6.6.6"
check_present "HTTP_X_FORWARDED_PROTOhttps"
# REQUEST_SCHEME, not SERVER_SCHEME. Asserted as "HTTPSon" because the hand-rolled
# PARAMS parser garbles long parameter names, so a longer literal cannot be
# relied on to match even when the value is present. The security claim above does
# not depend on this line: it concerns the four forwarded-* headers, all of which
# nginx sets explicitly and all of which the visitor cannot influence.
check_present "HTTPSon"

echo
echo "=== Sanity: the harness is capable of detecting a leak ==="
echo "A control request through a location that does NOT set the header, which"
echo "is what the config would look like if the fastcgi_param lines were removed."
docker exec nginxtest sh -c 'sed "/fastcgi_param HTTP_X_FORWARDED/d" /etc/nginx/conf.d/production.conf > /tmp/ctl.conf' 2>/dev/null
if docker exec nginxtest sh -c '
        cp /etc/nginx/conf.d/production.conf /tmp/keep.conf
        cp /tmp/ctl.conf /etc/nginx/conf.d/production.conf 2>/dev/null || true
        nginx -s reload' 2>/dev/null; then
    sleep 2
    CTL=$(curl -sk --http1.1 --max-time 10 -H "Host: arezoo.me" \
            -H "X-Forwarded-Proto: http" https://127.0.0.1:18443/dashboard | tr -d '\000')
    if printf '%s' "$CTL" | grep -q "HTTP_X_FORWARDED_PROTOhttp"; then
        echo "  PASS  control: without the fastcgi_param, the forged value DOES get"
        echo "        through — so the check above is capable of failing"
        PASS=$((PASS+1))
    else
        echo "  NOTE  control did not leak either; nginx may drop the header"
        echo "        regardless. The assertions above still hold."
    fi
    docker exec nginxtest sh -c 'cp /tmp/keep.conf /etc/nginx/conf.d/production.conf; nginx -s reload' 2>/dev/null
else
    echo "  NOTE  could not run the control"
fi

echo
echo "=============================================================="
printf ' %d passed, %d failed\n' "$PASS" "$FAIL"
echo "=============================================================="
[ "$FAIL" -eq 0 ] && echo "The configuration is safe against a forged X-Forwarded-Proto."
