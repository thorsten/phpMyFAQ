#! /bin/bash

# Exit on error
set -e

#=== Set folder permissions ===
folders="attachments data images config"

mkdir -vp $folders
chmod 775 $folders

. "$APACHE_ENVVARS"
chown "$APACHE_RUN_USER:$APACHE_RUN_GROUP" $folders

#=== Enable htaccess for search engine optimisations ===
if [ "x${DISABLE_HTACCESS}" = "x" ]; then
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

#=== Configure php ===
if [ "x${TIMEZONE}" = "x" ]; then
    TIMEZONE="Europe/Berlin"
fi
echo "date.timezone = ${TIMEZONE}" > "$PHP_INI_DIR/conf.d/timezone.ini"

if [ "x${MEMORY_LIMIT}" = "x" ]; then
    MEMORY_LIMIT="64M"
fi
echo "memory_limit = $MEMORY_LIMIT" > "$PHP_INI_DIR/conf.d/memory.ini"

docker-php-entrypoint "$@"
