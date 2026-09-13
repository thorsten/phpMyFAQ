# syntax=docker/dockerfile:1.7
#
# Production image for phpMyFAQ.
#
# The payload matches a release archive: Composer production dependencies,
# built frontend assets, no development tooling. Pick the web server with
# --target:
#
#   apache      Apache 2.4 + mod_php          published as phpmyfaq/phpmyfaq:<version>
#   frankenphp  FrankenPHP (Caddy, HTTP/2+3)  published as phpmyfaq/phpmyfaq:<version>-frankenphp
#
#   docker build -f .docker/production/Dockerfile --target apache -t phpmyfaq/phpmyfaq:local .
#
# See docs/deployment.md and docker-compose.prod.yml for how to run it.

ARG PHP_VERSION=8.4
ARG NODE_VERSION=24
ARG FRANKENPHP_VERSION=1

# ---------------------------------------------------------------------------
# PHP dependencies (vendor dir is phpmyfaq/src/libs, see composer.json)
# ---------------------------------------------------------------------------
FROM composer:2 AS php-deps

WORKDIR /app
COPY composer.json composer.lock ./
# Platform requirements (PHP version, extensions) are satisfied by the runtime
# stages below, not by the Composer image, so they are ignored here.
RUN --mount=type=cache,target=/tmp/cache \
    composer install --no-dev --prefer-dist --no-progress --no-interaction \
        --no-scripts --no-autoloader --ignore-platform-reqs
COPY phpmyfaq/src/phpMyFAQ phpmyfaq/src/phpMyFAQ
# The pre-autoload-dump script strips unused AWS SDK services from the payload.
RUN composer dump-autoload --no-dev --optimize --ignore-platform-reqs

# ---------------------------------------------------------------------------
# Frontend assets
# ---------------------------------------------------------------------------
FROM node:${NODE_VERSION}-alpine AS frontend

ENV HUSKY=0
WORKDIR /app
RUN npm install -g pnpm@8.15.5
COPY package.json pnpm-lock.yaml ./
RUN --mount=type=cache,target=/root/.local/share/pnpm/store \
    pnpm install --frozen-lockfile
COPY vite.config.ts tsconfig.json ./
COPY phpmyfaq ./phpmyfaq
RUN pnpm build:prod \
    && find phpmyfaq/assets/public -name '*.map' -type f -delete

# ---------------------------------------------------------------------------
# Assemble the payload once for all runtime variants
# ---------------------------------------------------------------------------
FROM alpine:3.21 AS payload

WORKDIR /payload
COPY --from=frontend /app/phpmyfaq ./phpmyfaq
COPY --from=php-deps /app/phpmyfaq/src/libs ./phpmyfaq/src/libs
COPY bin/console ./bin/console
# Strip development leftovers (mirrors scripts/prepare-release-artifacts.sh)
# and any runtime state that a local checkout may contain. Never ship a
# database.php: the container is configured through the installer or the
# entrypoint.
RUN set -eu \
    && rm -rf phpmyfaq/src/libs/tecnickcom/tcpdf/examples \
              phpmyfaq/src/libs/tecnickcom/tcpdf/test \
              phpmyfaq/src/libs/tecnickcom/tc-lib-pdf-font/util \
              phpmyfaq/src/libs/tecnickcom/tc-lib-pdf-font/target \
              phpmyfaq/content/core/cache \
              phpmyfaq/content/core/config/database.php \
              phpmyfaq/content/core/config/ldap.php \
              phpmyfaq/content/core/config/elasticsearch.php \
              phpmyfaq/content/core/config/opensearch.php \
              phpmyfaq/content/core/config/azure.php \
              phpmyfaq/content/core/config/update-token.php \
              phpmyfaq/.env \
    && find phpmyfaq/content/core/config -name '*.bak.php' -delete \
    && find phpmyfaq/content/core/config -name '*.zip' -delete \
    && find phpmyfaq/content/core/data phpmyfaq/content/core/logs phpmyfaq/content/upgrades \
            -mindepth 1 -not -name '.gitkeep' -delete \
    && find . -name '.DS_Store' -delete \
    && chmod +x bin/console

