FROM composer:2 AS composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --ignore-platform-reqs --no-scripts

FROM node:alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json vite.config.js ./
COPY resources/ ./resources/
COPY --from=composer /app/vendor ./vendor

RUN npm ci && \
    npm run build

FROM ghcr.io/auroradevllc/php-fpm-laravel:php8.5

USER root

# Runtime dependencies (image optimization)
RUN apk add --no-cache jpegoptim \
    optipng \
    libavif-apps \
    supervisor

RUN mkdir -p /var/log/supervisor

COPY ./docker/tmp.ini /usr/local/etc/php/config.d/custom.ini

USER www-data
WORKDIR /var/www

COPY --chown=www-data . .
COPY --chown=www-data --from=composer /app/vendor/ ./vendor
COPY --chown=www-data --from=frontend /app/public/build/ public/build/

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN composer dump-autoload && \
    php artisan storage:link

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
