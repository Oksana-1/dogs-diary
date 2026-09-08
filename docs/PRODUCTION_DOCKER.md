# Production Docker image

The root `Dockerfile` builds a production-only Symfony image with FrankenPHP,
Caddy, PostgreSQL support, optimized Composer autoloading, and compiled
AssetMapper assets. Railway detects this file automatically.

All repository `.env` variants are excluded from the image, together with
development dependencies and tests. The image receives a container-only base
dotenv file containing fail-closed, non-secret defaults because Symfony Runtime
requires that file to exist. Railway variables override every blank value.

The image always sets `APP_ENV=prod` and `APP_DEBUG=0`. Its startup entrypoint:

1. prepares writable cache, log, and upload directories;
2. runs `app:production:check` without printing secret values;
3. clears and warms the Symfony cache;
4. drops from root to `www-data` and starts FrankenPHP on Railway's `PORT`.

Doctrine migrations are not run by the image entrypoint. They require the
separate migration and backup policy in the deployment checklist, so an
ordinary container restart cannot unexpectedly change the database schema.

## Railway service settings

- Deploy the repository as a service; Railway will detect `Dockerfile`.
- Generate a public domain and set `APP_BASE_URL` to its HTTPS origin.
- Set all variables listed in `PRODUCTION_CONFIGURATION.md`.
- Set the healthcheck path to `/healthz` with an appropriate timeout.
- Attach a Railway Volume at exactly `/app/var/uploads` before accepting any
  uploads. A different path will leave uploaded media on ephemeral storage.
- Keep one application replica while using local media storage. Multiple
  replicas would not share the mounted filesystem consistently.

Railway mounts volumes only at runtime and initially owns the mount point as
root. The entrypoint starts as root only long enough to prepare these writable
directories, then runs the application as `www-data`.

The app server listens on `{$PORT}` with `8080` as a local fallback. Railway
terminates HTTPS; Caddy and Symfony trust the immediate platform proxy so secure
cookies and absolute HTTPS URLs work correctly.

## Local verification

Build the same image Railway will use:

```bash
docker build --tag dogs-diary:production .
```

Run it with production-like variables and a reachable PostgreSQL URL:

```bash
docker run --rm --publish 8080:8080 \
  --env PORT=8080 \
  --env APP_SECRET='<unique-random-secret>' \
  --env DATABASE_URL='<postgresql-url>' \
  --env MAILER_DSN='<mailer-dsn>' \
  --env MAILER_FROM_ADDRESS='no-reply@example.com' \
  --env MAILER_FROM_NAME='Dogs Diary' \
  --env APP_BASE_URL='https://dogs-diary.example.com' \
  --volume dogs-diary-uploads:/app/var/uploads \
  dogs-diary:production
```

Then request `http://localhost:8080/healthz`; it must return HTTP 204. Stop and
start a new container with the same named volume to verify media persistence.
