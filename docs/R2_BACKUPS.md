# PostgreSQL and uploads backups to R2

Status: implementation prepared; production activation, R2 upload, and production
restore verification must be recorded separately. Creating the bucket and setting
variables do not enable backups.

The application stays on Railway. `bin/backup` runs in the application container,
where it can reach the private PostgreSQL 16 service and `/app/var/uploads`.
Do not run this as a separate Railway cron service: it would not have the same
uploads volume. Keep one application replica.

## What is saved

Each encrypted restic snapshot contains a PostgreSQL custom-format dump,
the complete uploads directory, and a manifest with UTC capture time, commit,
and file count. The repository lives under `production/` in the private R2 bucket.
Restic deduplicates data across snapshots; uploaded media is not publicly served
from this bucket.

During local capture, a filesystem lock prevents HTTP mutations from changing
the database/media pair. Existing mutations finish first; if a write is active,
the backup attempt fails and can be retried. New mutations during capture receive
503 with `Retry-After: 30`; reads and `/healthz` remain available. The lock is
released before the network upload. This is intended for this small, single-replica
app. Do not run migrations, manual SQL, file modifications, or mutating console
commands during capture: they do not participate in the HTTP lock. Review this
design before adding background writers, GET routes that mutate media, or replicas.

Plaintext staging is in a private temporary directory, cleaned up on success or
handled failure. Ensure ephemeral disk has room for a full uploads copy plus dump.
Abrupt machine termination can leave staging until the container is discarded.
Secrets are passed through the process environment, never command arguments or
normal application logs. Failed tools are reported by name and exit code without
their potentially sensitive diagnostics.

## Railway variables

Set these on the **application service**, in production:

| Variable | Value |
| --- | --- |
| `BACKUP_R2_ENDPOINT` | `https://3dd49a221e4f56976041618202492b82.r2.cloudflarestorage.com` |
| `BACKUP_R2_BUCKET` | `dogs-diary-backups` |
| `BACKUP_R2_REGION` | `auto` |
| `BACKUP_R2_ACCESS_KEY_ID` | Bucket-scoped Object Read & Write access key; seal it |
| `BACKUP_R2_SECRET_ACCESS_KEY` | Corresponding secret; seal it |
| `BACKUP_RESTIC_PASSWORD` | Unique random password of at least 32 characters; seal it |
| `BACKUP_SCHEDULE_ENABLED` | Leave unset or `0` until the first backup and restore drill pass |
| `BACKUP_HOUR_UTC` | `3` by default: daily at 03:00 UTC, independent of DST |
| `BACKUP_RETENTION_ENABLED` | Leave unset or `0` until retention is reviewed |

Save the restic password in your password manager **outside Railway**. Losing it
makes the backups unusable, even if the R2 access keys still work. Do not replace
it casually; restic password rotation requires updating repository keys.

The job reads the existing `DATABASE_URL`. It supports standard PostgreSQL URLs
and the query parameters `serverVersion`, `charset`, and `sslmode`; unexpected
options fail rather than silently changing connection security. The image installs
PostgreSQL client 16 from the signed PostgreSQL apt repository, plus Python 3 and
restic. There are no new Python package dependencies.

## First backup and activation

1. Review and deploy the code and staged variables with scheduling disabled.
   Deployment remains a separate operator action. Run the normal release gate.
2. In an authenticated terminal **inside the running application container**, run:

   ```sh
   gosu www-data /app/bin/backup init
   gosu www-data /app/bin/backup run
   gosu www-data /app/bin/backup status
   gosu www-data /app/bin/backup check
   ```

   `init` is a one-time operation. `run` never initializes or replaces a repository
   on a network/authentication error. Record the successful snapshot ID and time.
   The full `check` reads and verifies stored data; the check after each normal
   run validates repository metadata. Neither is a substitute for a restore drill.
3. Complete the scratch restore below. Backups are not verified until this passes.
4. Set `BACKUP_SCHEDULE_ENABLED=1` and apply the deployment. The entrypoint starts
   a supervisor for the existing web process and the daily backup. It forwards
   shutdown to both process groups and exits if the web process dies. The first
   scheduled run is the next configured UTC hour; startup does not trigger one.
   Failures retry twice at five-minute intervals, then wait for the next daily run.
