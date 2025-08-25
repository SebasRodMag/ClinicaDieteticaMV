FROM php:8.2-fpm-alpine

WORKDIR /var/www

# Paquetes necesarios
RUN apk add --no-cache git unzip icu-dev libzip-dev oniguruma-dev

# Extensiones PHP
RUN docker-php-ext-install pdo pdo_mysql intl bcmath zip opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copia de código
COPY . .

# Instalar dependencias de PHP
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction --optimize-autoloader \
 && chown -R www-data:www-data storage bootstrap/cache

# Entrypoint
COPY .docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["sh", "/entrypoint.sh"]
CMD ["php-fpm"]
