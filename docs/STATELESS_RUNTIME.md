# PostgreSQL-backed security state and Preview runtime

Apply `Version20261013120000` before starting the new runtime. It adds `sessions`,
`security_rate_limits`, and `lock_keys`; no business tables or data change.
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
R2 implementation and measure connection occupancy before cutover.

## Hosted Preview

`Dockerfile.vercel` is a Preview-only image. Its entrypoint refuses a production
deployment, disables mail and backup scheduling, validates configuration and
warms only disposable local cache. It never runs migrations. Compiled assets are
in the image; production logs already go to stderr. Media is still local and
ephemeral until Wednesday's storage work.

Configure Preview-only `APP_SECRET`, `DATABASE_URL`, `APP_BASE_URL` (HTTPS origin),
and `PORT=8080`. Do not reuse live credentials or connect Preview to the live DB.
Keep Vercel's system environment variables enabled: `VERCEL=1` activates mandatory
TLS validation; `VERCEL_ENV=preview` selects the permitted deployment environment.
Use the same Preview secret across instances, different from production.

Runtime `DATABASE_URL` can use the provider's pooled endpoint. Run migrations,
imports and dumps with its **direct** URL, supplied only for those commands.
Use `sslmode=verify-full` with the provider's trusted CA where supported (at least
`sslmode=require`); URL-encode credentials. Never print either URL in reports.
Preflight checks the URL policy, not the live TLS connection. Verify actual
encryption with `pg_stat_ssl` on both the ORM and session connection before
declaring a hosted deployment ready.

```sh
php bin/console app:production:check --preview --require-tls
# Inject the direct URL as DATABASE_URL through your private environment first:
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
```

Trusted proxy configuration assumes the hosting edge is the only ingress and
overwrites forwarded headers. Local smoke emulates that edge; verify the real
Vercel header chain and secure cookies remotely before cutover. `APP_BASE_URL`
must match the Preview origin so reset links do not point to production.

On a first switch from filesystem sessions, existing sessions will need login
again. Preserve `APP_SECRET` for subsequent releases. Roll back application code
without dropping the new tables: the migration intentionally has no destructive
down path. Dropping tables would erase throttling and invalidate sessions.

## Recovery boundary

`ops/backup.py serve` and its filesystem capture lock are only valid for the
existing single-replica filesystem workflow. They are not the new hosted recovery
mechanism. Provider-managed PostgreSQL PITR must be proved separately.

For a coordinated DB/media export or cutover, the chosen consistency mechanism
is a maintenance window across **all** writers: block mutation traffic at the
common ingress, disable background writers, drain in-flight writes, capture DB
and media with a recorded timestamp/manifest, then reopen writes after validation.
An instance-local lock is not sufficient. This operational gate is not yet
configured on Vercel; do not claim a coordinated backup until it is exercised.
Independent PITR requires retaining deleted/replaced media for at least the DB
recovery window; finalize that policy with Wednesday's R2 work.

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
docker network create dogs-diary-tuesday
docker run -d --name dogs-diary-tuesday-db --network dogs-diary-tuesday \
  -e POSTGRES_DB=dogs_diary_tuesday_test -e POSTGRES_USER=app \
  -e POSTGRES_PASSWORD=tuesday-disposable-only \
  -p 127.0.0.1:15432:5432 postgres:16-alpine
# Wait until pg_isready succeeds before migrations.
docker exec dogs-diary-tuesday-db pg_isready -U app -d dogs_diary_tuesday_test
APP_ENV=test DATABASE_URL='postgresql://app:tuesday-disposable-only@127.0.0.1:15432/dogs_diary_tuesday?serverVersion=16&charset=utf8' \
  php bin/console doctrine:migrations:migrate --no-interaction
docker build -f Dockerfile.vercel -t dogs-diary:tuesday-preview .
```

Start the two apps with separate filesystems, using `a`/18181 and `b`/18182:

```sh
docker run -d --name dogs-diary-tuesday-a --network dogs-diary-tuesday \
  -p 127.0.0.1:18181:8080 \
  -e APP_SECRET=tuesday-isolated-shared-session-secret-2026 \
  -e DATABASE_URL='postgresql://app:tuesday-disposable-only@dogs-diary-tuesday-db:5432/dogs_diary_tuesday_test?serverVersion=16&charset=utf8' \
  -e APP_BASE_URL=https://diary.test dogs-diary:tuesday-preview
docker run -d --name dogs-diary-tuesday-b --network dogs-diary-tuesday \
  -p 127.0.0.1:18182:8080 \
  -e APP_SECRET=tuesday-isolated-shared-session-secret-2026 \
  -e DATABASE_URL='postgresql://app:tuesday-disposable-only@dogs-diary-tuesday-db:5432/dogs_diary_tuesday_test?serverVersion=16&charset=utf8' \
  -e APP_BASE_URL=https://diary.test dogs-diary:tuesday-preview
python3 -B tests/Operations/shared_state_smoke.py
```

The smoke checks registration/login/CSRF/logout/reset across instances and
restarts, single-use reset tokens, password-change session invalidation, session
expiry, secure cookie flags and five concurrent login failures followed by a
correct password that must still be throttled. Preview uses null mail, so the
reset completion uses a known synthetic token fixture in the disposable DB;
this check does not prove provider email delivery.

Stop when finished, preserving test data for inspection:

```sh
docker stop dogs-diary-tuesday-a dogs-diary-tuesday-b dogs-diary-tuesday-db
```
