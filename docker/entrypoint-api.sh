#!/bin/sh
# Arranque dos contentores de producao da API.
#
# config:cache/route:cache/view:cache nao podem correr no "docker build":
# dependem do .env, que so existe na VPS. Correm aqui, uma vez por
# arranque de contentor.
#
# Nota: com a config em cache, o .env deixa de ser lido em runtime -- toda
# a leitura de configuracao tem de passar por config(), nunca por env()
# (ver config/sanctum.php).
set -e

# So os processos de longa duracao preparam as caches. Sem esta distincao,
# um "docker compose run --rm api php artisan ..." pontual reconstruia as
# caches e misturava a saida do artisan com a do entrypoint -- o que, por
# exemplo, tornava impossivel capturar a saida de "key:generate --show".
case "$*" in
    php-fpm|*queue:work*|*schedule:work*)
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        ;;
esac

exec "$@"
