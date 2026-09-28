# syntax=docker/dockerfile:1

# One base, two targets.
#
# The asset container still needs PHP, which is the whole reason this is a
# multi-target file rather than two images: @laravel/vite-plugin-wayfinder shells
# out to `php artisan wayfinder:generate` every time Vite boots, so a node-only
# image fails on its first start with a bare `php: not found`. Building both
# targets from one base is what guarantees the container generating Wayfinder's
# TypeScript runs the same PHP the application runs.
#
# Both bases are Debian 13, so the PHP tree is copied between them without a glibc
# mismatch. The tag is the patch-level `8.5-fpm`, not `8.5-fpm-bookworm`, because
# that is what tracks Debian 13.
#
# The `dev` user is uid 1000 on purpose. The working tree is a bind mount, so
# whatever user the containers run as owns every file they write back into it —
# uploads, generated Wayfinder files, the .env key. Running as root would leave all
# of that root-owned in a directory the host user then cannot edit.

FROM php:8.5-fpm-trixie AS php-base

ARG DEV_UID=1000
ARG DEV_GID=1000

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        git \
        libicu-dev \
        libonig-dev \
        libsqlite3-dev \
        libxml2-dev \
        libzip-dev \
        unzip; \
    docker-php-ext-configure intl; \
    docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        mbstring \
        pcntl \
        pdo_mysql \
        pdo_sqlite \
        sockets \
        xml \
        zip; \
    rm -rf /var/lib/apt/lists/*; \
    groupadd --gid "${DEV_GID}" dev; \
    useradd --uid "${DEV_UID}" --gid "${DEV_GID}" --create-home --shell /bin/bash dev

COPY --from=composer:2.10 /usr/bin/composer /usr/bin/composer

# Created empty and owned by `dev` so the named volumes mounted over them inherit
# that ownership when Docker initialises them. Without this the volumes come out
# root-owned and nothing running as `dev` can write to them.
RUN set -eux; \
    mkdir -p /var/www/html/vendor /var/www/html/node_modules /home/dev/.composer; \
    chown -R dev:dev /var/www/html /home/dev

ENV HOME=/home/dev \
    COMPOSER_HOME=/home/dev/.composer \
    COMPOSER_CACHE_DIR=/home/dev/.composer/cache

WORKDIR /var/www/html

USER dev


FROM php-base AS app

COPY --chown=dev:dev docker/app/php.ini /usr/local/etc/php/conf.d/zz-arezoo.ini
COPY --chown=dev:dev docker/app/entrypoint.sh /usr/local/bin/arezoo-entrypoint
RUN chmod +x /usr/local/bin/arezoo-entrypoint

ENTRYPOINT ["arezoo-entrypoint"]
CMD ["php-fpm"]


FROM php-base AS vite

# The base stage ends as `dev`, so the image-building steps below need root back in
# order to place a binary in a root-owned /usr/local/bin. Only the run-time user
# matters, and that is restored at the end.
USER root

# Bun goes into the PHP image rather than PHP into Bun's, and the direction matters.
# The tempting version — start from oven/bun and copy /usr/local across — produces an
# image where the PHP CLI cannot start: it links against libreadline, libicu and the
# rest of the PHP base's system libraries, none of which a node-only base ships, and
# the failure surfaces as `php: error while loading shared libraries` from inside a
# Vite plugin. Bun is a single binary that needs nothing beyond glibc, so adding it to
# an image that already has PHP is both easier and the only direction that works.
COPY --from=oven/bun:1-debian /usr/local/bin/bun /usr/local/bin/bun

RUN set -eux; \
    ln -sf /usr/local/bin/bun /usr/local/bin/bunx; \
    mkdir -p /var/www/html/node_modules; \
    chown -R dev:dev /var/www/html

COPY --chown=dev:dev docker/vite/entrypoint.sh /usr/local/bin/arezoo-vite
RUN chmod +x /usr/local/bin/arezoo-vite

USER dev

ENTRYPOINT ["arezoo-vite"]
CMD ["bun", "run", "dev"]


# ---------------------------------------------------------------------------------
# Production.
#
# Two targets, because the front end has to be built somewhere and that somewhere
# is not the image that serves it. A production container has no Node, no Bun and
# no Vite: it serves the compiled files out of public/build and nothing else. So
# the build happens in a stage, and only its output — the compiled assets — is
# copied forward.
#
# The result is a single image containing the application and its compiled assets,
# deployable by `docker compose up` with no network access, no Node, and no build
# step on the host.
# ---------------------------------------------------------------------------------

FROM php-base AS node-deps

USER root
COPY --from=oven/bun:1-debian /usr/local/bin/bun /usr/local/bin/bun
RUN ln -sf /usr/local/bin/bun /usr/local/bin/bunx
USER dev

# Dependencies are installed in their own stage so that the layer is reused
# whenever only application source changes. Copying package.json and the lockfile
# alone is what makes that possible: a change to any .php or .vue file then
# rebuilds nothing but the build stage below.
COPY --chown=dev:dev package.json bun.lock ./
RUN bun install --frozen-lockfile



FROM php-base AS composer-deps-full

# The complete dependency set, dev included, used only by the asset stage — which
# runs `php artisan wayfinder:generate` and therefore needs a working application to
# boot. It never reaches a shipped image; the production stage below installs its own
# tree with --no-dev.
COPY --chown=dev:dev composer.json composer.lock ./
RUN composer install \
        --no-scripts \
        --no-interaction \
        --no-autoloader \
        --prefer-dist \
    && composer dump-autoload --no-scripts


FROM php-base AS composer-deps

# The tree that ships: runtime dependencies only, because Pint, PHPStan and the rest
# are what a contributor needs and what a production server does not, and they are
# several times the size.
#
# --no-scripts because the scripts are `artisan package:discover`, which wants an
# .env and a writable cache — neither of which exists while an image is being built.
# Discovery runs at container start, where an .env does exist.
COPY --chown=dev:dev composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --no-autoloader \
        --prefer-dist


FROM node-deps AS assets

# The application's dependencies, and the reason this stage needs them at all:
# `bun run build` loads the Wayfinder Vite plugin, which shells out to
# `php artisan wayfinder:generate` to write the route functions the front end
# imports. That is a PHP process booting a Laravel application, so it needs
# vendor/ — and it is the reason the development stack cannot be a node-only
# image either.
#
# The full set rather than the --no-dev one below: the build runs a real artisan
# command, and artisan resolves against the whole autoloader. A --no-dev tree would
# be slightly smaller and would fail the moment a package's service provider reached
# for a dev-only class.
COPY --from=composer-deps-full --chown=dev:dev /var/www/html/vendor /var/www/html/vendor
COPY --chown=dev:dev . .

# Artisan needs an APP_KEY to boot, even to print a route list. This is a build-time
# placeholder for a value that is discarded with this stage: the shipped image takes
# its key from the environment, and never from anything written here. Passed as a
# build argument rather than ENV so that a scanner reading the finished image does
# not find a key-shaped string in it.
ARG BUILD_APP_KEY=base64:buildtimeplaceholderbuildtimeplaceholderbuildtime=
ENV APP_KEY=${BUILD_APP_KEY}
ENV APP_ENV=production
ENV DB_CONNECTION=sqlite
ENV DB_DATABASE=:memory:

# Laravel's writable directories, created here because .dockerignore excludes them
# from the build context (they hold caches, which should not be shipped) — and
# because the artisan call below cannot run without them. The view compiler in
# particular refuses to start when its cache path is absent, which is a
# "Please provide a valid cache path" with no hint that the directory is the problem.
RUN mkdir -p \
        bootstrap/cache \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs

# Two things happen here that are worth naming, because both fail quietly elsewhere.
#
# `bun run build` is what generates Wayfinder's TypeScript. Those files are
# gitignored, so a build that skipped this step yields an image whose front end
# cannot resolve its own routes — surfacing as an import error in a browser, with
# nothing in the server logs to explain it.
#
# It also downloads the fonts. `bunny()` self-hosts them, which is the right default
# (no third-party request from a visitor's browser, no dependency on a font CDN
# being reachable), and it means this stage needs network access even though the
# image it feeds does not.
#
# No --ssr bundle is built. Inertia's SSR is off by default (see config/inertia.php);
# enabling it needs a separate process serving the bundle, and an image that merely
# contained the file would have the gateway POST to a server nothing is listening to.
RUN bun run build


FROM php-base AS production

COPY --chown=dev:dev docker/app/php.ini /usr/local/etc/php/conf.d/zz-arezoo.ini

# The filename is load-bearing, not a label. PHP applies conf.d files in sorted
# order and the last one to mention a setting wins, so an override called
# "zz-arezoo-production.ini" sorts BEFORE "zz-arezoo.ini" — a dot is 0x2E and a
# hyphen is 0x2D — and would silently lose every setting it tried to change. This
# name sorts after the file it overrides, which is the only property that matters.
COPY --chown=dev:dev docker/app/zz-production.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY --chown=dev:dev docker/app/entrypoint-production.sh /usr/local/bin/arezoo-entrypoint-production
RUN chmod +x /usr/local/bin/arezoo-entrypoint-production

# The writable directories, created here for the same reason they are created in the
# asset stage: .dockerignore excludes them from the build context, because they hold
# caches that should not be baked into a release. Their absence is not a warning, it is
# a failed boot —
#
#   View path not found.
#
# from view:cache, which the entrypoint runs. storage/app/public is included on
# purpose: the production compose mounts a named volume over storage/app, and Docker
# seeds a fresh named volume from the image's contents at that path, so a first deploy
# gets a usable public disk rather than an empty one that storage:link cannot target.
RUN mkdir -p \
        bootstrap/cache \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs

# The application source, and nothing else. .dockerignore excludes vendor/,
# node_modules/ and public/build from the build context, so they cannot arrive by
# this route — which is what makes the two COPY --from lines below necessary rather
# than merely tidy, and is how a --no-dev dependency tree gets in at all.
COPY --chown=dev:dev . .
COPY --from=composer-deps --chown=dev:dev /var/www/html/vendor /var/www/html/vendor

# The autoloader is dumped HERE, with the application source in place, and not in the
# stage that installed the dependencies.
#
# The classmap is built by scanning the autoload paths, and in that stage those paths
# point at an empty directory — only composer.json and composer.lock have been copied.
# A classmap generated there knows every package class and none of the application's
# own, so the first thing to touch an application class fails with
#
#   Invalid route action: [App\Http\Controllers\HomeController].
#
# Dumping after the source copy fixes it, and --classmap-authoritative is worth having
# for its actual benefit: Composer's PSR-4 fallback is a filesystem probe per class per
# process, and an authoritative classmap turns that into one array lookup.
RUN composer dump-autoload --no-dev --classmap-authoritative --no-scripts

# The compiled output, last, over the top of the source. The order is load-bearing:
# the build writes into the same tree, and copying these after the source is what
# makes those writes survive rather than being overwritten.
#
# node_modules/ is deliberately not copied. Nothing at runtime executes Node, and it
# is the largest directory in the build stage by a wide margin.
COPY --from=assets --chown=dev:dev /var/www/html/public/build /var/www/html/public/build
COPY --from=assets --chown=dev:dev /var/www/html/resources/js/routes /var/www/html/resources/js/routes
COPY --from=assets --chown=dev:dev /var/www/html/resources/js/actions /var/www/html/resources/js/actions

# The discovery cache is deleted rather than shipped.
#
# bootstrap/cache/packages.php and services.php are written by
# `artisan package:discover`, and the asset stage runs that — with dev dependencies
# present, because it needs them to execute artisan. So the cache it produced names
# laravel/boost's service provider, and this image has just removed boost with
# --no-dev. Laravel reads that file at boot, finds a class that is not there, and
# the container dies before php-fpm ever starts:
#
#   Class "Laravel\Boost\BoostServiceProvider" not found
#
# The fix is to ship no cache at all rather than a correct one. The entrypoint
# regenerates it on start, when the .env and the final dependency tree both exist —
# which is the only point at which it can be correct.
RUN rm -f bootstrap/cache/*.php

ENTRYPOINT ["arezoo-entrypoint-production"]
CMD ["php-fpm"]


FROM nginx:1.27-alpine AS production-web

# The layer Caddy proxies to, and the only one that speaks HTTP to the browser.
#
# The compiled front end is baked in rather than bind-mounted, because in
# production the image *is* the release: a mount of the host's public/build would
# quietly reintroduce the coupling to a working tree that the rest of this file
# exists to remove, and a deploy would then be able to change assets without
# changing the image.
#
# The whole of public/, not just build/. index.php is the front controller and
# belongs in the document root; a container holding the compiled assets but not
# the file that runs them serves every asset correctly and 403s on the homepage
# with "directory index of /var/www/html/public/ is forbidden".
# The default config that ships in the base image has to go, not be overwritten.
# It is a server block listening on port 80 marked default_server, and so is the
# one this image supplies; two of those on one port is a refusal to start with
# "a duplicate default server" rather than anything that names the conflict.
RUN rm -f /etc/nginx/conf.d/default.conf

# Into templates/, not conf.d/, because that is the directory the image's
# entrypoint watches: it runs envsubst over each .template and writes the result
# into conf.d/ before nginx starts. Which variables it substitutes is restricted
# by NGINX_ENVSUBST_FILTER in compose.production.yaml.
COPY docker/nginx/production.conf.template /etc/nginx/templates/production.conf.template
COPY --from=assets /var/www/html/public /var/www/html/public

# The uploads this container serves are the app's uploads, so the symlink has to
# point at the volume mounted over this path — the same volume the app writes to.
# A relative symlink would be shorter, but the two containers agree on this
# absolute path and nothing else.
RUN mkdir -p /var/www/html/storage/app/public \
    && ln -s /var/www/html/storage/app/public /var/www/html/public/storage
