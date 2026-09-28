#!/usr/bin/env bash
#
# Bring the asset container to the point where `bun run dev` can start.
#
# The one thing worth explaining is the wait below. The Vite plugin shells out to
# `php artisan wayfinder:generate` during startup, and artisan needs vendor/ to
# exist. vendor/ is a shared volume that the application container populates, so
# without this wait the asset container loses a race it always loses — it has no
# database to connect to and no migration to wait for, so it starts first.

set -euo pipefail

cd /var/www/html

until [ -f vendor/autoload.php ]; do
    echo '==> waiting for vendor/ (the app container installs it)'
    sleep 2
done

if [ ! -d node_modules/.bin ]; then
    echo '==> bun install'
    bun install --frozen-lockfile
fi

exec "$@"
