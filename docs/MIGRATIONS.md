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
over the running development DB), point the app to that target, keep mail local
and the backup scheduler disabled, and run:

```sh
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:migrations:status
php bin/console doctrine:schema:validate
php bin/console app:media:audit
```

An initial warning about four or five unregistered historical migrations is
expected during conversion. After success, status must show one executed,
one available, zero unavailable and zero pending migrations. Subsequent runs
are no-ops. Do not use `version --add --all` or `rollup` to bypass conversion:
Monday's backup still needs the security tables created. Older partial backups
must first be upgraded using their matching historical code; this baseline does
not guess missing history. Do not downgrade code independently of DB metadata.

Restoring production-derived archives also requires the existing local-account
sanitization and private uploads handling documented in the Monday record.

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

The local conversion on October 13 was verified with one executed baseline,
schema validation, media audit, and unchanged fingerprints for all seven domain
tables. A pre-conversion recovery dump is retained privately at
`~/DogsDiaryBackups/pre-squash-2026-10-13/database.dump`; its archive contents
were readable, but a separate restore was not performed. Monday's original
archive is untouched. This change does not migrate remote production.

Observed local checks: `bin/check` passed (98 PHP tests / 831 assertions,
4 JavaScript tests, 11 Python tests, Composer audit/validation and all linters).
`docker compose exec -T app php tests/Migrations/verify_baseline.php` passed all
eight scenarios. Migration repeat was a no-op; schema validation and media audit
passed; `/healthz` returned 204 and `/login` 200. Zero `baseline_check_*` schemas
remained. The fresh-schema and Monday cases used the checked-in legacy SQL
fixture, not another restore of private data. The remote CI job was not run.

During development, the integration comparison initially included public tables
outside its temporary schema; it failed without executing those proposed schema
changes. The comparison is now restricted to the test schema. An initial
multi-path formatter invocation failed for missing `--config`; rerunning with
`.php-cs-fixer.dist.php` succeeded, followed by the full checks above.
