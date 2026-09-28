#!/bin/bash
# Produces the exact DNS records to publish, and shows what is there now.
#
# Run after setup-2-emailwiz.sh, because the DKIM key is generated during that
# install and does not exist before it.
#
# The "currently published" column is the useful part. On a domain that has had
# mail records before, the failure is almost never a missing record — it is a
# stale one that looks correct and authorises nothing. A withdrawn DKIM key
# (`v=DKIM1; p=`) and an SPF of `v=spf1 -all` both read as present and both
# silently discard every message.

DOMAIN="arezoo.me"
MAILDOM="mail.${DOMAIN}"

dnsget() {
    curl -s --max-time 15 "https://dns.google/resolve?name=${1}&type=${2}" 2>/dev/null \
        | python3 -c "import sys,json;d=json.load(sys.stdin);print(' ; '.join(a['data'] for a in d.get('Answer',[])))" 2>/dev/null
}

# The DKIM value, read from the table emailwiz generated. Long, and unique to this
# machine, which is why it cannot be written down in advance.
DKIM=""
for f in /etc/postfix/dkim/"${DOMAIN}"/mail.txt /etc/postfix/dkim/"${DOMAIN}"/dkim.txt; do
    [ -f "$f" ] && DKIM="$(tr -d '\n' < "$f" | sed 's/k=rsa.*"p=/k=rsa; p=/; s/"\s*""//; s/"\s*).*//' | grep -o 'p=.*')" && break
done

ipv4=$(dnsget "${DOMAIN}" A | head -1)
ipv6=$(dnsget "${DOMAIN}" AAAA | head -1)

echo "==================================================================="
echo " DNS records to publish for ${DOMAIN}"
echo "==================================================================="
echo
echo "Set these at your registrar or DNS host. The name column is relative to"
echo "${DOMAIN} unless it starts with a dot."
echo

if [ -z "${DKIM}" ]; then
    echo "  !! No DKIM key found. Run setup-2-emailwiz.sh first — the key is"
    echo "     generated during that install and cannot be known in advance."
    echo
else
    echo "-------------------------------------------------------------------"
    echo "1. DKIM  (type TXT, name: mail._domainkey.${DOMAIN})"
    echo "-------------------------------------------------------------------"
    echo "   value: v=DKIM1; k=rsa; ${DKIM}"
    echo
    echo "   currently published: $(dnsget "mail._domainkey.${DOMAIN}" TXT)"
    echo
fi

echo "-------------------------------------------------------------------"
echo "2. SPF  (type TXT, name: @)"
echo "-------------------------------------------------------------------"
echo "   value: v=spf1 mx a:${MAILDOM} ip4:${ipv4} ip6:${ipv6} -all"
echo
echo "   currently published: $(dnsget "${DOMAIN}" TXT)"
echo "   ^ if that reads 'v=spf1 -all' it authorises NOTHING to send as this"
echo "     domain. It must be replaced, not added alongside."
echo

echo "-------------------------------------------------------------------"
echo "3. DMARC  (type TXT, name: _dmarc.${DOMAIN})"
echo "-------------------------------------------------------------------"
echo "   value: v=DMARC1; p=none; rua=mailto:postmaster@${DOMAIN}; fo=1"
echo
echo "   currently published: $(dnsget "_dmarc.${DOMAIN}" TXT)"
echo "   ^ start at p=none. Move to p=quarantine, then p=reject, only after a"
echo "     week of clean delivery. p=reject with strict alignment discards"
echo "     mail outright on any DKIM or SPF mistake."
echo

echo "-------------------------------------------------------------------"
echo "4. MX  (type MX, name: @)"
echo "-------------------------------------------------------------------"
echo "   value: 10 ${MAILDOM}"
echo
echo "   currently published: $(dnsget "${DOMAIN}" MX)"
echo "   ^ '(none)' means nothing can deliver to this domain at all."
echo

echo "-------------------------------------------------------------------"
echo "5. A records — verify, do not change"
echo "-------------------------------------------------------------------"
echo "   @     -> ${ipv4}   (the site)"
echo "   mail  -> $(dnsget "${MAILDOM}" A)"
echo "   ^ mail must NOT be behind a CDN proxy. Cloudflare and similar break"
echo "     mail entirely, and it presents as mail that works locally and"
echo "     vanishes in transit."
echo

echo "==================================================================="
echo " Cannot be done here — ask your hosting provider"
echo "==================================================================="
echo
echo "  a) Reverse DNS (PTR) for ${ipv4} -> ${MAILDOM}, for IPv4 and IPv6."
echo "     There is no way to set this from the server. Gmail and Outlook"
echo "     reject or heavily penalise mail from an IP with no PTR."
echo
echo "  b) Confirm inbound port 25 is not blocked. Most providers block it by"
echo "     default. Outbound 25 being open is a different thing and is not"
echo "     sufficient — without inbound 25 nothing can reach you."
echo
echo "  Current PTR: $(curl -s --max-time 15 "https://dns.google/resolve?name=$(echo ${ipv4} | awk -F. '{print $4"."$3"."$2"."$1}').in-addr.arpa&type=PTR" | python3 -c "import sys,json;d=json.load(sys.stdin);print(' '.join(a['data'] for a in d.get('Answer',[])) or 'NONE — this must be fixed')" 2>/dev/null)"
echo
echo "==================================================================="
echo " After publishing:  bash scripts/verify.sh"
echo "==================================================================="
