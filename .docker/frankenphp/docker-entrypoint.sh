#!/bin/bash
set -e

# Function to log messages
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

log "Starting FrankenPHP for phpMyFAQ..."

# Set up PHP configuration
log "Configuring PHP..."

# Set timezone
if [ -n "${PMF_TIMEZONE}" ]; then
    log "Setting timezone to: ${PMF_TIMEZONE}"
    echo "date.timezone = ${PMF_TIMEZONE}" > /usr/local/etc/php/conf.d/timezone.ini
fi

# Set memory limit
if [ -n "${PMF_MEMORY_LIMIT}" ]; then
    log "Setting memory limit to: ${PMF_MEMORY_LIMIT}"
    echo "memory_limit = ${PMF_MEMORY_LIMIT}" > /usr/local/etc/php/conf.d/memory.ini
fi

# Set upload settings
if [ -n "${PHP_UPLOAD_MAX_FILESIZE}" ]; then
    log "Setting upload_max_filesize to: ${PHP_UPLOAD_MAX_FILESIZE}"
    echo "upload_max_filesize = ${PHP_UPLOAD_MAX_FILESIZE}" > /usr/local/etc/php/conf.d/upload.ini
fi

if [ -n "${PHP_POST_MAX_SIZE}" ]; then
    log "Setting post_max_size to: ${PHP_POST_MAX_SIZE}"
    echo "post_max_size = ${PHP_POST_MAX_SIZE}" >> /usr/local/etc/php/conf.d/upload.ini
fi

# Set error reporting
if [ -n "${PHP_ERROR_REPORTING}" ]; then
    log "Setting error_reporting to: ${PHP_ERROR_REPORTING}"
    echo "error_reporting = ${PHP_ERROR_REPORTING}" > /usr/local/etc/php/conf.d/error.ini
fi

if [ -n "${PHP_LOG_ERRORS}" ]; then
    log "Setting log_errors to: ${PHP_LOG_ERRORS}"
    echo "log_errors = ${PHP_LOG_ERRORS}" >> /usr/local/etc/php/conf.d/error.ini
fi

# Set uploads configuration
if [ -n "${PMF_ENABLE_UPLOADS}" ]; then
    log "Setting file_uploads to: ${PMF_ENABLE_UPLOADS}"
    echo "file_uploads = ${PMF_ENABLE_UPLOADS}" >> /usr/local/etc/php/conf.d/upload.ini
fi

# Set opcache configuration for better performance
log "Configuring OPcache..."
cat > /usr/local/etc/php/conf.d/opcache.ini << EOF
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
opcache.save_comments=1
opcache.preload_user=www-data
EOF

# Create necessary directories
mkdir -p /var/www/html/content/core/config
mkdir -p /var/www/html/content/core/data
mkdir -p /var/www/html/content/core/logs
mkdir -p /var/www/html/content/user/attachments
mkdir -p /var/www/html/content/user/images

# Set permissions
chown -R www-data:www-data /var/www/html/content
chmod -R 755 /var/www/html/content

log "FrankenPHP configuration complete. Starting server..."

# Execute the command
exec "$@"