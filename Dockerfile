#
# This image uses a php:8.4-cli base image and does not have any phpMyFAQ code with it.
# It's for testing only
#
# Stage 1: Composer dependencies
FROM composer:lts AS composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-interaction

# Stage 2: PHP with necessary extensions
FROM php:8.4-cli

# Install necessary system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    libldap2-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    git unzip \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions required by phpMyFAQ
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd intl ldap mysqli pdo_mysql zip opcache

# Install composer dependencies
COPY --from=composer /app/vendor /var/www/html/vendor

# Copy the phpMyFAQ source code
COPY . /var/www/html
WORKDIR /var/www/html

# Install PHPUnit (if not in composer dependencies already)
RUN ./vendor/bin/phpunit --version

# Run PHPUnit tests by default
CMD ["./vendor/bin/phpunit"]
