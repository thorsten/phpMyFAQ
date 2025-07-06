#
# This image uses a dunglas/frankenphp base image and does not have any phpMyFAQ code with it.
# It's for development only, it's meant to be run with docker-compose
#

#####################################
#=== Unique stage without payload ===
#####################################
FROM dunglas/frankenphp:latest

#=== Install system dependencies ===
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    libldap2-dev \
    libbz2-dev \
    libsodium-dev \
    --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

#=== Install PHP extensions ===
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        intl \
        ldap \
        mysqli \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        pgsql \
        zip \
        opcache \
        bz2 \
        sodium

#=== Install xdebug for development ===
# RUN pecl install xdebug \
#     && docker-php-ext-enable xdebug

#=== Environment variables ===
ENV PMF_TIMEZONE="Europe/Berlin" \
    PMF_ENABLE_UPLOADS=On \
    PMF_MEMORY_LIMIT=2048M \
    PMF_DISABLE_HTACCESS="" \
    PHP_LOG_ERRORS=On \
    PHP_ERROR_REPORTING=E_ALL \
    PHP_POST_MAX_SIZE=64M \
    PHP_UPLOAD_MAX_FILESIZE=64M \
    FRANKENPHP_CONFIG="worker /var/www/html/frankenphp-worker.php"

#=== Copy configuration files ===
COPY ./.docker/frankenphp/Caddyfile /etc/caddy/Caddyfile

#=== Set custom entrypoint ===
COPY ./.docker/frankenphp/docker-entrypoint.sh /entrypoint
RUN chmod +x /entrypoint
ENTRYPOINT [ "/entrypoint" ]

#=== Re-Set CMD as we changed the default entrypoint ===
CMD [ "frankenphp", "run", "--config", "/etc/caddy/Caddyfile" ]