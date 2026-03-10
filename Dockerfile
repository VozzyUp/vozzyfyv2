FROM php:8.3-cli-alpine

RUN apk add --no-cache \
    git unzip libzip-dev libpng-dev oniguruma-dev \
    mysql-client icu-dev libxml2-dev $PHPIZE_DEPS

RUN pecl install redis \
    && docker-php-ext-enable redis

RUN docker-php-ext-install pdo_mysql zip exif intl opcache pcntl bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction || true

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0"]
