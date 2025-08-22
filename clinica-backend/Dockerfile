# --- Stage 1: dependencias de Composer (capa caché) ---
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction

# --- Stage 2: PHP-FPM con extensiones necesarias ---
FROM php:8.2-fpm-alpine AS php
WORKDIR /var/www

RUN apk add --no-cache icu-dev libzip-dev oniguruma-dev git bash nano \
    && docker-php-ext-install pdo pdo_mysql intl bcmath opcache

# Opcional: timezone
RUN echo "date.timezone=UTC" > /usr/local/etc/php/conf.d/date.ini

COPY --from=vendor /app/vendor ./vendor
COPY . .

# Permisos de Laravel
RUN chown -R www-data:www-data storage bootstrap/cache

# Script de arranque para key:generate y optimizaciones
COPY .docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["sh", "/entrypoint.sh"]
CMD ["php-fpm"]

# --- Stage 3: Nginx para la API (sirve /public y pasa PHP a FPM) ---
FROM nginx:1.25-alpine AS nginx
WORKDIR /var/www
COPY --from=php /var/www/public ./public
COPY .docker/nginx.conf /etc/nginx/conf.d/default.conf
