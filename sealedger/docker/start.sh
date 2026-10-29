#!/bin/sh
set -e
cd /app

# Uploaded fish photos live in storage/app/public (mount a Railway Volume here).
mkdir -p storage/app/public/products
rm -f public/storage
php artisan storage:link --force

php artisan package:discover --ansi
php artisan migrate --force

# One-time sample data + the vendor login. Set RUN_SEED=true for the first
# deploy only, then remove it (the product seeder resets sample stock levels).
if [ "$RUN_SEED" = "true" ]; then
    php artisan db:seed --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
