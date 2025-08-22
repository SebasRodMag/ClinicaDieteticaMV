#!/usr/bin/env sh
set -e

cd /var/www

# Copia .env si no existe (útil en despliegues)
if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

# Genera APP_KEY si falta
if ! grep -q "^APP_KEY=" .env || [ -z "$(grep '^APP_KEY=' .env | cut -d= -f2)" ]; then
  php artisan key:generate --force || true
fi

# Cache de configuración/ROUTES/views para prod
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Espera a DB y migra (opcional en prod; puedes comentar si prefieres manual)
php artisan migrate --force || true

exec "$@"
