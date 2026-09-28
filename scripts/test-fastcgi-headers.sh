#!/bin/bash
# Test harness: does nginx's FastCGI layer pass a client's own request headers
# through, in addition to the ones set with fastcgi_param?
#
# The answer decides whether the production config's
#
#     fastcgi_param HTTP_X_FORWARDED_PROTO $scheme;
#
# is actually a defence or only a setting. If nginx also forwards the visitor's
# X-Forwarded-Proto header as a param of the same name, there are two values, and
# which one PHP keeps is not something the nginx config decides.
#
# Method: run the real rendered config with its fastcgi_pass pointed at a
# minimal FastCGI responder that prints the PARAMS it was given, then send one
# request with a spoofed header and one without.

set -u
# Resolved from this script's own location, so a checkout anywhere works.
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
T=/tmp/nginxtest
PORT=9100

cleanup() {
    docker rm -f nginxtest >/dev/null 2>&1
    # Kill by port, not by name: a pkill pattern for the responder's filename
    # also matches the shell running this script.
    fuser -k "${PORT}/tcp" >/dev/null 2>&1
    sleep 1
}
cleanup

mkdir -p "$T/conf.d" "$T/certs" "$T/public" "$T/webroot"

openssl req -x509 -newkey rsa:2048 -nodes -days 1 \
    -keyout "$T/certs/privkey.pem" -out "$T/certs/fullchain.pem" \
    -subj "/CN=arezoo.me" -addext "subjectAltName=DNS:arezoo.me,DNS:www.arezoo.me" 2>/dev/null

# The real template, rendered exactly as the image entrypoint renders it.
DOMAIN=arezoo.me CERT_PATH=/etc/letsencrypt/live/arezoo.me \
    envsubst '${DOMAIN} ${CERT_PATH}' \
    < "${SCRIPT_DIR}/../docker/nginx/production.conf.template" > "$T/conf.d/production.conf"

# Only three changes, all to make it testable in isolation: the FastCGI upstream
# points at the dump server instead of the app container, the plain-HTTP server
# answers 200 instead of redirecting (so the ACME path can be tested separately),
# and the TLS server moves off 443 because the legacy nginx already holds it.
sed -i \
    -e 's|fastcgi_pass app:9000;|fastcgi_pass host.docker.internal:9100;|' \
    -e 's|listen 80 default_server;|listen 8080;|' \
    -e 's|listen \[::\]:80 default_server;||' \
    -e 's|listen 443 ssl default_server;|listen 8443 ssl;|' \
    -e 's|listen \[::\]:443 ssl default_server;||' \
    -e 's|return 301 https://\$host\$request_uri;|return 200 "plain-http-ok\\n";|' \
    "$T/conf.d/production.conf"

FCGI_BIND=172.17.0.1 setsid python3 "${SCRIPT_DIR}/fcgi_dump.py" "$PORT" > /tmp/fcgi.log 2>&1 &
sleep 2
cat /tmp/fcgi.log

docker run -d --name nginxtest \
    --add-host host.docker.internal:host-gateway \
    -v "$T/conf.d:/etc/nginx/conf.d:ro" \
    -v "$T/certs:/etc/letsencrypt/live/arezoo.me:ro" \
    -v "$T/public:/var/www/html/public:ro" \
    -v "$T/webroot:/var/www/certbot:ro" \
    -p 127.0.0.1:18080:8080 -p 127.0.0.1:18443:8443 \
    nginx:latest >/dev/null 2>&1
sleep 3

# HTTP/1.1 plus a printable-characters filter for the FastCGI tests. The TLS
# server block has `http2 on`, so a response can arrive length-prefixed and
# binary-framed even when curl was asked for 1.1, and grep then reports a binary
# match instead of showing the text.
FC="curl -sk --http1.1 --max-time 10 -H Host:arezoo.me"
# Strip NULs only. An earlier version filtered to printable characters, which
# also removed the newlines and ran every name and value together — bad, because
# the readability of the parse is what the duplicate check depends on.
STRIP="tr -d '\\000'"

banner() { echo; echo "### $1"; }

banner "TEST 1 — normal request, no client-supplied header"
$FC https://127.0.0.1:18443/dashboard | eval $STRIP \
    | grep -E "HTTP_X_FORWARDED|HTTP_HOST|DUPLICATE|No duplicate|LAST occurrence"

banner "TEST 2 — visitor SPOOFS X-Forwarded-Proto: http over TLS"
echo "    (if HTTP_X_FORWARDED_PROTO shows http, the spoof wins and the config is unsafe)"
$FC -H "X-Forwarded-Proto: http" https://127.0.0.1:18443/dashboard | eval $STRIP \
    | grep -E "HTTP_X_FORWARDED|DUPLICATE|No duplicate|LAST occurrence"

banner "TEST 3 — visitor spoofs X-Forwarded-Host and X-Real-IP"
$FC -H "X-Forwarded-Host: evil.example" -H "X-Real-IP: 6.6.6.6" \
    https://127.0.0.1:18443/dashboard | eval $STRIP \
    | grep -E "HTTP_X_FORWARDED_HOST|HTTP_X_REAL_IP|DUPLICATE|No duplicate"

banner "TEST 4 — plain HTTP port, and the ACME challenge path"
# The full path certbot's webroot plugin writes, not the directory root: the
# location block sets `root /var/www/certbot`, so the token has to sit under
# .well-known/acme-challenge/ within it.
mkdir -p "$T/webroot/.well-known/acme-challenge"
printf 'token-abc123' > "$T/webroot/.well-known/acme-challenge/token"
echo "    GET /            -> $(curl -s --max-time 10 -H 'Host: arezoo.me' http://127.0.0.1:18080/ | tr -d '\n')"
echo "    GET challenge    -> $(curl -s --max-time 10 -H 'Host: arezoo.me' http://127.0.0.1:18080/.well-known/acme-challenge/token) (http $(curl -s -o /dev/null -w '%{http_code}' --max-time 10 -H 'Host: arezoo.me' http://127.0.0.1:18080/.well-known/acme-challenge/token))"

banner "TEST 5 — security headers on a real TLS response"
curl -skI --max-time 10 -H "Host: arezoo.me" https://127.0.0.1:18443/ \
    | grep -iE "strict-transport|x-frame|x-content-type|referrer|server:" | sed 's/^/    /'

cleanup
echo
echo "harness done"
