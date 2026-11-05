# syntax=docker/dockerfile:1
FROM node:22-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm install --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

FROM php:8.3-apache AS app
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev libicu-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev unzip git curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql zip intl bcmath gd exif opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-gestionahub.ini
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint && chmod +x /usr/local/bin/entrypoint
WORKDIR /var/www/html
ARG INSTALL_DEV=false
COPY composer.json composer.lock ./
RUN if [ "$INSTALL_DEV" = "true" ]; then FLAGS=""; else FLAGS="--no-dev"; fi \
    && composer install $FLAGS --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress
COPY . .
COPY --from=assets /app/public/build ./public/build
RUN if [ "$INSTALL_DEV" = "true" ]; then FLAGS=""; else FLAGS="--no-dev"; fi \
    && composer dump-autoload --optimize $FLAGS \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
               storage/logs storage/app/public storage/app/private bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0
EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -fs http://localhost/up || exit 1
ENTRYPOINT ["entrypoint"]
CMD ["apache2-foreground"]