# ---------------------------------------------------------------------------
# Apache + mod_php
# ---------------------------------------------------------------------------
FROM php:${PHP_VERSION}-apache AS apache

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions gd intl ldap mysqli pdo_mysql pdo_pgsql pgsql zip bz2 opcache redis \
    && a2enmod rewrite headers expires deflate \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=192'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=1'; \
        echo 'opcache.revalidate_freq=2'; \
    } > "$PHP_INI_DIR/conf.d/opcache-recommended.ini"

COPY .docker/production/apache.conf /etc/apache2/sites-available/000-default.conf
COPY .docker/production/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --from=payload /payload/phpmyfaq /var/www/html
COPY --from=payload /payload/bin/console /var/www/bin/console

# The Composer autoloader resolves the project root relative to the vendor
# dir, so the document root must also be reachable as /var/www/phpmyfaq.
RUN ln -s /var/www/html /var/www/phpmyfaq \
    && chmod +x /usr/local/bin/docker-entrypoint \
    && mkdir -p /var/www/html/cache \
    && chown -R www-data:www-data /var/www/html/cache /var/www/html/content

ENV APP_ENV=production \
    PMF_TIMEZONE=UTC \
    PMF_MEMORY_LIMIT=512M \
    PMF_ENABLE_UPLOADS=On \
    PHP_UPLOAD_MAX_FILESIZE=64M \
    PHP_POST_MAX_SIZE=64M \
    PHP_LOG_ERRORS=On \
    PHP_ERROR_REPORTING="E_ALL & ~E_DEPRECATED"

LABEL org.opencontainers.image.title="phpMyFAQ" \
      org.opencontainers.image.description="phpMyFAQ with Apache and mod_php" \
      org.opencontainers.image.url="https://www.phpmyfaq.de" \
      org.opencontainers.image.documentation="https://github.com/thorsten/phpMyFAQ/blob/main/docs/deployment.md" \
      org.opencontainers.image.source="https://github.com/thorsten/phpMyFAQ" \
      org.opencontainers.image.licenses="MPL-2.0"

WORKDIR /var/www/html
EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS -o /dev/null http://localhost/api/health || exit 1
ENTRYPOINT ["docker-entrypoint"]
CMD ["apache2-foreground"]

# ---------------------------------------------------------------------------
# FrankenPHP (Caddy with embedded PHP, HTTP/2 and HTTP/3, automatic HTTPS)
# ---------------------------------------------------------------------------
FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION} AS frankenphp

RUN install-php-extensions gd intl ldap mysqli pdo_mysql pdo_pgsql pgsql zip bz2 opcache redis \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=192'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=1'; \
        echo 'opcache.revalidate_freq=2'; \
    } > "$PHP_INI_DIR/conf.d/opcache-recommended.ini"

COPY .docker/production/Caddyfile /etc/frankenphp/Caddyfile
COPY .docker/production/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --from=payload /payload/phpmyfaq /var/www/html
COPY --from=payload /payload/bin/console /var/www/bin/console

RUN ln -s /var/www/html /var/www/phpmyfaq \
    && chmod +x /usr/local/bin/docker-entrypoint \
    && mkdir -p /var/www/html/cache \
    && chown -R www-data:www-data /var/www/html/cache /var/www/html/content

# SERVER_NAME=":80" serves plain HTTP (use behind a reverse proxy). Set it to
# a public host name to get automatic HTTPS with Let's Encrypt certificates.
ENV APP_ENV=production \
    SERVER_NAME=:80 \
    PMF_TIMEZONE=UTC \
    PMF_MEMORY_LIMIT=512M \
    PMF_ENABLE_UPLOADS=On \
    PHP_UPLOAD_MAX_FILESIZE=64M \
    PHP_POST_MAX_SIZE=64M \
    PHP_LOG_ERRORS=On \
    PHP_ERROR_REPORTING="E_ALL & ~E_DEPRECATED"

LABEL org.opencontainers.image.title="phpMyFAQ" \
      org.opencontainers.image.description="phpMyFAQ with FrankenPHP" \
      org.opencontainers.image.url="https://www.phpmyfaq.de" \
      org.opencontainers.image.documentation="https://github.com/thorsten/phpMyFAQ/blob/main/docs/deployment.md" \
      org.opencontainers.image.source="https://github.com/thorsten/phpMyFAQ" \
      org.opencontainers.image.licenses="MPL-2.0"

WORKDIR /var/www/html
EXPOSE 80 443 443/udp
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS -o /dev/null http://localhost/api/health || exit 1
ENTRYPOINT ["docker-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
