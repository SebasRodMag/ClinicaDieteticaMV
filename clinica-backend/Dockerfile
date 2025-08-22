# --- PHP-FPM con extensiones necesarias + Composer ---
FROM php:8.2-fpm-alpine AS php
WORKDIR /var/www

# Paquetes del sistema
RUN apk add --no-cache bash git unzip icu-dev libzip-dev oniguruma-dev

# Extensiones PHP (incluye bcmath que necesita tu dependencia)
RUN docker-php-ext-configure intl \
 && docker-php-ext-install -j$(nproc) intl pdo_mysql bcmath zip opcache

# (Opcional) Zona horaria
RUN echo "date.timezone=UTC" > /usr/local/etc/php/conf.d/date.ini

# Añadimos Composer a esta imagen
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instalar dependencias de PHP con Composer (aprovecha cache si COPY selectivo)
COPY composer.json composer.lock ./
RUN composer install \
  --no-dev --prefer-dist --no-progress --no-interaction --optimize-autoloader \
  --no-scripts

# Copiamos el resto del proyecto
COPY . .

# Permisos Laravel
RUN chown -R www-data:www-data storage bootstrap/cache

# Entrypoint (key:generate, caches, migrate…)
COPY .docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["sh", "/entrypoint.sh"]
CMD ["php-fpm"]

# --- Nginx para la API (sirve /public y pasa PHP a FPM) ---
FROM nginx:1.25-alpine AS nginx
WORKDIR /var/www
COPY --from=php /var/www/public ./public
COPY .docker/nginx.conf /etc/nginx/conf.d/default.conf
