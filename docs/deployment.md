# Deployment

How the production stack is built, deployed, and recovered. Written for someone
deploying it for the first time on a fresh machine.

Nothing here is automated. Every deploy is a handful of commands you run on the
server.

---

## What runs

Four services, all on one Docker network called `arezoo-prod_default`. Only nginx
publishes a port.

```
                    internet
                       │
                 :80   :443          nginx      TLS, static files, ACME challenge
                       └──────────────►
                                       │  FastCGI
                                       ▼
                                      app       Laravel, php-fpm on :9000
                                       │
                        ┌──────────────┴──────────────┐
                        ▼                             ▼
                    mariadb                       queue     queue:work, opt-in
                   :3306 internal             (database queue)
                        │
                        ▼
                  named volumes                survive `down`, die with `down -v`
```

### Why nginx does all of it

This stack used to be Caddy in front of nginx, with Caddy terminating TLS and
proxying plain HTTP to an nginx that bridged to php-fpm. Two containers, because
two languages: Caddy speaks HTTP, php-fpm speaks FastCGI, and something in the
middle has to translate.

That is no longer true. Certificates come from certbot on the host rather than
from Caddy obtaining its own, so there is one language at the edge and nothing
left to translate. `nginx` is now the only web-facing container: it terminates
TLS, serves the compiled front end and uploads, answers the ACME challenge, and
passes requests to php-fpm.

### Why certificates are not nginx's problem

nginx cannot obtain or renew a certificate, and a renewal that quietly stops is a
failure nobody notices until every visitor sees a browser warning. So certbot runs
on the host, not in the stack, and two things keep that honest:

- it renews on `certbot.timer`, twice a day, against a webroot
- a deploy hook reloads nginx, postfix and dovecot afterwards, because a renewed
  certificate on disk changes nothing for a running server until it is reloaded

`certbot renew --dry-run` proves the first; the second is
`/etc/letsencrypt/renewal-hooks/deploy/10-reload-arezoo`.

The nginx config is a **template** (`docker/nginx/production.conf.template`),
not a plain config, because the image's entrypoint runs `envsubst` over
`/etc/nginx/templates/`. `NGINX_ENVSUBST_FILTER` in `compose.production.yaml`
restricts that to `DOMAIN` and `CERT_PATH` — without it, `envsubst` would also
replace `$host`, `$scheme` and every other nginx variable in the file with an
empty string.

### Why `queue` is behind a profile

No job in the application implements `ShouldQueue`, and there are no scheduled
tasks. The service exists so that adding the first real job is a configuration
change rather than a production incident, not because it has anything to do now:

```bash
docker compose --env-file .env.production -f compose.production.yaml --profile queue up -d queue
```

It is a profile rather than a deletion because on a host that also runs a mail
server, the memory matters — and because removing it and needing it back should
cost one flag.

### Volumes

| Volume | Holds | Losing it means |
|---|---|---|
| `arezoo-prod_mariadb-data` | the entire database | every account, wish and payment |
| `arezoo-prod_app-storage` | uploaded avatars and wish images | every user's picture |
| `arezoo-prod_mailpit-data` | Mailpit's captured mail, opt-in only | nothing that matters |

`docker compose down` keeps them. `docker compose down -v` destroys them, and is
the reason that flag exists: it should be a deliberate way to lose them, not a
thing that happens because a flag was already there.

Certificates and the ACME webroot are **bind mounts** from the host
(`/etc/letsencrypt`, `/var/www/certbot`), not volumes. They are produced by
certbot rather than by this stack, so they belong to the host and outliving the
stack is the point.

---

## First deploy

### 1. Point the domain at the server

An A record for your domain to the server's public IP, and an AAAA record only if
the server really has IPv6. Wait for it to resolve:

```bash
dig +short your-domain.com
```

Ports 80 and 443 must be reachable from the internet. Port 80 is not optional
even though everything is served over HTTPS — it is where the ACME challenge is
answered.

### 2. Get the code on the server

```bash
git clone https://github.com/s0h3illl/arezoo.git
cd arezoo
```

You need the repository on the server, not just the image: the nginx template is
mounted from the working tree, and the image is built here so that the running
code is the code on this machine.

### 3. Stop anything already holding ports 80 and 443

A first deploy onto a host that is already serving something has to give the
ports up first, or the new container cannot bind them:

```bash
sudo systemctl stop nginx && sudo systemctl disable nginx
```

Disable it, do not just stop it. A reboot with it merely stopped brings the old
site back and the two fight for the ports.

### 4. Create the environment file

```bash
cp .env.production.example .env.production
```

Then edit it. These have no defaults and the stack refuses to start without
them:

```bash
# generate the app key, then paste the output (no quotes) into APP_KEY
docker compose --env-file .env.production -f compose.production.yaml \
  run --rm app php artisan key:generate --show

# generate two different database passwords
openssl rand -base64 24
openssl rand -base64 24
```

