# syntax=docker/dockerfile:1
FROM php:8.2-apache

# System deps + PHP extensions + PECL build deps
RUN apt-get update && apt-get install -y --no-install-recommends \
    $PHPIZE_DEPS \
    git unzip libzip-dev pkg-config libssl-dev libsasl2-dev \
  && docker-php-ext-install mysqli pdo pdo_mysql zip \
  && pecl install mongodb \
  && docker-php-ext-enable mongodb \
  && a2enmod rewrite \
  && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# App code
WORKDIR /var/www/app
COPY . /var/www/app

# Set Apache DocumentRoot to /var/www/app/htdocs
RUN sed -ri 's!/var/www/html!/var/www/app/htdocs!g' /etc/apache2/sites-available/000-default.conf \
 && sed -ri 's!/var/www/!/var/www/app/htdocs!g' /etc/apache2/apache2.conf || true

# Install PHP deps
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress

EXPOSE 80