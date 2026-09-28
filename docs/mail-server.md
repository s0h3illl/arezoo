# Mail server

How to give this application a real mail transport, so that signup and password
reset work instead of silently stalling.

Companion to [deployment.md](deployment.md). That document assumes mail is already
able to send; this one is about getting to that point.

---

## What is actually broken by default

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

## Which server

Two things decide it, and both are about the machine rather than the application.

**Mailcow does not fit on a small host.** It is mature and gives you webmail, IMAP
and ActiveSync, but it is 10+ containers and officially 6 GiB of RAM. It also binds
ports 25, 80, 443, 110, 143, 465, 587, 993, 995 and 4190, so sharing a host with
the web stack means reconfiguring one to get the other.

**emailwiz does fit, and is what this host runs.** Postfix, Dovecot, SpamAssassin,
OpenDKIM and fail2ban — a few hundred megabytes, and it uses the ports mail
actually needs. It has no webmail, which is a real limitation: you read mail with a
normal client (Thunderbird, K-9, mutt) rather than in a browser. It authenticates
against Unix accounts, so a mailbox is a user, not a row in a table.

A separate server for mail remains the better *layout* — it keeps sending
reputation separate from the web host, and a mail server that runs out of memory is
not a website outage. On a host this size, though, it is a second VPS rather than a
configuration choice, and emailwiz on the same host is the trade being made
deliberately.

---

## Order of operations

The sequence matters more than any individual step, because each one depends on
the state the previous one left.

