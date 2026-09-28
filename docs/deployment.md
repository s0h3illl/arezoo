# Deployment

How the production stack is built, deployed, and recovered. Written for someone
deploying it for the first time on a fresh machine.

Nothing here is automated. Every deploy is a handful of commands you run on the
server.

---

## What runs

Five services, all on one Docker network called `arezoo-prod_default`. Only Caddy
publishes a port.

```
                    internet
                       │
                 :80   :443          Caddy      TLS, HSTS, request logging
                       └──────────────►
                                       │  plain HTTP, internal network only
                                       ▼
                                      web       static assets, FastCGI bridge
                                       │  FastCGI
                                       ▼
                                      app       Laravel, php-fpm on :9000
                                       │
                        ┌──────────────┴──────────────┐
                        ▼                             ▼
                    mariadb                         queue    queue:work
                   :3306 internal              (database queue)
                        │
                        ▼
                  named volumes                survive `down`, die with `down -v`
```

### Why there is a `web` container

Caddy terminates TLS and nginx talks to php-fpm, because those are the two
languages each of them speaks. `reverse_proxy app:9000` — pointing Caddy straight
at php-fpm — connects successfully and immediately drops the request, producing a
502 with nothing useful in either log. `docker/nginx/production.conf` is that
bridge.

### Why `queue` is running with nothing to do

No job in the application implements `ShouldQueue`, and there are no scheduled
tasks. The service exists so that adding the first real job is a configuration
change rather than a production incident.

### Volumes

| Volume | Holds | Losing it means |
|---|---|---|
| `arezoo-prod_mariadb-data` | the entire database | every account, wish and payment |
| `arezoo-prod_app-storage` | uploaded avatars and wish images | every user's picture |
| `arezoo-prod_caddy-data` | certificates and the ACME account | re-requesting; rate limits apply |
| `arezoo-prod_caddy-config` | Caddy's runtime config | harmless |

`docker compose down` keeps all four. `docker compose down -v` destroys all four,
and is the reason that flag exists: it should be a deliberate way to lose them, not
a thing that happens because a flag was already there.

---

## First deploy

### 1. Point the domain at the server

An A record for your domain to the server's public IP, and an AAAA record only if
the server really has IPv6. Wait for it to resolve:

```bash
dig +short your-domain.com
```

Caddy cannot obtain a certificate for a name that does not resolve to the machine
it is running on, and the failure is a retry loop rather than a clear message.

Ports 80 and 443 must be reachable from the internet. Port 80 is not optional
even though everything is served over HTTPS — it is where the ACME challenge is
answered.

### 2. Get the code on the server

```bash
git clone https://github.com/s0h3illl/arezoo.git
cd arezoo
```

You need the repository on the server, not just the image: the Caddyfile and the
nginx config are mounted from the working tree, and the image is built here so that
the running code is the code on this machine.

### 3. Create the environment file

```bash
cp .env.production.example .env.production
```

Then edit it. These four have no defaults and the stack refuses to start without
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
| `DOMAIN` | your-domain.com — the name the certificate is issued for |
| `ACME_EMAIL` | a real address; expiry warnings go there |
| `DB_PASSWORD`, `DB_ROOT_PASSWORD` | two different random values |

`.env.production` is gitignored. Never commit it, and never paste a real key into
`.env.production.example` — that is the one mistake here that cannot be undone by
rotating it.

### 4. Build and start

```bash
docker compose --env-file .env.production -f compose.production.yaml build
docker compose --env-file .env.production -f compose.production.yaml up -d
docker compose --env-file .env.production -f compose.production.yaml ps
```

Wait for `app` to report `healthy`. It is healthy only once the entrypoint has
finished: dependencies verified, configuration compiled, manifest found.

### 5. Run the migrations

```bash
docker compose --env-file .env.production -f compose.production.yaml \
  exec app php artisan migrate --force
```

This is not automatic, and that is deliberate. An automatic `migrate` on boot means
a deploy of a broken migration takes the site down during a rollout, and a
rollback of the application code does not roll back the schema. You read the
migration, then you run it.

### 6. Set up mail — before anyone tries to register

**Signup does not work until this is done.** A new account must confirm its address
before it can be used, and the confirmation arrives by email. With the log driver
nothing is sent, the user is told to check an inbox that does not exist, and every
registration stalls at the same point.

`MAIL_MAILER=log` ships as the default, and every SMTP example in the environment
template is commented out. Once a real server answers, fill one of them in and set
`MAIL_FROM_ADDRESS` to a domain whose SPF and DKIM records you control — otherwise
your mail is spam.

See **[mail-server.md](mail-server.md)** for standing up a self-hosted Mailcow: the
DNS records, the resource trims needed on a small host, and the order to test
delivery in before connecting the application.

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

### 7. Verify

```bash
curl -I https://your-domain.com/
docker compose --env-file .env.production -f compose.production.yaml ps
docker compose --env-file .env.production -f compose.production.yaml logs --tail=50
```

Then register an account yourself and confirm the email arrives, and use its reset
link. Those two exercise the parts of the stack most likely to be wrong: the proxy
chain, the signed URL, and the mail transport.

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
# what is running, and is it healthy
docker compose --env-file .env.production -f compose.production.yaml ps

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

### A 502 from Caddy

Caddy reached `web` and `web` could not reach the app. Check `web` and `app` are
both up. `web` depends on `app` being healthy, so this usually means the app
crashed on boot — read its logs.

### A 403 on the homepage, assets still working

The document root in the `web` image is missing `index.php`. The front controller
is not being served, and nginx reports "directory index of ... is forbidden".

### A 419 on any form submission

Laravel did not receive the CSRF token. Usually a proxy is stripping it, or the
`X-XSRF-TOKEN` header is not reaching the app. `docker/nginx/production.conf`
passes `X-Forwarded-Proto` through from Caddy for exactly this class of problem —
check the `fastcgi_param` lines still carry the forwarded headers.

### Session cookies arrive without `Secure`

Laravel thinks the request arrived over plain HTTP. Check that Caddy is setting
`X-Forwarded-Proto` and that `docker/nginx/production.conf` is passing the header
through rather than replacing it with `$scheme`. `trustProxies` is in
`bootstrap/app.php`, not in the environment file.

### Emails contain http:// links, or links do not work

The request that generated the link was not seen as HTTPS. Same check as above.

### Certificate errors

```bash
docker compose --env-file .env.production -f compose.production.yaml logs caddy
```

Usually DNS not pointing here yet, ports 80/443 blocked, or the domain has hit
Let's Encrypt's rate limit. The limit is five duplicate certificates per week; if
the container has been restart-looping, the domain may be locked out for hours.
Check before deleting `arezoo-prod_caddy-data` — that is what makes the limit
recoverable.

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
