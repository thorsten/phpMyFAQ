#! /bin/sh

# Exit on error
set -e

#=== Set folder permissions ===
folders="content/core/config content/core/data content/core/logs content/user/attachments content/user/images"

mkdir -vp $folders

{
  chown -R www-data:www-data $folders
  chmod 775 $folders
}

##=== Check database vars ===
#=== DB host ===
if [ -z "$PMF_DB_HOST" -a ! -e "./content/core/config/database.php" ]; then
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

#=== Configure php.ini ===
{
  echo "# PHP settings:"
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

#=== Set recommended opcache settings ===
# see https://secure.php.net/manual/en/opcache.installation.php
{
  echo "# OPCache settings:"
  echo "opcache.enable=1"
  echo "; 0 means it will check on every request"
  echo "; 0 is irrelevant if opcache.validate_timestamps=0 which is desirable in production"
  echo "opcache.revalidate_freq=0"
  echo "opcache.validate_timestamps=1"
  echo "opcache.max_accelerated_files=10000"
  echo "opcache.memory_consumption=192"
  echo "opcache.max_wasted_percentage=10"
  echo "opcache.interned_strings_buffer=16"
  echo "opcache.fast_shutdown=0"
} | tee $PHP_INI_DIR/conf.d/opcache-recommended.ini

#=== Start php-fpm ===
exec docker-php-entrypoint php-fpm
