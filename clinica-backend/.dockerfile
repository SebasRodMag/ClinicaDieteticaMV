# 1) Composer: instalar vendor
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 2) Runtime: PHP-FPM + Nginx + Supervisor en Alpine
FROM php:8.2-fpm-alpine

# Paquetes y extensiones PHP necesarias
RUN apk add --no-cache \
    nginx supervisor bash git curl icu-dev oniguruma-dev libzip-dev zlib-dev \
    freetype-dev libjpeg-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath opcache intl zip gd

# Directorios
WORKDIR /var/www/html

# Copiar código Laravel ya con vendor desde la etapa anterior
COPY --from=vendor /app /var/www/html

# Nginx & Supervisor configs
COPY docker/nginx-laravel.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

# Permisos Laravel
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Variables de entorno por defecto
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0

EXPOSE 8080

# Lanzar Nginx + PHP-FPM con Supervisor
CMD ["/usr/bin/supervisord","-c","/etc/supervisord.conf"]