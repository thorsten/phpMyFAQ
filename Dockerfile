#
# This image uses a php:8.1-apache base image and do not have any phpMyFAQ code with it.
# It's for development only, it's meant to be run with docker-compose
#

#####################################
#=== Unique stage without payload ===
#####################################
FROM php:8.1-apache

#=== Install gd php dependencie ===
RUN set -x \
 && buildDeps="libpng-dev libjpeg-dev libfreetype6-dev" \
 && apt-get update && apt-get install -y ${buildDeps} --no-install-recommends \
 \
 && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
 && docker-php-ext-install gd \
 \
 && apt-get purge -y ${buildDeps} \
 && rm -rf /var/lib/apt/lists/*

#=== Install ldap PHP dependencies ===
RUN set -x \
 && buildDeps="libldap2-dev" \
 && apt-get update && apt-get install -y ${buildDeps} --no-install-recommends \
 \
 && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
 && docker-php-ext-install ldap \
 \
 && apt-get purge -y ${buildDeps} \
 && rm -rf /var/lib/apt/lists/*

#=== Install intl, opcache and zip PHP dependencies ===
RUN set -x \
 && buildDeps="libicu-dev zlib1g-dev libxml2-dev libzip-dev"  \
 && apt-get update && apt-get install -y ${buildDeps} --no-install-recommends \
 \
 && docker-php-ext-configure intl \
 && docker-php-ext-install intl \
 && docker-php-ext-install zip \
 && docker-php-ext-install opcache \
 \
 && apt-get purge -y ${buildDeps} \
 && rm -rf /var/lib/apt/lists/*

#=== Install mysqli php dependencies ===
RUN set -x \
 && docker-php-ext-install pdo pdo_mysql mysqli

#=== Install pgsql dependencies ===
RUN set -ex \
 && buildDeps="libpq-dev" \
 && apt-get update && apt-get install -y $buildDeps \
 \
 && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
 && docker-php-ext-install pdo pdo_pgsql pgsql \
 \
 && apt-get purge -y ${buildDeps} \
 && rm -rf /var/lib/apt/lists/*

#=== php default ===
ENV PMF_TIMEZONE="Europe/Berlin" \
    PMF_ENABLE_UPLOADS=On \
    PMF_MEMORY_LIMIT=2048M \
    PMF_DISABLE_HTACCESS="" \
    PHP_LOG_ERRORS=On \
    PHP_ERROR_REPORTING=E_ALL|E_STRICT \
    PHP_POST_MAX_SIZE=64M \
    PHP_UPLOAD_MAX_FILESIZE=64M

#=== Set custom entrypoint ===
COPY docker-entrypoint.sh /entrypoint
RUN chmod +x /entrypoint
ENTRYPOINT [ "/entrypoint" ]

#=== Re-Set CMD as we changed the default entrypoint ===
CMD [ "apache2-foreground" ]
