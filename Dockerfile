FROM php:7.1-apache

MAINTAINER PhpMyFAQ Team <adrien.estanove@gmail.fr>

#=== Change UID and GID of www-data user to match host privileges ===
ARG APACHE_USER_UID=1000
ARG APACHE_USER_GID=1000
RUN set -x \
  && . "$APACHE_ENVVARS" \
  && usermod -u "$APACHE_USER_UID" "$APACHE_RUN_USER" \
  && groupmod -g "$APACHE_USER_GID" "$APACHE_RUN_GROUP"

#=== Install gd php dependencie ===
RUN set -x \
  && buildDeps="libpng12-dev libjpeg62-turbo-dev libfreetype6-dev" \
  && apt-get update && apt-get install -y ${buildDeps} --no-install-recommends \
  \
  && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
  && docker-php-ext-install gd \
  \
  && apt-get purge -y ${buildDeps} \
  && rm -rf /var/lib/apt/lists/*

#=== Install ldap php dependencie ===
RUN set -x \
  && buildDeps="libldap2-dev" \
  && apt-get update && apt-get install -y ${buildDeps} --no-install-recommends \
  \
  && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
  && docker-php-ext-install ldap \
  \
  && apt-get purge -y ${buildDeps} \
  && rm -rf /var/lib/apt/lists/*

#=== Install zip php dependencie ===
RUN set -x \
  && buildDeps="zlib1g-dev" \
  && apt-get update && apt-get install -y ${buildDeps} --no-install-recommends \
  \
  && docker-php-ext-install zip \
  \
  && apt-get purge -y ${buildDeps} \
  && rm -rf /var/lib/apt/lists/*

#=== Install intl php dependencie ===
RUN set -x \
  && buildDeps="libicu-dev" \
  && apt-get update && apt-get install -y ${buildDeps} --no-install-recommends \
  \
  && docker-php-ext-configure intl \
  && docker-php-ext-install intl \
  && docker-php-ext-enable intl \
  \
  && apt-get purge -y ${buildDeps} \
  && rm -rf /var/lib/apt/lists/*

#=== Install mcrypt php dependencie ===
RUN set -x \
  && buildDeps="libmcrypt-dev" \
  && apt-get update && apt-get install -y ${buildDeps} --no-install-recommends \
  \
  && docker-php-ext-install mcrypt \
  \
  && apt-get purge -y ${buildDeps} \
  && rm -rf /var/lib/apt/lists/*

#=== Install soap php dependencie ===
RUN set -x \
  && buildDeps="libxml2-dev" \
  && apt-get update && apt-get install -y ${buildDeps} --no-install-recommends \
  \
  && docker-php-ext-install soap \
  \
  && apt-get purge -y ${buildDeps} \
  && rm -rf /var/lib/apt/lists/*

#=== Install pdo_mysql php dependencie ===
RUN set -x \
  && docker-php-ext-install pdo_mysql

#=== Set custom entrypoint ===
COPY docker-entrypoint.sh /entrypoint
ENTRYPOINT [ "/entrypoint" ]

#=== Re-Set CMD as we changed the default entrypoint ===
CMD [ "apache2-foreground" ]
