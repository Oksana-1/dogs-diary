# Runtime wait fix: local verification, 2026-11-16

Scope: bounded private-media metadata lookups, releasing sessions after normal
security persistence and before storage lookup, transaction-local PostgreSQL
session query/lock timeouts, and duration/outcome logging to existing stderr.
No deployment, push, paid service, production database access or dependency update.

Base: `4de9bde` (locally known `origin/main`). Branch: `fix/bounded-runtime-waits`.
Separate worktree: `/tmp/dogs-diary-runtime-fix`; the original translations branch
and its working directory were left unchanged.

## Passed checks

- `composer install --no-interaction --prefer-dist --no-scripts` (with temporary
  Composer cache): dependencies installed from the unchanged lock file.
- `APP_ENV=test DATABASE_URL='sqlite:///:memory:' php bin/console importmap:install`:
  pinned vendor assets installed for page-rendering tests.
- PHP tests used only in-memory SQLite and disabled mail:

  ```sh
  APP_ENV=test DATABASE_URL='sqlite:///:memory:' MAILER_DSN='null://null' \
    APP_BASE_URL=http://localhost MAILER_FROM_ADDRESS=no-reply@dogs-diary.test \
    MAILER_FROM_NAME='Dogs Diary' APP_SECRET=dogs-diary-check-only-secret \
    php bin/phpunit
  ```

  Result: **135 tests, 1151 assertions**. Includes private/foreign/anonymous media
  access, byte ranges, direct upload validation/replay/failure behavior, closed
  session at storage access, continued authentication, concurrent logout not
  being overwritten, and one HTTP attempt for a failed metadata lookup despite
  the S3 client's normal retry budget.
- `vendor/bin/php-cs-fixer fix --dry-run --diff`: no formatting differences.
- `php bin/console lint:yaml config --parse-tags`: 22 files valid.
- `php bin/console lint:twig templates`: 16 files valid.
- `php bin/console lint:container`: valid injection types. Console lint checks used
  the isolated test environment above.
- `python3 -B -m unittest discover -s tests/Operations -v`: 7 tests passed.
- `docker build --platform linux/amd64 -f Dockerfile.vercel -t dogs-diary:runtime-wait-fix .`:
  local runtime image built successfully.
- On a newly created, disposable `dogs_diary_runtime_test` PostgreSQL 18.6 database:
  `php bin/console doctrine:migrations:migrate --no-interaction`,
  `php bin/console doctrine:schema:validate`, and
  `php tests/Migrations/verify_baseline.php`: all passed, including the baseline's
  eight fresh/legacy/rejection scenarios.
- `python3 -B tests/Operations/shared_state_smoke.py` against the two newly created
  isolated runtime containers: passed CSRF, login/logout, password reset,
  persistence after restart, expiry and concurrent login throttling. The added
  `session_waits.php` scenario measured a lock timeout at **3.01 seconds**, verified
  data recovery, a statement timeout and rollback of transaction-local settings.
  Containers, disposable database and network were removed afterward.
- `git diff --check`: passed.

## Failures and limits

`bin/check php --local` fails at `composer audit --locked --abandoned=fail --no-interaction`:
**19 advisories affecting 12 packages in the unchanged lock file**. Composer
validation passes; the script stops at audit. Subsequent configured PHP checks
were run separately as listed above. The quality gate was not weakened. This
change is not declared deployable with that audit failure.

Intermediate checks found missing vendor assets in the new worktree and a
production lazy-proxy type mismatch after introducing the timed session handler.
Both were corrected and affected checks rerun. Initial dependency installation
also encountered sandbox DNS/cache restrictions and an incomplete archive; the
completed installation above succeeded.

Frontend tests were not run: no frontend implementation or tests were changed.
Real R2 operations and hosted Vercel execution were not run. SDK transport tests
use synthetic responses; no resource saving for the historical incident is
claimed. SQL budgets apply to session transactions, not ORM/migration queries,
connection establishment, or external connection-pool queueing. Upload transfer
budgets and owner-scoped authorization remain covered by the existing tests.
