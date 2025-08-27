#!/usr/bin/env sh
set -e

cd /var/www

# Espera a que MySQL acepte conexiones para ejecutar las migraciones
until php -r "try { new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); } catch (Exception $e) { exit(1);}"; do
  echo "⏳ Esperando a la base de datos (${DB_HOST}:${DB_PORT})..."
  sleep 2
done

# si no existe .env, copiar .env.example
[ ! -f .env ] && [ -f .env.example ] && cp .env.example .env

# si falta la línea APP_KEY, añadirla
grep -q '^APP_KEY=' .env || echo 'APP_KEY=' >> .env

# Genera clave si falta, si ya existe, no falla
php artisan key:generate --force || true

# Cache de configuración/ROUTES/views para prod
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Espera a DB y migra (opcional en prod; puedes comentar si prefieres manual)
php artisan migrate --force --no-interaction

# Sembrado controlado por variable de entorno en docker-compose.yml
# Por defecto está a false para evitar problemas en producción
if [ "${SEED_ON_START}" = "true" ]; then
  php artisan db:seed --force --no-interaction
fi

exec php-fpm
