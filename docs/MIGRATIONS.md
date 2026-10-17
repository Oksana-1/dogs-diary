# Squashed migration baseline

`Version20261013180000` replaces the five migrations dated August 7–October 13,
2026. Old sources remain recoverable from Git before this change. The SQL fixture
in `tests/Migrations/fixtures/monday-schema.sql` preserves the exact four-version
schema for regression checks; it is not a runnable application migration.

Supported starting states:

- Empty database: creates the complete current schema, including media CHECK
  constraints, ownership, reset requests, sessions, rate limits and locks.
- Monday's backup: requires exactly the four known August migration versions
  and all domain tables; adds the three security-state tables.
- Pre-squash current database: requires all five known versions and all tables;
  preserves both application data and existing security state.

Both legacy paths delete only the five known migration-history rows. Doctrine
records the new baseline in the same transaction. No application table is
dropped or truncated. Incomplete/unknown histories, missing tables and partial
security schemas abort before SQL is queued. As with other migrations, matching
version metadata assumes no manual column/constraint changes; validate the
schema afterward. Baseline rollback is deliberately refused to protect data.

## Restoring Monday's archive

Keep `~/DogsDiaryBackups/2026-10-12/database.dump` and `uploads.tar.gz` unchanged.
After an explicitly authorized restore into an empty local target (never restore
over the running development DB), point the app to that target, keep mail local,
and run:

```sh
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:migrations:status
php bin/console doctrine:schema:validate
php bin/console app:media:audit
```

An initial warning about four or five unregistered historical migrations is
expected during conversion. After success, status must show the latest available
version, zero unavailable and zero pending migrations. The current chain includes
the baseline and the R2 upload-ticket migration `Version20261015090000`.
Subsequent runs
are no-ops. Do not use `version --add --all` or `rollup` to bypass conversion:
Monday's backup still needs the security tables created. Older partial backups
must first be upgraded using their matching historical code; this baseline does
not guess missing history. Do not downgrade code independently of DB metadata.

For this personal project, the user explicitly requested preserving the original
production users and password hashes locally. Do not anonymize or reset the
account during restore. Keep email routed to local Mailpit and the restored
database/uploads private; never use this data in Preview.
See [the original-data restore record](operations/2026-10-14-local-original-restore.md).

## Verification

Run the configured checks:

```sh
docker compose exec -T app php tests/Migrations/verify_baseline.php
bin/check
```

The integration script uses random schemas and transaction rollback in the
existing PostgreSQL database. It never creates another database/volume and never
executes generated schema-update SQL. CI also executes real Doctrine migrations
against its empty PostgreSQL service before schema validation and these checks.

Historical baseline-conversion verification is recorded in
[13 October operations](operations/2026-10-13.md); the authorized hosted R2
migration is recorded in [15 October operations](operations/2026-10-15-neon-r2-migration.md).
A pre-conversion recovery dump remains private at
`~/DogsDiaryBackups/pre-squash-2026-10-13/database.dump`; preserve it and the
original database/media archives.
