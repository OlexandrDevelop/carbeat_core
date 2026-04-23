#!/bin/bash
set -e

cd /var/www

echo "[entrypoint] Running composer install..."
composer install --no-interaction --optimize-autoloader

exec "$@"
