# Production configuration

Production configuration is supplied by the deployment platform. Do not put
credentials in `.env`, `.env.prod`, the Docker image, or committed Symfony
secrets. The committed `.env.prod` deliberately contains blank values so a
deployment cannot silently reuse local database, mailer, URL, or secret
defaults.

## Required environment variables

| Variable | Production requirement |
|---|---|
| `APP_ENV` | `prod` |
| `APP_DEBUG` | `0` |
| `APP_SECRET` | Unique random value of at least 32 characters |
| `DATABASE_URL` | Railway PostgreSQL URL, including credentials and database name |
| `MAILER_DSN` | Real mail transport; `null://null` is rejected |
| `MAILER_FROM_ADDRESS` | Verified sender address |
| `MAILER_FROM_NAME` | Visible sender name |
| `APP_BASE_URL` | Public HTTPS origin, without a trailing path |

Generate `APP_SECRET` locally and copy only the output into Railway:

```bash
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Use Railway variable references for `DATABASE_URL` instead of copying a local
PostgreSQL URL. Treat both `APP_SECRET` and `DATABASE_URL` as credentials:
restrict access, never print them in build logs, and rotate them if exposed.

## Preflight

Run this in the deployed service (or with the exact production variables in a
safe shell) before migrations or traffic are enabled:

```bash
php bin/console app:production:check
```

The command reports variable names and validation failures but never their
values. It rejects debug mode, committed development/test secrets, localhost
databases, disabled mail delivery, and non-HTTPS public URLs.

After changing variables, redeploy or restart the service and run the preflight
again. A successful preflight checks configuration shape; it does not replace a
database connection check or a password-reset email smoke test.
