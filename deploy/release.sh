#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
if [ ! -f .env ]; then
    echo 'Create .env from .env.production.example and fill the real server values first.' >&2
    exit 1
fi
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan config:clear
php artisan syncode:production-check --config-only
# Before updates, run syncode:backup and back up storage/app/public separately.
php artisan migrate --force
# Production seeders only add default categories; demo seeders refuse production.
php artisan db:seed --force
if [ ! -e public/storage ] && [ ! -L public/storage ]; then
    php artisan storage:link
fi
php artisan optimize
php artisan queue:restart
printf '%s\n' 'Release prepared. On first installation run: php artisan syncode:admin' 'Then run: php artisan syncode:production-check'
