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
| `DATABASE_URL` | Hosted PostgreSQL URL, including credentials and database name |
| `MAILER_DSN` | Real mail transport; `null://null` is rejected |
| `MAILER_FROM_ADDRESS` | Verified sender address |
| `MAILER_FROM_NAME` | Visible sender name |
| `APP_BASE_URL` | Public HTTPS origin, without a trailing path |

Vercel also requires `MEDIA_STORAGE=r2` and the five `MEDIA_R2_*` variables in
[R2_MEDIA.md](R2_MEDIA.md); its filesystem cannot provide durable media storage.
The Vercel production entrypoint enforces this requirement using
`app:production:check --require-tls --require-r2`. It preserves the configured
production mail transport, requires an explicit canonical origin and disables
the filesystem backup scheduler. It never runs migrations.

Generate `APP_SECRET` locally and save the output in the deployment platform's secret settings:

```bash
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Use the hosted database connection URL for `DATABASE_URL`, with TLS enabled,
instead of a local PostgreSQL URL. Treat both `APP_SECRET` and `DATABASE_URL` as credentials:
restrict access, never print them in build logs, and rotate them if exposed.

## Preflight

Run this in the deployed service (or with the exact production variables in a
safe shell) before migrations or traffic are enabled:

```bash
php bin/console app:production:check --require-tls --require-r2
```

The command reports variable names and validation failures but never their
values. It rejects debug mode, committed development/test secrets, localhost
databases, disabled mail delivery, and non-HTTPS public URLs.

After changing variables, redeploy or restart the service and run the preflight
again. A successful preflight checks configuration shape; it does not replace a
database connection check or a password-reset email smoke test.

The Vercel image supports Production and isolated Preview startup. Preview alone
forces disabled mail. Shared-state behavior and testing are documented in
[STATELESS_RUNTIME.md](STATELESS_RUNTIME.md).

## R2-only diagnostic deployment

To check the already-saved R2 credentials without exposing them locally, request
a non-promoted deployment with Production variables. Snapshot the existing
production deployment and all domain mappings first, and verify them afterward:

```bash
vercel deploy --prod --skip-domain --env R2_CHECK_ONLY=1 --env MEDIA_STORAGE=r2 --env PORT=8080
```

Observed on 15 October 2026: despite `--skip-domain`, Vercel reassigned the
default project alias to the diagnostic. The alias was explicitly restored and
verified against the prior deployment `dpl_CgKXXHxWNSw4fD8SqdAU1xqtokpF`.
Do not rely on this flag alone or repeat this against a live app without a
verified alias-restoration plan. No custom domain/DNS was changed.

This mode runs `ops/r2_check.php` before starting a health-only HTTP server.
It uses only new random synthetic objects, verifies signed uploads/downloads,
ranges, private access, retention, restore and cleanup. It does not boot Symfony,
connect to the database, send mail, or serve application routes. A failed check
prevents startup. Only `/healthz` returns 204 afterward; all application paths
return 503. Logs contain check names, not credentials or signed URLs.

Do not promote this deployment or save `R2_CHECK_ONLY=1` as a project-wide
variable. It is a storage diagnostic, not a user-facing production release.
Each cold start performs a bounded set of tiny synthetic operations; avoid load
testing this diagnostic. It does not verify browser CORS, large video playback,
bucket-wide retention policies or coordinated database recovery.

Current application-release blockers: Production has the five `MEDIA_R2_*`
variables, but still needs its own `DATABASE_URL`, stable `APP_SECRET`, real
mail settings and canonical `APP_BASE_URL`. Configure `MEDIA_STORAGE=r2` and
`PORT=8080` there too; the diagnostic used deployment-only overrides for these.
Do not reuse the isolated Preview database role for production.
