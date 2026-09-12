FROM dunglas/frankenphp:1-php8.4-bookworm AS php_base

RUN install-php-extensions \
        intl \
        pdo_pgsql \
        zip

ENV APP_ENV=prod \
    APP_DEBUG=0

WORKDIR /app

FROM php_base AS build

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock symfony.lock ./

RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --no-autoloader \
        --prefer-dist

COPY . ./
COPY docker/runtime.env /app/.env

RUN composer dump-autoload \
        --no-dev \
        --no-interaction \
        --no-scripts \
        --classmap-authoritative \
    && php bin/console importmap:install \
    && php bin/console asset-map:compile \
    && php bin/console cache:clear --no-debug --no-warmup

FROM php_base AS runtime

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && apt-get update \
    && apt-get install -y --no-install-recommends gosu \
    && rm -rf /var/lib/apt/lists/*

COPY --from=build /app /app
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini

RUN chmod +x /usr/local/bin/app-entrypoint \
    && mkdir -p /app/var/cache /app/var/log /app/var/uploads /config /data \
    && chown -R www-data:www-data /app/var /config /data

EXPOSE 8080

ENTRYPOINT ["app-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
