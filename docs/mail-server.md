# Mail server

How to give this application a real mail transport, so that signup and password
reset work instead of silently stalling.

Companion to [deployment.md](deployment.md). That document assumes mail is already
able to send; this one is about getting to that point.

---

## What is actually broken right now

`MAIL_MAILER=log` is the shipped default, and it is correct until a mail server
exists. But the failure it causes is not an error — it is silence:

- a visitor registers
- the account is created
- Laravel writes the verification message to `storage/logs/laravel.log` inside the
  container
- nothing arrives
- the visitor is told to check an inbox that no message will ever reach

There is no exception, no non-2xx response, and nothing to grep. Every signup stalls
at the same point, and the only symptom is a conversion rate of zero. Get a working
server before telling anyone the site is up.

---

## Choosing where it runs

Mailcow is a good choice for this: mature, well documented, and it gives you a
webmail UI, IMAP, and ActiveSync as well as the SMTP submission the application
needs. It is also a *large* stack — 10+ containers, officially 6 GiB of RAM minimum.

**A separate server for mail is the strongly recommended layout**, for reasons that
are not about the application:

- Mailcow documents needing a dedicated host, and it binds ports 25, 80, 443, 110,
  143, 465, 587, 993, 995 and 4190. The application's Caddy already owns 80 and 443,
  so sharing a host means reconfiguring one to get the other.
- A second IP keeps the sending reputation of transactional mail separate from
  whatever else the site sends, and gives you a place to fix a blacklisting without
  touching the application server.
- Mailcow running out of memory stops being a website outage.
- A second small VPS is cheaper than the debugging it prevents.

The rest of this document assumes that layout, and says what changes if you insist
on one host.

---

## Prerequisites

These are the ones that take time to arrange, because they are not in your hands.

```bash
free -h | awk '/Mem/{print "RAM:", $2}'          # 6 GiB minimum for the full suite
swapon --show                                  # want 1+ GiB
# Is outbound 25 blocked? Most providers block it by default.
timeout 10 bash -c 'cat </dev/null >/dev/tcp/mail.olabs.net/25' && echo "25 open" || echo "25 BLOCKED"
```

**Ask your provider to set PTR/rDNS** on the mail server's IP to
`mail.yourdomain.com`. You cannot do this yourself, and every receiving domain will
reject mail from an IP with no reverse DNS. Also ask them to confirm **outbound
port 25 is unblocked** — if it is closed, none of the rest works.

---

## Install

```bash
git clone https://github.com/mailcow/mailcow-dockerized.git
cd mailcow-dockerized
./generate_config.sh        # it will offer to trim ClamAV if RAM is low
docker compose pull
docker compose up -d
```

### Trimming for a 4 GB host

If mailcow is on a small VPS, these are the levers, in order of how much they save:

| Change | Where | Saves |
|---|---|---|
| `WOWorkersCount = "20"` → `"4"` | `data/conf/sogo/sogo.conf` | 1–2 GB — the big one; each SOGo worker reaches ~350 MB |
| `SKIP_CLAMD=y` | `mailcow.conf` | ~1 GB |
| `SxVMemLimit = 384` → `256` | `data/conf/sogo/sogo.conf` | caps any runaway worker |
| `SKIP_OLEFY=y` | `mailcow.conf` | ~100 MB (document scanner) |
| `SKIP_FTS=y` | `mailcow.conf` | ~200 MB (Flatcurve indexing) |

Trimming costs you antivirus scanning, attachment inspection and full-text search.
On 4 GB those are the right things to lose; a server that gets OOM-killed is worse
than one that lets a document attachment through.

---

## DNS

Add these before testing. Nothing else is worth doing until they are correct.

| Type | Name | Value | Notes |
|---|---|---|---|
| A | `mail` | mail server IP | **must not** be behind a CDN proxy — Cloudflare and similar break mail |
| MX | `@` | `mail.yourdomain.com` | priority 10. `v=spf1 mx -all` implies it |
| A | `@` | app server IP | the site itself |
| TXT | `@` | `v=spf1 mx -all` | tighten to `-all` only after delivery is reliable |
| TXT | `dkim._domainkey` | from the Mailcow UI | copy it exactly |
| TXT | `_dmarc` | `v=DMARC1; p=none; rua=mailto:you@…` | **start at `none`** |
| PTR | — | IP → `mail.yourdomain.com` | set by your provider |

Optional but good: a CAA record (`0 issue "letsencrypt.org"`) pinning who may issue
for the domain.

