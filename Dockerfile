# syntax=docker/dockerfile:1

# The site in one image: FrankenPHP (Caddy with PHP 8.5) serves it on port 80, and the same
# container runs Lynx's scheduler and the queue worker. The database, uploads and logs live in
# /app/storage, which should be a volume. "Deploying with Docker" in the README has the steps.

FROM dunglas/frankenphp:1-php8.5 AS php

RUN install-php-extensions gd intl pcntl pdo_pgsql zip \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-site.ini"
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app

# PHP packages, then the compiled Blade views: Tailwind reads those to find the classes that
# Lynx's own views use.
FROM php AS vendor
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist
COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && php artisan view:cache

FROM node:22-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY --from=vendor /app ./
RUN npm run build

FROM php AS app
ENV SERVER_NAME=:80 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/app/storage/database/database.sqlite
COPY --from=vendor --chown=www-data:www-data /app /app
COPY --from=assets --chown=www-data:www-data /app/public/build /app/public/build
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/site-entrypoint
# Port 80 without root.
RUN setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp \
    && chown -R www-data:www-data /config/caddy /data/caddy
USER www-data
EXPOSE 80
ENTRYPOINT ["site-entrypoint"]
CMD ["--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]
