# Continuous integration

GitHub Actions runs `.github/workflows/ci.yaml` for every push and pull request,
and it can also be started manually. The workflow has read-only repository
permissions and cancels an older run for the same branch when a newer commit is
pushed.

## Local quality checks

Run the quality checks from any working directory using `bin/check` (with the
appropriate path to the script). From the repository root:

```bash
bin/check                 # All quality checks
bin/check php             # PHP quality checks only
bin/check frontend        # Existing Node.js transport tests only
bin/check --local         # All checks using host PHP/Composer instead of Docker
bin/check php --local     # PHP checks using host PHP/Composer
```

By default, PHP commands run in the already-running Compose `app` container;
Node.js runs on the host. Start the development stack as described in the README
before running PHP checks. `--local` requires host PHP, Composer, the required PHP
extensions (including SQLite), and installed Composer dependencies. CI uses PHP
8.4 and Node.js 24. The script does not install dependencies or start containers.

The full `bin/check` run also executes the backup workflow unit tests using host
Python 3 (`python3 -B -m unittest discover -s tests/Operations -v`). These tests
require no additional Python packages and never access production or R2.

The PHP checks reuse the quality-job commands below, excluding dependency
installation. They explicitly set `APP_ENV=test`, an in-memory SQLite database,
disabled email delivery, and test application settings, overriding inherited
development values. Symfony and PHPUnit may write test caches. The dependency
audit requires network access.

The script prints each command, stops at the first failure, and returns that
command's nonzero exit code. Remaining checks are reported as not run. Invalid
arguments return 2; missing host tools return 127. A successful run covers only
the selected scope, not the PostgreSQL migration/schema job below. That job
continues to run separately against a clean PostgreSQL service in CI.

## Quality job

The quality job uses PHP 8.4, Node.js 24, and the repository's isolated SQLite
test configuration. It runs:

```bash
composer validate --strict --no-check-publish
composer install --prefer-dist --no-interaction --no-progress
composer audit --locked --abandoned=fail --no-interaction
php bin/phpunit
node --test tests/JavaScript/FetchClient.test.mjs
python3 -B -m unittest discover -s tests/Operations -v
vendor/bin/php-cs-fixer fix --dry-run --diff
php bin/console lint:yaml config --parse-tags
php bin/console lint:twig templates
php bin/console lint:container
```

The Node command is the canonical frontend command documented in
`tests/JavaScript/TESTING.md`; no unconfigured package runner is used.

## Database job

The database job starts a clean PostgreSQL 16 service, applies the complete
Doctrine migration chain, and then validates that the resulting database schema
matches the current entity mapping:

```bash
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
```

Both jobs must pass before a commit is considered deployable. Protect `main` in
GitHub and require the `Tests and static checks` and `PostgreSQL migrations and
schema` checks before merging.
