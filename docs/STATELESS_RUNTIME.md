# PostgreSQL-backed security state and Vercel runtime

Apply the squashed baseline `Version20261013180000` before starting the new
runtime. On Monday's backup it adds `sessions`, `security_rate_limits`, and
`lock_keys`; on an already upgraded database it preserves those tables and
adopts the baseline. Business data remains intact. See [MIGRATIONS.md](MIGRATIONS.md)
for supported starting states and restore checks.
The schema listener includes these tables in Doctrine's normal schema validation
in every environment. Do not exclude them with a schema filter.

## Runtime behavior

Production-mode sessions use Symfony's PDO handler with transactional row locks
and a separate PDO connection from the ORM. DBAL constructs that connection so
PostgreSQL TLS parameters are preserved. Sessions have a sliding 24-minute TTL;
the browser cookie is Secure, HttpOnly, SameSite=Lax. Development retains its
local session storage; PHPUnit's ordinary functional tests retain mock sessions.
The two-instance smoke below exercises the actual production handler.

Login limits are unchanged: five attempts per username/IP and 25 per IP per
minute. State and lock leases live in PostgreSQL in all environments. The key
uses `APP_SECRET`, stable across instances and builds, rather than a build hash.
Missing/failed state storage raises an error instead of resetting the budget.
Expired state is pruned probabilistically on writes; session/lock stores also
perform probabilistic expiry cleanup. Keep instance clocks synchronized.

The runtime uses up to two database connections per active request using a
session: one for ORM/limiter state, one for the session transaction. Bound hosting
concurrency against the selected provider's limits. The session transaction holds
a backend connection until session close, even with transaction pooling. Avoid
session-level advisory locks with PgBouncer transaction pooling. Large private
media requests may keep sessions active; revisit early session closing with the
media behavior and measure connection occupancy under hosted traffic.

## Hosted runtime

`Dockerfile.vercel` uses `docker/vercel-entrypoint.sh`. Production startup
preserves real mail settings, requires a canonical HTTPS origin, PostgreSQL TLS
and R2 storage, validates configuration and warms disposable local cache. It
never runs migrations or schedules backups. Compiled assets
are in the image; production logs go to stderr. Local storage remains available
for development and isolated tests, not durable Vercel production storage.

R2 storage can be checked manually without deploying or booting the application.
See [PRODUCTION_CONFIGURATION.md](PRODUCTION_CONFIGURATION.md#manual-r2-storage-check).

### Isolated Preview compatibility

Preview still disables mail and requires separate credentials. These protections
remain useful for isolated security tests; they are not production configuration.

Configure Preview-only `APP_SECRET`, `DATABASE_URL`, and `PORT=8080`.
Hosted startup derives `APP_BASE_URL` from the deployment's `VERCEL_URL` when no
explicit HTTPS origin is supplied; local Docker smoke still requires it explicitly.
Do not reuse live credentials. With one hosted database, keep synthetic Preview
tables in a separate `preview` schema and use a role with no `USAGE` on `public`.
That role must default to `search_path=preview` on both direct and pooled
connections; verify those permissions before putting its URL in Preview.
Keep Vercel's system environment variables enabled: `VERCEL=1` activates mandatory
Preview TLS validation; `VERCEL_ENV=preview` selects isolated Preview behavior.
Use the same Preview secret across instances, different from production.

Create a Preview from the project root with the container port set explicitly:

```sh
vercel deploy --target preview --env PORT=8080
```

The deployment override keeps Vercel's routing port aligned with the image's
default, even if the project-level port differs. Do not pass database credentials
or the application secret on the command line; use the Preview-scoped variables.
The image build verifies the PostgreSQL PDO driver and validates Caddy's
configuration as the runtime user before deployment.

Runtime `DATABASE_URL` can use the provider's pooled endpoint. Run migrations,
imports and dumps with its **direct** URL, supplied only for those commands.
Use `sslmode=verify-full` with the provider's trusted CA where supported (at least
`sslmode=require`); URL-encode credentials. Never print either URL in reports.
Preflight checks the URL policy, not the live TLS connection. Verify actual
encryption on the actual client connection before declaring deployment ready.
Neon's proxy terminates TLS before PostgreSQL, so backend `pg_stat_ssl` may report
false even with verified TLS on the client-to-proxy connection. Use verified CA
TLS settings and client connection information instead of treating that backend
flag as proof of plaintext transport.

```sh
php bin/console app:production:check --preview --require-tls
# Inject the direct URL as DATABASE_URL through your private environment first:
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
```