5. Inspect logs and run `status` after the scheduled time to verify it actually ran.
   Check the first scheduled snapshot, not just the enabled variable.

## Retention and alerts

Do **not** configure R2 object-expiration rules for `production/`: restic snapshots
share data chunks, and expiring individual objects would corrupt the repository.
Do not enable bucket locks that prevent restic lock-file removal or pruning.

Preview the policy (7 daily and 4 weekly snapshots, scoped to this app's host/tag):

```sh
gosu www-data /app/bin/backup retention-preview
```

After reviewing it and completing a restore drill, set `BACKUP_RETENTION_ENABLED=1`
to apply it after successful scheduled/manual backups. Alternatively, explicitly
run `gosu www-data /app/bin/backup prune`. This deletes older snapshots and unused
data; until enabled, snapshots accumulate. It does not delete live application data.

Failures appear in application logs. `bin/backup status` exits nonzero if no snapshot
exists or the latest is older than 26 hours. An external alert destination and
independent missed-backup monitoring are **not yet configured**. A process inside
the application cannot alert if the entire app is down. Treat monitoring as an
activation follow-up, not as something provided by the daily scheduler.

## Scratch restore

Keep the snapshot ID from a successful run. Restore into a new scratch directory:

```sh
gosu www-data /app/bin/backup restore <snapshot-id>
```

This verifies restored bytes and prints a unique directory below `/app/var/backup`.
It never overwrites `/app/var/uploads` or imports a database. Locate `database.dump`,
`uploads/`, and `manifest.json` within that directory. Use PostgreSQL 16 tools to
import the dump into a **new disposable database** with `pg_restore --no-owner
--no-acl --exit-on-error`. Supply its connection through isolated `PG*` variables;
do not reuse production `DATABASE_URL` for restoration.

Point a disposable application at that database and the restored uploads. Validate
the Doctrine schema, compare users/dogs/treatments/media counts, run the read-only
media audit, and sample authenticated image/video downloads. Never pass
`--delete-orphans`. Record snapshot ID, times, counts, results, and operator before
removing scratch resources. The existing deployment runbook describes the checks.

For disaster recovery without the running app, use the same image/tools in a
disposable container, supply the R2 credentials and saved encryption password,
and restore there. The bucket is not a replacement for keeping the password and
repository access recoverable outside the lost Railway project.

## Local checks

```sh
python3 -B -m unittest discover -s tests/Operations -v
php bin/phpunit tests/EventSubscriber/BackupWriteLockSubscriberTest.php
bin/check
docker build --target production -t dogs-diary:r2-backup-test .
```

Unit tests cover configuration, credential handling, capture failures, paired
contents, lock coordination, upload cleanup, retention preview, stale status, and
scheduler timing. They do not contact production or prove R2 access. Record real
R2 and restore-drill results separately.

Local verification on 2026-10-06: `bin/check --local` passed (92 PHP tests,
808 assertions; 4 JavaScript tests; 11 Python tests), and the production image
build passed. Inside that image, the configured migrations and schema validation
passed against a new isolated PostgreSQL 16 database. `tests/Operations/restore_smoke.py`
also passed against disposable PostgreSQL 16: a synthetic table and upload were
backed up to a local encrypted restic repository, fully checked, and restored with
matching rows and file bytes. This is not an R2 connectivity test or a production
application restore drill.

The smoke script requires a disposable PostgreSQL service at `127.0.0.1:5432`,
user `postgres`, password `backup-test-only`, and absent databases `backup_source`
and `backup_restored`. Run it only inside the built image sharing that isolated
service's network namespace, with `python3 -B /app/tests/Operations/restore_smoke.py`.
It creates these databases and does not delete them; discard the test container
afterwards. Do not point it at an existing development or production database.

References: [R2 credentials](https://developers.cloudflare.com/r2/api/tokens/),
[restic repositories](https://restic.readthedocs.io/en/stable/030_preparing_a_new_repo.html),
[restic retention](https://restic.readthedocs.io/en/stable/060_forget.html),
[PostgreSQL Debian packages](https://www.postgresql.org/download/linux/debian/).
