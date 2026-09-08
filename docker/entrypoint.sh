#!/bin/sh
set -eu

mkdir -p /app/var/cache /app/var/log /app/var/uploads /config /data
chown www-data:www-data /app/var/cache /app/var/log /app/var/uploads /config /data

gosu www-data php /app/bin/console app:production:check --no-debug
gosu www-data php /app/bin/console cache:clear --no-debug
gosu www-data php /app/bin/console cache:warmup --no-debug

exec gosu www-data "$@"
