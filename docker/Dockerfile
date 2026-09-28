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