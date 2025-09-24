#!/usr/bin/env sh
set -e

cd /var/www

# Esperar a MySQL (usa tus variables del entorno)
until php -r "try { new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); } catch (Exception $e) { exit(1);}"; do
  echo "... Esperando a la base de datos (${DB_HOST}:${DB_PORT})..."
  sleep 2
done

# Si NO hay APP_KEY en el entorno, entonces sí preparamos .env y generamos la key
if [ -z "${APP_KEY}" ]; then
  [ ! -f .env ] && [ -f .env.example ] && cp .env.example .env
  grep -q '^APP_KEY=' .env || echo 'APP_KEY=' >> .env
  php artisan config:clear || true
  php artisan key:generate --force || true
fi

# Cache de configuración/rutas/vistas con las variables actuales
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Migraciones solo si está habilitado
if [ "${MIGRATE_ON_START:-false}" = "true" ]; then
  php artisan migrate --force --no-interaction
fi

# Seed opcional
if [ "${SEED_ON_START:-false}" = "true" ]; then
  php artisan db:seed --force --no-interaction
fi

exec php-fpm
