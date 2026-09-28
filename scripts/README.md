# Operational scripts

These exist as executable documentation. Each one does a thing that is easy to get
subtly wrong, and the reasoning it encodes is worth more than the commands — the
`arezoo.me` certificate that sat on the server unrenewed is what happens when these
steps are done from memory.

Run them from the repository root, in this order.

## Prerequisites

| Script | Needs `sudo` | When |
| --- | --- | --- |
| `setup-1-certificates.sh` | yes | first, before anything binds port 80 |
| `setup-2-emailwiz.sh` | yes | after setup-1 |
| `show-dns-records.sh` | no | after setup-2, to publish DNS |
| `verify.sh` | no | after `docker compose up -d` |
| `test-header-spoofing.sh` | no | any time; needs `docker` |

## The order is not interchangeable

`setup-1` issues the certificate with certbot's `--standalone` authenticator, which
starts a throwaway web server on port 80. It therefore stops the legacy nginx and
verifies the port is actually free. Running it after the compose stack is up fails,
because the stack's nginx holds the port.

It then rewrites the renewal configuration to use `--webroot`, because standalone
renewal needs port 80 free *every three months forever* and the stack will be
holding it. The stack's nginx serves `/.well-known/acme-challenge/` out of
`/var/www/certbot`, so from then on renewal is a file write and a reload.

That is why the renewal dry-run is in `verify.sh` and not in `setup-1`: a webroot
challenge is fetched over HTTP by Let's Encrypt, so it can only succeed while
something is serving that directory. `setup-1` deliberately leaves port 80 empty,
so a dry-run there could only ever fail.

## Descriptions

### `setup-1-certificates.sh`

Installs certbot, frees port 80, issues one SAN certificate covering
`arezoo.me`, `www.arezoo.me` and `mail.arezoo.me`, switches renewal to webroot,
installs a deploy hook that reloads nginx, postfix and dovecot, enables
`certbot.timer`, and symlinks `live/mail.arezoo.me` at the certificate so emailwiz
finds it.

A wildcard certificate is deliberately not used. It would need a DNS-01 challenge
and API credentials for the DNS provider, which do not exist on this host — which
is exactly why the previous wildcard had no renewal mechanism at all.

### `setup-2-emailwiz.sh`

Seeds debconf so the postfix install does not open an interactive dialog on a
machine with no one watching, runs emailwiz, and creates the `arezoo` mailbox. The
mailbox password is read from `~/.mailpass` and never appears in the repository or
in this file.

### `show-dns-records.sh`

Prints each required record next to the value currently published, read through
DNS-over-HTTPS so it reflects the real world rather than a local resolver's cache.

### `verify.sh`

Reports rather than changes, so it is safe to run at any time. It separates facts
about this machine from facts about the outside world's opinion of it, because the
honest answer to "is it working" differs for each. Reverse DNS and inbound port 25
are in the second category and cannot be established from here at all.

### `test-header-spoofing.sh`

Proves the nginx configuration cannot be tricked into a forged
`X-Forwarded-Proto`. `bootstrap/app.php` calls `trustProxies(at: '*')`, so Laravel
believes whatever the header says; a visitor able to set it to `http` would strip
`Secure` from session cookies and put `http://` links in password-reset emails.

It asserts on the *absence* of the forged values in what the application receives,
and includes a control run with the defence removed — because a test that cannot
fail proves nothing.

## `fcgi_dump.py`

Not a deployment step. A minimal FastCGI responder that prints the parameters it was
handed, used by the two test scripts to observe what nginx actually sends upstream.
