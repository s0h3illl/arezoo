#!/usr/bin/env bash
#
# Bring the application container to the point where php-fpm can serve requests.
#
# Dependencies are installed into the bind-mounted working tree rather than baked
# into the image. That is the point: no lockfile is ever copied in, so what the
# containers run is always what composer.lock and bun.lock currently say, and a
# `git pull` that changes either is picked up on the next start instead of needing
# a rebuild. The cost is a first start that takes a minute; the benefit is that the
# image never disagrees with the branch.

set -euo pipefail

cd /var/www/html

if [ ! -f vendor/autoload.php ]; then
    echo '==> composer install'
    composer install --no-interaction --prefer-dist
fi

# Compose supplies every environment-specific value (database, mail, app url) as
# real environment variables, which the dotenv loader will not overwrite. The .env
# file is still created so the pieces Compose does not set — the app key, the
# session and cache stores — have somewhere to live.
if [ ! -f .env ]; then
    echo '==> .env from .env.example'
    cp .env.example .env
fi

if ! grep -qE '^APP_KEY=base64:.+' .env; then
    echo '==> php artisan key:generate'
    php artisan key:generate --force
fi

# A cached config would freeze the environment variables above into a file written
# by whichever container started first, which is exactly the bug this stack is prone
# to when a value changes between `docker compose up` runs.
php artisan config:clear >/dev/null

# `public/storage` is what serves avatars and wish covers. It is a relative symlink
# into the bind mount, so it resolves identically here and in the nginx container.
if [ ! -e public/storage ]; then
    echo '==> php artisan storage:link'
    php artisan storage:link
fi

# Generated on both sides on purpose: the Vite plugin runs this itself on every
# boot, and doing it here means the files are already there for anyone who builds
# assets without the asset container running.
php artisan wayfinder:generate --with-form

echo '==> php artisan migrate --force'
php artisan migrate --force

exec "$@"
