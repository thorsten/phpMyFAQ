#
# This image uses a php:8.4-cli base image and does not have any phpMyFAQ code with it.
# It's for testing only
#

# Stage 1: Composer dependencies with PHP extensions
FROM php:8.5.2-cli AS composer

RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libicu-dev libpq-dev libldap2-dev libbz2-dev libsodium-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd intl ldap mysqli pdo_mysql zip opcache bz2 sodium

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./

# Explicit Composer error visibility
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --verbose

# Stage 2: PHP runtime with same extensions
FROM php:8.5.2-cli

# Install necessary system dependencies again (clean stage)
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libicu-dev libpq-dev libldap2-dev libbz2-dev libsodium-dev \
    && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions again
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd intl ldap mysqli pdo_mysql zip opcache bz2 sodium

# Copy vendor directory from composer stage
COPY --from=composer /app/phpmyfaq/src/libs /var/www/html/phpmyfaq/src/libs

# Copy your app code
COPY . /var/www/html
WORKDIR /var/www/html

# PHPUnit test verification
RUN ./phpmyfaq/src/libs/bin/phpunit --version

# Run PHPUnit tests by default
CMD ["./phpmyfaq/src/libs/bin/phpunit"]
