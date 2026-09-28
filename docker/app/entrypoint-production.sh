#!/bin/sh
#
# The production counterpart of docker/app/entrypoint.sh, and deliberately much
# smaller.
#
# The development entrypoint runs `migrate` on every start. That is convenient
# locally and wrong in production, for two reasons. A deploy that changes the
# schema would migrate the database as a side effect of restarting a container —
# so a rollback would want to un-migrate a database it has already left, and
# there is no such thing as an atomic rollback across a schema change and the
# code that assumed it. And two replicas starting at once would race.
#
# So migrations here are a command a human runs, deliberately, after reading the
# deploy notes. What this script does instead is refuse to serve traffic until the
# environment it was handed is one that could work: an application key present,
# storage writable, dependencies installed, and the front-end build in place.
set -eu

cd /var/www/html

# A missing APP_KEY is not a warning. Encryption, signed URLs and the session
# cookie all depend on it, and Laravel's error for a missing one appears only
# when something first needs it — which for a session cookie is a user's login.
#
# This is not checked when the command is one-shot rather than a server. There is
# an unavoidable ordering problem: the key has to be generated inside the image
# that will use it, and the only way to run anything in that image is through this
# entrypoint, which is the thing refusing to start. So a one-shot command runs
# without the check, and the operator either supplies the key anyway (this is how
# `key:generate --show` is supposed to be used) or is generating it.
if [ -z "${APP_KEY:-}" ] && [ "${#}" -le 1 ]; then
    echo "arezoo: APP_KEY is not set." >&2
    echo "  Generate one with: docker compose --env-file .env.production" >&2
    echo "    -f compose.production.yaml run --rm app php artisan key:generate --show" >&2
    exit 1
fi

# The application's own paths have to be writable or uploads and logs fail. The
# public disk is a mounted volume in production, so its ownership is whatever the
# volume was initialised with, not what this image says.
for path in storage bootstrap/cache; do
    if [ ! -w "$path" ]; then
        echo "arezoo: $path is not writable by uid $(id -u)." >&2
        echo "  It is likely a volume that was initialised by root." >&2
        exit 1
    fi
done

# Wayfinder's generated TypeScript is gitignored, so it is produced by the image
# build. Its absence means the build step did not run, and the front end will
# fail to resolve on its first import rather than at boot.
if [ ! -f public/build/manifest.json ]; then
    echo "arezoo: public/build/manifest.json is missing." >&2
    echo "  The image must be built with the production target, which runs the front-end build:" >&2
    echo "    docker compose -f compose.production.yaml build app" >&2
    exit 1
fi

# Config, routes and views are compiled once rather than parsed on every request.
# This runs before the cache is populated, since a stale cached config is worse
# than none — and then never again, so a config change needs a restart.
php artisan config:cache --quiet
php artisan route:cache --quiet
php artisan view:cache --quiet

# The symlink the application serves uploads through. Cheap, and it is the kind of
# thing that is quietly missing in a hand-built image.
php artisan storage:link --force --quiet 2>/dev/null || true

echo "arezoo: ready (migrations are NOT run here — run 'php artisan migrate --force' by hand)."
exec "$@"
