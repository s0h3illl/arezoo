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
    curl -s --max-time 15 -H "accept: application/dns-json" \
        "https://cloudflare-dns.com/dns-query?name=${1}&type=${2}" 2>/dev/null \
        | python3 -c "import sys,json;d=json.load(sys.stdin);print(' ; '.join(a['data'] for a in d.get('Answer',[])))" 2>/dev/null
}

# Same DoH query, but distinguishes the two kinds of blank a bare dnsget
# conflates: a query that was answered with no records ("NONE") versus one that
# was never answered at all ("LOOKUP FAILED"). In a script whose purpose is to
# diff published DNS, a network blip must not read as "no record".
lookup() {
    local out
    out="$(curl -s --max-time 15 -H "accept: application/dns-json" \
        "https://cloudflare-dns.com/dns-query?name=${1}&type=${2}" 2>/dev/null)"
    if [ -z "${out}" ]; then
        echo "LOOKUP FAILED"
        return
    fi
    printf '%s' "${out}" | python3 -c "
import sys, json
d = json.load(sys.stdin)
# Status 0 (NOERROR) and 3 (NXDOMAIN) are definitive answers; NXDOMAIN is how
# a recursor says a reverse name has no PTR. Anything else (SERVFAIL, REFUSED,
# a non-JSON body from a dead upstream) means the lookup never got answered.
st = d.get('Status')
if st not in (0, 3):
    print('LOOKUP FAILED')
else:
    ans = d.get('Answer') or []
    print(' ; '.join(a['data'] for a in ans) or 'NONE')
" 2>/dev/null || echo "LOOKUP FAILED"
}

# The DKIM value, read from the table emailwiz generated. Long, and unique to this
# machine, which is why it cannot be written down in advance. The file is root
# owned, so fall back to sudo when the invoking user cannot read it; the quoting
# (continuation lines wrapped in quotes and tabs) is stripped by extracting the
# base64 blob verbatim.
DKIM=""
for f in /etc/postfix/dkim/"${DOMAIN}"/mail.txt /etc/postfix/dkim/"${DOMAIN}"/dkim.txt; do
    [ -f "$f" ] || continue
    if [ -r "$f" ]; then
        raw="$(cat "$f")"
    elif sudo -n true 2>/dev/null; then
        raw="$(sudo sh -c "cat \"${f}\"" 2>/dev/null)" || raw=""
    else
        continue
    fi
    DKIM="$(printf '%s' "${raw}" | python3 -c "
import sys, re
s = sys.stdin.read()
m = re.search(r'p=', s)
if not m:
    print(''); sys.exit()
tail = s[m.end():]
closes = [i for i in (tail.find('\" )'), tail.find('\")')) if i != -1]
if closes:
    tail = tail[:min(closes)]
print('p=' + re.sub(r'[^A-Za-z0-9+/=]', '', tail))
")"
    [ -n "${DKIM}" ] && break
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
    echo "   currently published: $(lookup "mail._domainkey.${DOMAIN}" TXT)"
    echo
fi

echo "-------------------------------------------------------------------"
echo "2. SPF  (type TXT, name: @)"
echo "-------------------------------------------------------------------"
# ip6 only appears when the domain actually publishes an AAAA record; an empty
# `ip6:` token would make the record invalid.
spfval="v=spf1 mx a:${MAILDOM} ip4:${ipv4}"
[ -n "${ipv6}" ] && spfval="${spfval} ip6:${ipv6}"
echo "   value: ${spfval} -all"
echo
echo "   currently published: $(lookup "${DOMAIN}" TXT)"
echo "   ^ if that reads 'v=spf1 -all' it authorises NOTHING to send as this"
echo "     domain. It must be replaced, not added alongside."
echo

echo "-------------------------------------------------------------------"
echo "3. DMARC  (type TXT, name: _dmarc.${DOMAIN})"
echo "-------------------------------------------------------------------"
echo "   value: v=DMARC1; p=none; rua=mailto:postmaster@${DOMAIN}; fo=1"
echo
echo "   currently published: $(lookup "_dmarc.${DOMAIN}" TXT)"
echo "   ^ start at p=none. Move to p=quarantine, then p=reject, only after a"
echo "     week of clean delivery. p=reject with strict alignment discards"
echo "     mail outright on any DKIM or SPF mistake."
echo

echo "-------------------------------------------------------------------"
echo "4. MX  (type MX, name: @)"
echo "-------------------------------------------------------------------"
echo "   value: 10 ${MAILDOM}"
echo
echo "   currently published: $(lookup "${DOMAIN}" MX)"
echo "   ^ 'NONE' means nothing can deliver to this domain at all;"
echo "     'LOOKUP FAILED' means the DNS query itself did not get answered — retry."
echo

echo "-------------------------------------------------------------------"
echo "5. A/AAAA records — verify, do not change"
echo "-------------------------------------------------------------------"
echo "   @     -> ${ipv4}   (the site, IPv4)"
echo "   mail  -> $(lookup "${MAILDOM}" A)   (IPv4)"
echo "   @     -> $(lookup "${DOMAIN}" AAAA)  (IPv6; publish if the host has one)"
echo "   mail  -> $(lookup "${MAILDOM}" AAAA) (IPv6)"
echo "   ^ mail must NOT be behind a CDN proxy. Cloudflare and similar break"
echo "     mail entirely, and it presents as mail that works locally and"
echo "     vanishes in transit."
echo "   ^ the host's own IPv6 is 2a14:7981:467:272::/124, so the suggested"
echo "     AAAA value for both names is 2a14:7981:467:272::"
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
echo "  Current PTR: $(lookup "$(printf '%s' "${ipv4}" | awk -F. '{print $4"."$3"."$2"."$1}').in-addr.arpa" PTR)"
echo "  ^ 'NONE' means no PTR yet — ask your provider to set it to ${MAILDOM}."
echo
echo "==================================================================="
echo " After publishing:  bash scripts/verify.sh"
echo "==================================================================="
