# Production Docker image

The root `Dockerfile` provides production and development Symfony images with FrankenPHP,
Caddy, PostgreSQL support, optimized Composer autoloading, and compiled
AssetMapper assets. Compose uses its development target; the separate
`Dockerfile.vercel` builds the hosted Production/Preview runtime described in
[Hosted runtime](../../architecture/devops/stateless-runtime.md).

All repository `.env` variants are excluded from the image, together with
development dependencies and tests. The image receives a container-only base
dotenv file containing fail-closed, non-secret defaults because Symfony Runtime
requires that file to exist. Deployment environment variables override every
blank value.

The image always sets `APP_ENV=prod` and `APP_DEBUG=0`. Its startup entrypoint:

1. prepares writable cache, log, and upload directories;
2. runs `app:production:check` without printing secret values;
3. clears and warms the Symfony cache;
4. drops from root to `www-data` and starts FrankenPHP on the configured `PORT`.

Doctrine migrations are not run by the image entrypoint. They require the
separate migration and backup policy in [Migrations](../db/migrations.md), so an
ordinary container restart cannot unexpectedly change the database schema.

## Persistent-container requirements

- Build the root `Dockerfile` for a host that supports persistent containers.
- Generate a public domain and set `APP_BASE_URL` to its HTTPS origin.
- Set all variables listed in [Production configuration](production-configuration.md).
- Set the healthcheck path to `/healthz` with an appropriate timeout.
- Mount persistent storage at exactly `/app/var/uploads` before accepting any
  uploads. A different path will leave uploaded media on ephemeral storage.
- Keep one application replica while using local media storage. Multiple
  replicas would not share the mounted filesystem consistently.

Volume mounts are supplied at runtime. The entrypoint starts as root only long
enough to prepare writable directories and their ownership, then runs the
application as `www-data`.

The app server listens on `{$PORT}` with `80` as Caddy's fallback. Set
`PORT=8080` to match the image's exposed port and the example below. When the
hosting edge terminates HTTPS, Symfony trusts the immediate proxy; verify its
forwarded headers and secure cookies before enabling traffic.

## Local verification

Build the portable image:

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
