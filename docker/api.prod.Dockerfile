# Imagem de producao da API do SGA.
#
# Diferencas face a docker/api.Dockerfile (desenvolvimento):
#   - o codigo e COPIADO para dentro da imagem (em desenvolvimento e
#     bind-mounted), por isso a imagem e auto-suficiente e reproduzivel;
#   - sem pcov nem dependencias de desenvolvimento (--no-dev);
#   - opcache afinado para producao (ver docker/php/producao.ini);
#   - entrypoint que reconstroi as caches de config/rotas/vistas no
#     arranque -- nao podem ser construidas no "build" porque dependem
#     do .env, que so existe na VPS.

# ---------------------------------------------------------------- vendor
FROM composer:2 AS vendor
WORKDIR /app
COPY backend/composer.json backend/composer.lock ./
RUN composer install \
      --no-dev \
      --no-scripts \
      --no-autoloader \
      --prefer-dist \
      --no-interaction

# ----------------------------------------------------------------- final
FROM php:8.3-fpm-alpine

RUN apk add --no-cache postgresql-dev libzip-dev icu-dev oniguruma-dev \
    && docker-php-ext-install pdo pdo_pgsql zip intl opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/producao.ini /usr/local/etc/php/conf.d/zz-producao.ini

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY backend/ ./

# O mkdir -p e uma rede de seguranca: se alguma destas pastas nao vier no
# contexto de build, "config:cache"/"view:cache" falham no arranque.
RUN mkdir -p storage/framework/cache/data storage/framework/sessions \
             storage/framework/views storage/logs bootstrap/cache \
    && composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint-api.sh /usr/local/bin/entrypoint-api
RUN chmod +x /usr/local/bin/entrypoint-api

EXPOSE 9000
ENTRYPOINT ["entrypoint-api"]
CMD ["php-fpm"]
