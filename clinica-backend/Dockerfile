FROM php:8.2-fpm-alpine AS php
WORKDIR /var/www

# SO y extensiones necesarias
RUN apk add --no-cache icu-dev libzip-dev oniguruma-dev git unzip bash nano \
 && docker-php-ext-configure intl \
 && docker-php-ext-install -j$(nproc) pdo pdo_mysql intl bcmath zip opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Se copia solo composer.* para cachear vendor
COPY composer.json composer.lock ./

# Se instalamos dependencias
RUN composer install \
    --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader \
    --no-scripts

# Copiamos el proyecto
COPY . .

# Se ejecuta composer otra vez para que corran los scripts
RUN composer install \
    --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

# Permisos
RUN chown -R www-data:www-data storage bootstrap/cache

# Entrypoint
COPY .docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["sh", "/entrypoint.sh"]
CMD ["php-fpm"]