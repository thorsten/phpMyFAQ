#! /bin/bash

# Exit on error
set -e

#=== Set folder permissions ===
folders="attachments data images config"

mkdir $folders
chmod 775 $folders

. "$APACHE_ENVVARS"
chown "$APACHE_RUN_USER:$APACHE_RUN_GROUP" $folders

#=== Enable htaccess for search engine optimisations ===
if [ "x${DISABLE_HTACCESS}" -eq "x" ]; then
    a2enmod rewrite headers
    [ ! -f /.htaccess ] && cp _.htaccess .htaccess
    sed -ri .htaccess \
      -e "s~RewriteBase /phpmyfaq/~RewriteBase /~"
    # Enabling permissions override
    sed -ri ${APACHE_CONFDIR}/conf-available/*.conf \
      -e "s~(.*AllowOverride).*~\1 All~g"
else
    rm .htaccess
    # Disabling permissions override
    sed -ri "${APACHE_CONFDIR}/conf-available/*.conf" \
      -e "s~(.*AllowOverride).*~\1 none~g"
fi

# Set utf8 locale
if [ "x${CARACTER_SET}" -ne "x" ]; then
    sed -ri /etc/locale.gen \
      -e "s/#?\s*(${CARACTER_SET}.UTF-8 UTF-8)/\1/"
    dpkg-reconfigure --frontend=noninteractive locales
fi

#=== Configure php ===
if [ "x${TIMEZONE}" -eq "x" ]; then
    TIMEZONE="Europe/Berlin"
fi
{
    echo "date.timezone = ${TIMEZONE}"
    echo "register_globals = off"
    echo "safe_mode = off"
    echo "memory_limit = 64M"
    echo "file_upload = on"
} | tee "$PHP_INI_DIR/php.ini"

docker-php-entrypoint "$@"
