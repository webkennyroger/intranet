#!/bin/sh
set -e
cd /var/www/html

# O volume de storage começa vazio: recria a estrutura que o Laravel espera.
mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs

# Banco SQLite num volume próprio (/data), fora da imagem.
DB_FILE="${DB_DATABASE:-/data/database.sqlite}"
mkdir -p "$(dirname "$DB_FILE")"
[ -f "$DB_FILE" ] || touch "$DB_FILE"

php artisan package:discover --ansi
php artisan storage:link --force
# Num banco novo, carrega o dump de schema; num existente, só aplica o que faltar.
php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

chown -R www-data:www-data storage bootstrap/cache "$(dirname "$DB_FILE")"

exec "$@"