**Do not start DMARC at `p=quarantine`.** That is a policy telling receiving
servers to send your mail to spam, and until reputation is established that is
exactly what will happen — to yourself.

Verify what the world actually sees before going further:

```bash
dig +short MX yourdomain.com
dig +short TXT yourdomain.com
dig +short -x <mail-server-ip>      # must print mail.yourdomain.com
```

---

## Test delivery before connecting the application

This ordering matters. If mail does not reach Gmail, pointing the application at
this server only gives you a harder way to find that out.

```bash
# send a test from the mail host itself
swaks --to you@gmail.com --server 127.0.0.1 --from you@yourdomain.com \
      --auth LOGIN --auth-user you@yourdomain.com --auth-password '…' -t
```

Then check three destinations:

1. **A Gmail account — the one that matters.** Check the spam folder, not just the
   inbox. A fresh IP has no reputation, and correct DNS alone does not override
   that.
2. Your own inbox, to prove the path works end to end.
3. A mailcow mailbox, to prove local delivery works.

When Gmail files it as spam, use the "Report not spam" button once. That is a
direct positive signal to Gmail, and doing it a few times a day for a week moves a
new IP from "probably a spammer" to "probably fine". Mailcow's UI shows the
Rspamd score for each message, which is the fastest way to tell whether you have a
reputation problem or an authentication problem.

---

## Connect the application

Create a dedicated mailbox for the application — `arezoo@yourdomain.com` — and use
an app-specific password if your Mailcow version offers them under the mailbox's
**Apps** tab. Then set these in `.env.production`:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=arezoo@yourdomain.com
MAIL_PASSWORD=<the app password>
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=hello@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

Three details that are easy to get wrong:

- **Use a hostname, not `127.0.0.1`.** Inside the container the loopback address is
  that container, so a loopback host sends mail to the application itself.
- **`MAIL_FROM_ADDRESS` must be in the same domain as `MAIL_USERNAME`.** A mismatch
  fails authentication at the receiving end and the message is rejected or filed as
  spam.
- **Port 587 with `MAIL_SCHEME=tls` is STARTTLS.** Port 465 is implicit TLS and
  needs `MAIL_SCHEME=smtps`. Mixing them up produces a connection error that reads
  like a firewall problem.

Then restart and test a real registration:

```bash
docker compose --env-file .env.production -f compose.production.yaml up -d
docker compose --env-file .env.production -f compose.production.yaml \
  exec app php artisan config:clear
```

Register with a real address, receive the message, click the link, and confirm the
account activates. Then test **forgot password** as well — it builds a signed URL
from the current request, which is the one place the proxy configuration shows up
in the mail itself.

---

## If you must run Mailcow on the same host as the application

Then ports 80 and 443 are already taken by Caddy, and Mailcow will fail to start
with `bind: address already in use`. Move its web interface and put Caddy in front:

```ini
# mailcow.conf
HTTP_BIND=172.17.0.1
HTTP_PORT=8080
HTTPS_BIND=172.17.0.1
HTTPS_PORT=8443
SKIP_LETS_ENCRYPT=y        # Caddy holds the certificate
```

Do not use 8081, 9081 or 65510 — Mailcow reserves them. Add
`extra_hosts: ["host.docker.internal:host-gateway"]` to the `caddy` service, add a
site block for `{$MAILCOW_HOST}` proxying to `host.docker.internal:8080`, and set
`TRUSTED_PROXIES` on `nginx-mailcow` so it believes the forwarded scheme.

Also drop the `queue` service from `compose.production.yaml` while you are short on
memory — no job in this application implements `ShouldQueue`, so it costs memory
for nothing.

---

## Troubleshooting

**Mail from the server is rejected by Gmail with no SPF/DKIM error.** Almost always
reputation, not configuration. Check the IP at mxtoolbox.com, and look at the
Rspamd score in the Mailcow UI.

**Port 25 refuses connections.** Your provider blocks it. This is a ticket, not a
configuration change.

**Sending works, receiving does not.** Check the MX record resolves, and that PTR
is actually set. Then check for a firewall dropping inbound 25.

**Connection times out on 587 from the app container.** The app cannot reach the
mail host. If they are the same machine, use a hostname that resolves to a real
address rather than 127.0.0.1, and confirm the port is published.

**Password-reset links in mail are `http://`.** The request that generated the link
was not seen as HTTPS. This is the proxy chain, not mail: see
[deployment.md](deployment.md#session-cookies-arrive-without-secure).

**Registration activates but the mail never arrived.** Check whether the account is
actually marked verified — the flow may have completed via a link you opened in
another test.
