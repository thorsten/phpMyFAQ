#! /bin/sh

# Exit on error
set -e

#=== Set folder permissions ===
folders="attachments data images config"

mkdir -vp $folders

{
  if [ -f "$APACHE_ENVVARS" ]; then
    . "$APACHE_ENVVARS"
    chown -R "$APACHE_RUN_USER:$APACHE_RUN_GROUP" $folders
  else
    chown -R www-data:www-data $folders
  fi
  chmod 775 $folders
}

##=== Check database vars ===
#=== DB host ===
if [ -z "$PMF_DB_HOST" -a ! -e "./config/database.php" ]; then
  echo >&2 'WARN: missing PMF_DB_HOST environment variable'
  echo >&2 '  Did you forget to --link some_mysql_container:db ?'
else
  #=== DB user and pass ===
  : ${PMF_DB_USER:=root}
  if [ "$PMF_DB_USER" = 'root' ]; then
    : ${PMF_DB_PASS:=$DB_ENV_MYSQL_ROOT_PASSWORD}
  fi

  if [ -z "$PMF_DB_PASS" ]; then
    echo >&2 'ERROR: missing required PMF_DB_PASS environment variable'
    echo >&2 '  Did you forget to -e PMF_DB_PASS=... ?'
    echo >&2
    echo >&2 '  (Also of interest might be PMF_DB_USER and PMF_DB_NAME.)'
    exit 1
  #=== Setup database if needed ===
  elif [ 0 -eq 1 ]; then # TODO : Add something like: php setup/maintenance.php --vars...
    {
      echo "<?php"
      echo "\$DB['server'] = '$PMF_DB_HOST';"
      echo "\$DB['user'] = '$PMF_DB_USER';"
      echo "\$DB['password'] = '$PMF_DB_PASS';"
      echo "\$DB['db'] = '${PMF_DB_NAME:-phpmyfaq}';"
      echo "\$DB['prefix'] = '${PMF_DB_PREFIX}';"
      echo "\$DB['type'] = '${PMF_DB_TYPE:-mysqli}';"
    } | tee ./config/database.php
  fi
fi

if [ -f "$APACHE_ENVVARS" ]; then
  #=== Enable htaccess for search engine optimisations ===
  if [ "x${DISABLE_HTACCESS}" = "x" ]; then
      a2enmod rewrite headers
      sed -ri .htaccess \
        -e "s~RewriteBase /phpmyfaq/~RewriteBase /~"
      # Enabling permissions override
      sed -ri ${APACHE_CONFDIR}/conf-available/*.conf \
        -e "s~(.*AllowOverride).*~\1 All~g"
  else
      rm .htaccess
      # Disabling permissions override
      sed -ri ${APACHE_CONFDIR}/conf-available/*.conf \
        -e "s~(.*AllowOverride).*~\1 none~g"
  fi
fi

#=== Configure php ===
{
  echo "# php settings:"
  echo "register_globals = Off"
  echo "safe_mode = Off"
  echo "log_errors = $PHP_LOG_ERRORS"
  echo "error_reporting = $PHP_ERROR_REPORTING"
  echo "date.timezone = $PMF_TIMEZONE"
  echo "memory_limit = $PMF_MEMORY_LIMIT"
  echo "file_upload = $PMF_ENABLE_UPLOADS"
  echo "post_max_size = $PHP_POST_MAX_SIZE"
  echo "upload_max_filesize = $PHP_UPLOAD_MAX_FILESIZE"
} | tee $PHP_INI_DIR/conf.d/php.ini

#=== Set recommanded opcache settings ===
# see https://secure.php.net/manual/en/opcache.installation.php
{
  echo "opcache.memory_consumption=128"
  echo "opcache.interned_strings_buffer=8"
  echo "opcache.max_accelerated_files=4000"
  echo "opcache.revalidate_freq=2"
  echo "opcache.fast_shutdown=1"
  echo "opcache.enable_cli=1"
} | tee $PHP_INI_DIR/conf.d/opcache-recommended.ini

docker-php-entrypoint "$@"
