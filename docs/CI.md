# Continuous integration

GitHub Actions runs `.github/workflows/ci.yaml` for every push and pull request,
and it can also be started manually. The workflow has read-only repository
permissions and cancels an older run for the same branch when a newer commit is
pushed.

## Quality job

The quality job uses PHP 8.4, Node.js 24, and the repository's isolated SQLite
test configuration. It runs:

```bash
composer validate --strict --no-check-publish
composer install --prefer-dist --no-interaction --no-progress
composer audit --locked --abandoned=fail --no-interaction
php bin/phpunit
node --test tests/JavaScript/FetchClient.test.mjs
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
