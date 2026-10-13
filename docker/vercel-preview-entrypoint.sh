#!/bin/sh
set -eu

if [ "${VERCEL_ENV:-preview}" != preview ]; then
    echo 'This feasibility image supports Preview only.' >&2
    exit 1
fi
: "${APP_SECRET:?Set a new Preview-only APP_SECRET}"
: "${DATABASE_URL:?Set an isolated Preview database URL}"
: "${APP_BASE_URL:?Set the HTTPS Preview origin}"
export APP_ENV=prod APP_DEBUG=0 MAILER_DSN=null://null BACKUP_SCHEDULE_ENABLED=0
export MAILER_FROM_ADDRESS=no-reply@dogs-diary.test MAILER_FROM_NAME='Dogs Diary Preview'

if [ "${VERCEL:-0}" = 1 ]; then
    php /app/bin/console app:production:check --preview --require-tls --no-debug
else
    # Local Docker smoke uses its isolated PostgreSQL service without TLS.
    php /app/bin/console app:production:check --preview --no-debug
fi
php /app/bin/console cache:clear --no-debug
php /app/bin/console cache:warmup --no-debug
# No migrations, persistent scheduler, or filesystem backup capture in this runtime.
exec "$@"
