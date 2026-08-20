FROM php:8.3-fpm-alpine

# Extensoes exigidas pelo Laravel e pelo PostgreSQL
RUN apk add --no-cache postgresql-dev libzip-dev icu-dev oniguruma-dev git \
    && docker-php-ext-install pdo pdo_pgsql zip intl opcache \
    && apk del git

# pcov: driver de cobertura usado por "pest --coverage" (make cobertura)
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# composer.lock* (com asterisco) torna o ficheiro opcional na primeira
# construcao: ainda nao existe antes de "composer update" correr uma vez.
# Depois de gerado, deve ser versionado (RNF-08 - fixar dependencias).
COPY composer.json composer.lock* ./
RUN composer install --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