| Variable | What it is |
|---|---|
| `APP_KEY` | from `key:generate --show` |
| `APP_URL` | `https://your-domain.com` — builds every emailed link |
| `DOMAIN` | your-domain.com — the name in the nginx `server_name` |
| `CERT_PATH` | where certbot keeps the certificate, e.g. `/etc/letsencrypt/live/your-domain.com` |
| `ACME_EMAIL` | a real address; expiry warnings go there |
| `DB_PASSWORD`, `DB_ROOT_PASSWORD` | two different random values |

`.env.production` is gitignored. Never commit it, and never paste a real key into
`.env.production.example` — that is the one mistake here that cannot be undone by
rotating it.

### 5. Issue the certificate

This has to happen **before** the stack starts, and while nothing else holds port
80. See [Certificates](#certificates) below for the full sequence.

```bash
sudo certbot certonly --standalone -d your-domain.com -d www.your-domain.com
# then switch renewal to webroot, as described below
```

The certificate should cover the bare domain, `www`, and the mail host in one
request. A wildcard is not obtainable here: it needs a DNS-01 challenge and API
access to the DNS provider, and a certificate nothing on the machine can renew is
worse than no wildcard at all.

### 6. Build and start

```bash
docker compose --env-file .env.production -f compose.production.yaml build
docker compose --env-file .env.production -f compose.production.yaml up -d
docker compose --env-file .env.production -f compose.production.yaml ps
```

Wait for `app` to report `healthy`. It is healthy only once the entrypoint has
finished: dependencies verified, configuration compiled, manifest found.

The build is slow on a small machine — it runs a full `bun install` and a Vite
production build inside Docker. Fifteen minutes on two cores is normal, and it is
not a hang.

### 7. Run the migrations

```bash
docker compose --env-file .env.production -f compose.production.yaml \
  exec app php artisan migrate --force
```

This is not automatic, and that is deliberate. An automatic `migrate` on boot means
a deploy of a broken migration takes the site down during a rollout, and a
rollback of the application code does not roll back the schema. You read the
migration, then you run it.

### 8. Set up mail — before anyone tries to register

**Signup does not work until this is done.** A new account must confirm its address
before it can be used, and the confirmation arrives by email. With the log driver
nothing is sent, the user is told to check an inbox that does not exist, and every
registration stalls at the same point.

`MAIL_MAILER=log` ships as the default, and every SMTP example in the environment
template is commented out. Once a real server answers, fill one of them in and set
`MAIL_FROM_ADDRESS` to a domain whose SPF and DKIM records you control — otherwise
your mail is spam.

See **[mail-server.md](mail-server.md)** for standing up a self-hosted mail server:
the DNS records, and the order to test delivery in before connecting the
application.

To read what would have been sent before then:

```bash
# the log driver
docker compose --env-file .env.production -f compose.production.yaml \
  exec app tail -f storage/logs/laravel.log

# or a real inbox, on localhost only
docker compose --env-file .env.production -f compose.production.yaml \
  --profile mail up -d mailpit
# then open http://127.0.0.1:8025
```

Mailpit is behind a profile and bound to 127.0.0.1 on purpose: on a public host it
would be an open SMTP relay, and mail delivered to it looks sent and arrives
nowhere.

### 9. Verify

```bash
./scripts/verify.sh
```

That checks the containers, TLS, the security headers, the migrations, the mail
ports, and — through DNS-over-HTTPS — the records the outside world actually sees.
It reports rather than changes, and is safe to run at any time.

Then register an account yourself and confirm the email arrives, and use its reset
link. Those two exercise the parts of the stack most likely to be wrong: the
proxy chain, the signed URL, and the mail transport.

---

## Certificates

Two methods, deliberately, because they have to work at different moments.

**Issuance is `--standalone`.** certbot starts its own throwaway web server on
port 80 for the duration of the challenge, which is only possible while nothing
else holds that port. On a first deploy that is true, because the previous web
server has just been stopped.

**Renewal is `--webroot`.** By the time renewal runs, the stack's nginx owns port
80 and a standalone authenticator would fail — silently, every time, until the
certificate expires. So the renewal configuration is switched immediately after
issuance:

```bash
# /etc/letsencrypt/renewal/your-domain.com.conf
authenticator = webroot
webroot_path = /var/www/certbot,
```

The stack's nginx already serves `/.well-known/acme-challenge/` out of that
directory, so renewal becomes a file being written and a reload, with no downtime
and no port juggling. Prove it:

```bash
sudo certbot renew --dry-run
```

And the deploy hook, which is what makes a renewed certificate take effect:

```bash
sudo cat /etc/letsencrypt/renewal-hooks/deploy/10-reload-arezoo
```

---

## Deploying a change

```bash
git pull
docker compose --env-file .env.production -f compose.production.yaml build
docker compose --env-file .env.production -f compose.production.yaml up -d
docker compose --env-file .env.production -f compose.production.yaml \
  exec app php artisan migrate --force
```

If you changed a migration, read it before running it.

The build is a fresh image rather than a layer cache hit whenever application code
or a dependency changes, which is the point: uploads and the database live in
volumes, so replacing the image discards nothing.

A change to `compose.production.yaml` or to the nginx template needs `up -d` to
recreate the container, not just a restart — the template is rendered at container
start, so `restart` keeps the old one.

---

## Backups

The database and the uploads. Everything else is reproducible from the repository.

```bash
# database
docker compose --env-file .env.production -f compose.production.yaml \
  exec -T mariadb mariadb-dump -u arezoo -p"$DB_PASSWORD" --single-transaction \
  arezoo > "backup-$(date +%F-%H%M).sql"

# uploads
docker run --rm -v arezoo-prod_app-storage:/data -v "$PWD":/backup \
  alpine tar czf "/backup/uploads-$(date +%F-%H%M).tar.gz" -C /data .
```

`--single-transaction` matters: without it a dump taken while someone registers can
be internally inconsistent.

Restoring:

```bash
docker compose --env-file .env.production -f compose.production.yaml \
  exec -T mariadb mariadb -u arezoo -p"$DB_PASSWORD" arezoo < backup.sql
```

Do not put either artifact in the repository. Do not leave them on the same disk as
the only copy.

---

## When something is wrong

```bash
# everything, most recent last
docker compose --env-file .env.production -f compose.production.yaml logs --tail=200

# one service
docker compose --env-file .env.production -f compose.production.yaml logs --tail=200 app
```

The app's own log is inside the container and dies with it:

```bash
docker compose --env-file .env.production -f compose.production.yaml \
  exec app tail storage/logs/laravel.log
```

### A 502 from nginx

nginx reached `app` and `app` could not be reached. Check `app` is up and healthy —
it depends on nothing being broken, and it refuses to open port 9000 until the
entrypoint has finished, so an unhealthy `app` is a crash on boot. Read its logs.

### A 403 on the homepage, assets still working

The document root is missing `index.php`. The front controller is not being served,
and nginx reports "directory index of ... is forbidden".

### A 419 on any form submission

Laravel did not receive the CSRF token. Usually a proxy is stripping it, or the
`X-XSRF-TOKEN` header is not reaching the app.

### Session cookies arrive without `Secure`

Laravel thinks the request arrived over plain HTTP. `trustProxies` is in
`bootstrap/app.php`, not in the environment file, and the header it reads has to be
set by nginx. Check that the `location = /index.php` block still carries:

```nginx
fastcgi_param HTTP_X_FORWARDED_PROTO $scheme;
```

**It must be `$scheme`, not the incoming `X-Forwarded-Proto` header.** While a
proxy sat in front of nginx that header was trustworthy, because the only client
that could reach the port was that proxy and it overwrote the value. nginx is now
the edge, so the header arrives from whoever is making the request, and passing it
through would let a visitor send `X-Forwarded-Proto: http` and talk Laravel into
issuing a session cookie without `Secure` and building `http://` links into
password-reset mail.

### Emails contain http:// links, or links do not work

The request that generated the link was not seen as HTTPS. Same check as above.

### nginx will not start: "a duplicate default server"

The base image's own `default.conf` is still present alongside the generated one.
The image removes it in the `production-web` target; if you copied the config in
by hand rather than through the template, remove
`/etc/nginx/conf.d/default.conf` yourself.

### nginx will not start, and every `${...}` is empty

`NGINX_ENVSUBST_FILTER` is missing or wrong. Without it `envsubst` substitutes
every environment variable it can see, which includes `$host`, `$scheme` and
`$uri`. It belongs in the `nginx` service's `environment:` block.

### Certificate errors

```bash
docker compose --env-file .env.production -f compose.production.yaml logs nginx
openssl x509 -in /etc/letsencrypt/live/your-domain.com/fullchain.pem -noout -dates
```

Usually DNS not pointing here yet, ports 80/443 blocked, `CERT_PATH` pointing
somewhere certbot did not write, or the domain has hit Let's Encrypt's rate
limit. The limit is five duplicate certificates per week; if a container has been
restart-looping, the domain may be locked out for hours. Check before deleting
anything under `/etc/letsencrypt` — that is what makes the limit recoverable.

### The certificate renewed but the site still serves the old one

The deploy hook did not run, or could not find the container. Run it by hand:

```bash
sudo /etc/letsencrypt/renewal-hooks/deploy/10-reload-arezoo
```

---

## Rolling back

```bash
git checkout <previous-sha>
docker compose --env-file .env.production -f compose.production.yaml build
docker compose --env-file .env.production -f compose.production.yaml up -d
```

This rolls back the code, not the schema. If the release included a migration,
either write a compensating migration or restore the database from a backup —
deciding which is easier before you deploy is the point of the backup.
