#!/bin/sh
set -eu

mkdir -p /app/var/cache /app/var/log /app/var/uploads /config /data
chown www-data:www-data /app/var/cache /app/var/log /app/var/uploads /config /data

if [ "${APP_ENV:-prod}" = "prod" ]; then
    gosu www-data php /app/bin/console app:production:check --no-debug
    gosu www-data php /app/bin/console cache:clear --no-debug
    gosu www-data php /app/bin/console cache:warmup --no-debug
else
    mkdir -p /app/vendor /app/assets/vendor "${COMPOSER_HOME:-/app/var/composer}"
    chown -R www-data:www-data /app/vendor /app/assets/vendor "${COMPOSER_HOME:-/app/var/composer}"
    gosu www-data composer install --no-interaction --prefer-dist
    gosu www-data php /app/bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
fi

exec gosu www-data "$@"
