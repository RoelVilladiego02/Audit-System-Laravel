#!/usr/bin/env sh
set -e
if [ ! -d bootstrap/cache ]; then
  mkdir -p bootstrap/cache
fi
# Ensure writable
chmod -R 0777 bootstrap/cache || true
# Clear old caches safely
php artisan optimize:clear || true