1. **The certificate, while port 80 is still free.** emailwiz asks certbot for a
   certificate for the mail host, and by default uses the standalone authenticator,
   which needs port 80 to itself. Once the web stack is up it does not have it, and
   the install fails on a step that looks nothing like the cause.

   So: issue **one** certificate covering the site, `www` and the mail host first,
   then symlink the mail host's name at it:

   ```bash
   ln -sfn your-domain.com /etc/letsencrypt/live/mail.your-domain.com
   ```

   emailwiz sees the directory exists and skips certbot entirely. One certificate,
   one renewal, no chance of the two drifting apart — and a symlink is safe across
   renewal because certbot updates what it points at rather than replacing the
   entry. See [deployment.md](deployment.md#certificates).

2. **emailwiz itself.** It prints the DNS records at the end. Do not skip to
   configuring the application — the records have to exist before its mail is worth
   testing.

   Debian 13 ships dovecot 2.4, and emailwiz still writes dovecot 2.3 syntax, which
   ‌2.4 refuses to start with (`dovecot_config_version` missing, `ssl_cert` renamed,
   bare `passdb { }` / `userdb { }` blocks, `mail_location` split into
   `mail_driver` + `mail_path`, `plugin { }` replaced by `sieve_script` sections,
   `%n`-style variables gone). `scripts/setup-2-emailwiz.sh` runs
   `scripts/dovecot-24-migrate.py` right after emailwiz for exactly this reason; the
   migration is idempotent and validates the result before restarting dovecot.

3. **The DNS records.** Four of them, plus one only the hosting provider can set.

4. **Test delivery**, before pointing the application at it. If mail does not reach
   Gmail, connecting the application only gives you a harder way to find that out.

5. **Connect the application**, and register a real account.

---

## The mailboxes

Two mailboxes, two different roles, and no third way — emailwiz authenticates
against Unix accounts, so each mailbox is a system user:

```bash
useradd -m -G mail noreply     # the application's sender
useradd -m -G mail info        # the human mailbox: replies and DMARC reports
```

A user in the `mail` group can receive mail. They are deliberately different
things: `noreply`'s password is what `.env.production`'s `MAIL_PASSWORD` holds,
so the application's sending credential can be rotated without touching a
person's mail access; `info` is the address the site publishes, and where a
human (you) reads the replies and the DMARC aggregate reports.

`postmaster@` is mandatory for any domain that accepts mail (RFC 5321 4.5.1),
and `root@` collects the system's local cron and error mail. Both are aliased to
`info` so they land somewhere a person actually reads:

```bash
printf '\npostmaster: info\nroot: info\n' >> /etc/aliases
newaliases
```

**The From address must have the same local part as the login, not merely the same
domain.** emailwiz writes this into Postfix:

```
/^(.*)@your-domain\.com$/   ${1}
```

That is `smtpd_sender_login_maps`, and it resolves an envelope sender to the login
name permitted to use it. Sending as `hello@your-domain.com` while authenticating as
`noreply@your-domain.com` is therefore **refused at submission** with a sender login
mismatch — a connection that succeeds, authenticates, and then fails on the last
step. Put the friendly part in `MAIL_FROM_NAME` instead.

---

## DNS

Add these before testing. Nothing else is worth doing until they are correct.

| Type | Name | Value | Notes |
|---|---|---|---|
| A | `mail` | mail server IP | **must not** be behind a CDN proxy — Cloudflare and similar break mail |
| A | `@` | app server IP | the site itself |
| MX | `@` | `mail.yourdomain.com` | priority 10 |
| TXT | `@` | from emailwiz's output | replaces whatever SPF was there |
| TXT | `mail._domainkey` | from emailwiz's output | **the selector is `mail`**, which is not always what a stale record used |
| TXT | `_dmarc` | `v=DMARC1; p=none; rua=mailto:you@…` | **start at `none`** |
| PTR | — | IP → `mail.yourdomain.com` | set by your provider, not by you |

Two of these are the ones that decide whether anything works, and both are easy to
get wrong on a domain that has had mail records before:

- **A stale SPF record is a silent failure.** `v=spf1 -all` means *nothing may send
  as this domain*, and it keeps meaning that until it is replaced. It produces no
  error at the sender — only rejections at every receiver.
- **A revoked DKIM key is a silent failure.** A record reading `v=DKIM1; p=` is a
  key that was withdrawn. It looks present and authenticates nothing.

**Do not start DMARC at `p=reject`.** That is a policy telling receiving servers to
send your mail to spam — or, with strict alignment (`adkim=s; aspf=s`), to discard
it outright. Until DKIM and SPF are both verified and aligned, that is exactly what
will happen, to yourself. Move to `p=quarantine` and then `p=reject` only after a
week of clean delivery.

Verify what the world actually sees before going further:

```bash
dig +short MX yourdomain.com
dig +short TXT yourdomain.com
dig +short TXT mail._domainkey.yourdomain.com
dig +short -x <mail-server-ip>      # must print mail.yourdomain.com
```

`./scripts/verify.sh` does all of the above through DNS-over-HTTPS, so it reports the
published records rather than what a local resolver has cached.

### The two things you cannot do yourself

Both are tickets to your hosting provider, and no amount of configuration on this
machine substitutes for them:

- **Reverse DNS (PTR).** An IP with no PTR is rejected or treated as spam by Gmail
  and Outlook. There is no way to set it from the server.
- **Inbound port 25.** Most providers block it by default. Outbound 25 being open
  is not the same thing, and is not sufficient.

---

## Test delivery before connecting the application

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
3. A local mailbox on the mail host, to prove local delivery works.

When Gmail files it as spam, use the "Report not spam" button once. That is a
direct positive signal to Gmail, and doing it a few times a day for a week moves a
new IP from "probably a spammer" to "probably fine".

---

## Connect the application

Then set these in `.env.production`:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=<the noreply mailbox password>
MAIL_SCHEME=smtp
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

Four details that are easy to get wrong:

- **Use a hostname, not `127.0.0.1`.** Inside the container the loopback address is
  that container, so a loopback host sends mail to the application itself.
- **The From local part must equal the login local part**, as described above.
- **`MAIL_FROM_ADDRESS` must be in a domain whose DKIM you control.** A mismatch
  fails at the receiving end and the message is rejected or filed as spam.
- **`MAIL_SCHEME` is `smtp` for port 587, `smtps` for port 465.** The old
  `tls` value was a SwiftMailer-ism; Laravel's Symfony transport accepts exactly
  `smtp` and `smtps` and throws "unsupported scheme" for anything else. With
  `smtp` on 587 the transport negotiates STARTTLS when the server offers it,
  which postfix here does. Mixing the two up produces a connection error that
  reads like a firewall problem.

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

## Troubleshooting

**Mail from the server is rejected by Gmail with no SPF/DKIM error.** Almost always
reputation, not configuration. Check the IP at mxtoolbox.com.

**Port 25 refuses connections.** Your provider blocks it. This is a ticket, not a
configuration change.

**Sending works, receiving does not.** Check the MX record resolves, and that PTR
is actually set.

**Submission authenticates and then fails.** Sender login mismatch — see
[the mailbox section](#the-mailbox-for-the-application).

**Connection times out on 587 from the app container.** The app cannot reach the
mail host. If they are the same machine, use a hostname that resolves to a real
address rather than 127.0.0.1.

**Password-reset links in mail are `http://`.** The request that generated the link
was not seen as HTTPS. This is the proxy chain, not mail: see
[deployment.md](deployment.md#session-cookies-arrive-without-secure).

**Registration activates but the mail never arrived.** Check whether the account is
actually marked verified — the flow may have completed via a link you opened in
another test.
