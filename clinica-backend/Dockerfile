# syntax=docker/dockerfile:1.6
# Convertir a multi-stage para tener PHP-FPM + y la api con Nginx con mi código
############################
# PHP-FPM + Composer (build)
############################
FROM php:8.2-fpm-alpine AS php
WORKDIR /var/www

# SO y extensiones necesarias
RUN apk add --no-cache icu-dev libzip-dev oniguruma-dev git unzip bash nano \
 && docker-php-ext-configure intl \
 && docker-php-ext-install -j$(nproc) pdo pdo_mysql intl bcmath zip opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copiamos solo composer.* primero
COPY composer.json composer.lock ./

# Instalar dependencias sin scripts primero para cachear
RUN composer install \
    --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader \
    --no-scripts

# Copiamos el proyecto
COPY . .

# Segunda pasada para ejecutar scripts de composer
RUN composer install \
    --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

# Permisos
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Entrypoint y CMD por defecto
COPY .docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["sh", "/entrypoint.sh"]
CMD ["php-fpm"]

############################
# API (Nginx sirviendo /public)
############################
FROM nginx:1.25-alpine AS api
WORKDIR /var/www

# Copiamos el código desde el stage php
COPY --from=php /var/www /var/www

# Conf de Nginx que apunta a /var/www/public
COPY .docker/nginx.conf /etc/nginx/conf.d/default.conf

# healthcheck sencillo para verificar que el contenedor está vivo
HEALTHCHECK --interval=30s --timeout=3s --retries=5 CMD wget -qO- http://127.0.0.1/_health || exit 1
