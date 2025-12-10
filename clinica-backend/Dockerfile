# syntax=docker/dockerfile:1.6

############################
# PHP-FPM + Composer (build)
############################
FROM php:8.2-fpm-alpine AS php
WORKDIR /var/www

# SO y extensiones necesarias
RUN apk add --no-cache icu-dev libzip-dev oniguruma-dev git unzip bash nano netcat-openbsd \
 && docker-php-ext-configure intl \
 && docker-php-ext-install -j$(nproc) pdo pdo_mysql intl bcmath zip opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copiamos solo composer.* primero (mejor cache)
COPY composer.json composer.lock ./

# Instalar dependencias sin scripts (cache)
RUN composer install \
    --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader \
    --no-scripts

# Copiamos el proyecto
COPY . .

# Segunda pasada para ejecutar scripts
RUN composer install \
    --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

# Permisos runtime
RUN mkdir -p storage bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

# Entrypoint
COPY .docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Pool PHP-FPM escuchando 0.0.0.0:9000
COPY .docker/pool-www.conf /usr/local/etc/php-fpm.d/zz-pool.conf

ENTRYPOINT ["sh", "/entrypoint.sh"]
CMD ["php-fpm"]

############################
# API (Nginx sirviendo /public)
############################
FROM nginx:1.25-alpine AS api
WORKDIR /var/www

# Copiamos el código desde el stage php
COPY --from=php /var/www /var/www

# Conf de Nginx que apunta a /var/www/public y proxy a php:9000
COPY .docker/nginx.conf /etc/nginx/conf.d/default.conf

# Healthcheck
HEALTHCHECK --interval=30s --timeout=3s --retries=5 \
CMD wget -qO- http://127.0.0.1/_health || exit 1

#Modificación de la configuración inicial para subir archivos más grandes
RUN { \
    echo "file_uploads = On"; \
    echo "upload_max_filesize = 25M"; \
    echo "post_max_size = 25M"; \
} > /usr/local/etc/php/conf.d/uploads.ini