Trusted proxy configuration assumes the hosting edge is the only ingress and
overwrites forwarded headers. Local smoke emulates that edge; verify the real
Vercel header chain and secure cookies on the deployed runtime. Preview `APP_BASE_URL`
must match the Preview origin so reset links do not point to production.

On a first switch from filesystem sessions, existing sessions will need login
again. Preserve `APP_SECRET` for subsequent releases. Roll back application code
without dropping the new tables: the baseline intentionally has no destructive
down path. Code from before the squash must not be used to run migrations against
baseline metadata. Dropping tables would erase throttling and invalidate sessions.

## Recovery boundary

The former filesystem/restic backup scheduler and its instance-local HTTP write
lock have been removed. The application does not schedule backups.
Provider-managed PostgreSQL PITR must be proved separately.

For a coordinated DB/media export or cutover, the chosen consistency mechanism
is a maintenance window across **all** writers: block mutation traffic at the
common ingress, disable background writers, drain in-flight writes, capture DB
and media with a recorded timestamp/manifest, then reopen writes after validation.
An instance-local lock is not sufficient. This operational gate is not yet
configured on Vercel; do not claim a coordinated backup until it is exercised.
Independent PITR requires retaining deleted/replaced media for at least the DB
recovery window; verify the configured retention and coordinated restore behavior.

Provider acceptance remains: selected free plan and region, PostgreSQL version,
storage/compute/connection limits, selected retention and actual RPO, restore into
a separate branch, restored marker verified, duration and $0 cost recorded.
A local dump restore is not evidence of provider-managed recovery.

## Disposable cross-instance check

This explicit integration check creates synthetic accounts, expires their
sessions, resets test login budgets, and restarts **only** the named test apps.
It refuses other ports/databases and checks the app database targets and secrets.
It does not use production or the Monday development database.

Build and set up once (commands fail if names already exist; inspect/reuse them
rather than dropping databases):

```sh
docker network create dogs-diary-runtime
docker run -d --rm --name dogs-diary-runtime-db --network dogs-diary-runtime \
  --tmpfs /var/lib/postgresql \
  -e POSTGRES_DB=dogs_diary_runtime_test -e POSTGRES_USER=app \
  -e POSTGRES_PASSWORD=runtime-disposable-only \
  -p 127.0.0.1:15434:5432 postgres:18.6-alpine
# Wait until pg_isready succeeds before migrations.
docker exec dogs-diary-runtime-db pg_isready -U app -d dogs_diary_runtime_test
APP_ENV=test DATABASE_URL='postgresql://app:runtime-disposable-only@127.0.0.1:15434/dogs_diary_runtime?serverVersion=18&charset=utf8' \
  php bin/console doctrine:migrations:migrate --no-interaction
docker build --platform linux/amd64 -f Dockerfile.vercel -t dogs-diary:vercel-runtime-check .
```

Start the two apps with separate filesystems, using `a`/18181 and `b`/18182:

```sh
docker run -d --rm --platform linux/amd64 --name dogs-diary-runtime-a --network dogs-diary-runtime \
  -p 127.0.0.1:18181:8080 \
  -e APP_SECRET=runtime-isolated-shared-session-secret-2026 \
  -e DATABASE_URL='postgresql://app:runtime-disposable-only@dogs-diary-runtime-db:5432/dogs_diary_runtime_test?serverVersion=18&charset=utf8' \
  -e APP_BASE_URL=https://diary.test dogs-diary:vercel-runtime-check
docker run -d --rm --platform linux/amd64 --name dogs-diary-runtime-b --network dogs-diary-runtime \
  -p 127.0.0.1:18182:8080 \
  -e APP_SECRET=runtime-isolated-shared-session-secret-2026 \
  -e DATABASE_URL='postgresql://app:runtime-disposable-only@dogs-diary-runtime-db:5432/dogs_diary_runtime_test?serverVersion=18&charset=utf8' \
  -e APP_BASE_URL=https://diary.test dogs-diary:vercel-runtime-check
python3 -B tests/Operations/shared_state_smoke.py
```

The smoke checks registration/login/CSRF/logout/reset across instances and
restarts, single-use reset tokens, password-change session invalidation, session
expiry, secure cookie flags and five concurrent login failures followed by a
correct password that must still be throttled. Preview uses null mail, so the
reset completion uses a known synthetic token fixture in the disposable DB;
this check does not prove provider email delivery.

Stop when finished. These `--rm` containers contain only synthetic data and are
removed automatically, including the tmpfs database:

```sh
docker stop dogs-diary-runtime-a dogs-diary-runtime-b dogs-diary-runtime-db
docker network rm dogs-diary-runtime
```
