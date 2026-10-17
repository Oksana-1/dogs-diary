#!/bin/sh
set -eu

export APP_ENV=prod APP_DEBUG=0

case "${VERCEL_ENV:-preview}" in
    production)
        : "${APP_BASE_URL:?Set the canonical production HTTPS origin}"
        : "${MAILER_DSN:?Set the production mail transport}"
        : "${MAILER_FROM_ADDRESS:?Set the verified production sender}"
        [ "${MEDIA_STORAGE:-}" = r2 ] || { echo 'Production requires MEDIA_STORAGE=r2.' >&2; exit 1; }
        preflight='--require-tls --require-r2'
        ;;
    preview)
        if [ -z "${APP_BASE_URL:-}" ] && [ "${VERCEL:-0}" = 1 ] && [ -n "${VERCEL_URL:-}" ]; then
            export APP_BASE_URL="https://${VERCEL_URL}"
        fi
        : "${APP_BASE_URL:?Set the HTTPS Preview origin}"
        export MAILER_DSN=null://null MAILER_FROM_ADDRESS=no-reply@dogs-diary.test MAILER_FROM_NAME='Dogs Diary Preview'
        preflight='--preview'
        if [ "${VERCEL:-0}" = 1 ]; then
            preflight='--preview --require-tls'
        fi
        ;;
    *) echo 'Unsupported VERCEL_ENV.' >&2; exit 1 ;;
esac

: "${APP_SECRET:?Set a stable environment-specific APP_SECRET}"
: "${DATABASE_URL:?Set the environment-specific PostgreSQL URL}"
php /app/bin/console app:production:check $preflight --no-debug
php /app/bin/console cache:clear --no-debug
php /app/bin/console cache:warmup --no-debug
exec "$@"
