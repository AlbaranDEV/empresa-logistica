FROM php:8.4-fpm

ARG UID=1000
ARG GID=1000

# Dependencias del sistema y extensiones de PHP requeridas por Laravel
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libicu-dev libonig-dev libpng-dev \
    && docker-php-ext-install pdo_mysql mbstring zip intl bcmath pcntl opcache gd \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Alinea el usuario www-data con el UID/GID del host para evitar problemas de permisos
RUN usermod -u ${UID} www-data && groupmod -g ${GID} www-data

WORKDIR /var/www/html

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
