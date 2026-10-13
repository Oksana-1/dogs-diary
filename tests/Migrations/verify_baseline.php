<?php

declare(strict_types=1);

use App\Kernel;
use Doctrine\Migrations\Exception\AbortMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use Doctrine\ORM\Tools\SchemaValidator;
use DoctrineMigrations\Version20261013180000;
use Psr\Log\NullLogger;

require dirname(__DIR__, 2).'/vendor/autoload.php';
(new Symfony\Component\Dotenv\Dotenv())->bootEnv(dirname(__DIR__, 2).'/.env');
require dirname(__DIR__, 2).'/migrations/Version20261013180000.php';

// Each scenario creates only a random schema inside a transaction. No existing
// table is addressed, and rollback removes all test DDL/data even on failure.
$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', false);
$kernel->boot();
$manager = $kernel->getContainer()->get('doctrine')->getManager();
$connection = $manager->getConnection();
// PostgreSQL introspection also lists explicitly qualified tables from other
// schemas. Compare only this scenario's unqualified search_path tables.
$testFilter = static fn (string $name): bool => !str_contains($name, '.');
$connection->getConfiguration()->setSchemaAssetsFilter($testFilter);
if (!$connection->getDatabasePlatform() instanceof Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
    throw new RuntimeException('This integration check requires PostgreSQL.');
}
$legacy = array_map(static fn (string $version): string => 'DoctrineMigrations\\Version'.$version, [
    '20260807150000', '20260813180000', '20260819145346', '20260824193000', '20261013120000',
]);
$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

foreach (['fresh', 'monday', 'current', 'incomplete-history', 'unknown-history', 'missing-table', 'partial-security', 'untracked-schema'] as $scenario) {
    $connection->beginTransaction();
    try {
        $namespace = 'baseline_check_'.bin2hex(random_bytes(8));
        $connection->executeStatement('CREATE SCHEMA '.$connection->quoteIdentifier($namespace));
        $connection->executeStatement('SET LOCAL search_path TO '.$connection->quoteIdentifier($namespace));
        $assert($namespace === $connection->fetchOne('SELECT current_schema()'), 'Test schema isolation failed.');
        $connection->executeStatement('CREATE TABLE doctrine_migration_versions (version VARCHAR(191) PRIMARY KEY, executed_at TIMESTAMP DEFAULT NULL, execution_time INT DEFAULT NULL)');

        if ('fresh' !== $scenario) {
            $connection->executeStatement(file_get_contents(__DIR__.'/fixtures/monday-schema.sql'));
            foreach (array_slice($legacy, 0, 4) as $version) {
                $connection->insert('doctrine_migration_versions', ['version' => $version]);
            }
            $connection->executeStatement("INSERT INTO dog (name, birth_date) VALUES ('Baseline test dog', '2020-01-01')");
        }
        if ('current' === $scenario) {
            // First upgrade the Monday fixture, then simulate the old fifth
            // version's completed state before testing adoption of that state.
            $setup = new Version20261013180000($connection, new NullLogger());
            $setup->up($connection->createSchemaManager()->introspectSchema());
            foreach ($setup->getSql() as $query) {
                $connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
            }
            foreach ($legacy as $version) {
                $connection->insert('doctrine_migration_versions', ['version' => $version]);
            }
            $connection->insert('security_rate_limits', ['id' => 'sentinel', 'state' => 'preserve-me']);
        }
        if ('incomplete-history' === $scenario) {
            $connection->delete('doctrine_migration_versions', ['version' => $legacy[3]]);
        } elseif ('unknown-history' === $scenario) {
            $connection->insert('doctrine_migration_versions', ['version' => 'UnknownVersion']);
        } elseif ('missing-table' === $scenario) {
            $connection->executeStatement('DROP TABLE reset_password_request');
        } elseif ('partial-security' === $scenario) {
            $connection->executeStatement('CREATE TABLE sessions (id INT)');
        } elseif ('untracked-schema' === $scenario) {
            $connection->executeStatement('DELETE FROM doctrine_migration_versions');
        }

        $migration = new Version20261013180000($connection, new NullLogger());
        $supported = in_array($scenario, ['fresh', 'monday', 'current'], true);
        try {
            $migration->up($connection->createSchemaManager()->introspectSchema());
            $assert($supported, 'Unsupported schema/history was accepted: '.$scenario);
        } catch (AbortMigration $exception) {
            $assert(!$supported, $exception->getMessage());
            $assert([] === $migration->getSql(), 'Rejected migration queued mutations.');
            echo 'PASS: '.$scenario." rejected without changes\n";
            continue;
        }
        foreach ($migration->getSql() as $query) {
            $connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }
        $assert(0 === (int) $connection->fetchOne('SELECT COUNT(*) FROM doctrine_migration_versions'), 'Legacy versions were not removed.');
        $connection->getConfiguration()->setSchemaAssetsFilter(static fn (string $name): bool => $testFilter($name) && 'doctrine_migration_versions' !== $name);
        $schemaChanges = (new SchemaValidator($manager))->getUpdateSchemaList();
        $connection->getConfiguration()->setSchemaAssetsFilter($testFilter);
        $assert([] === $schemaChanges, 'Baseline schema does not match the application mapping: '.implode('; ', $schemaChanges));
        $assert(('fresh' === $scenario ? 0 : 1) === (int) $connection->fetchOne('SELECT COUNT(*) FROM dog'), 'Existing dog data changed.');
        if ('current' === $scenario) {
            $assert('preserve-me' === $connection->fetchOne("SELECT state FROM security_rate_limits WHERE id = 'sentinel'"), 'Security state changed.');
        }
        try {
            $connection->executeStatement("INSERT INTO treatment_media (treatment_id, storage_key, original_name, mime_type, size_bytes, width, height, position, created_at) VALUES (1, 'x', 'x', 'image/png', 1, 1, 1, 2, NOW())");
            throw new RuntimeException('Treatment photo position constraint is missing.');
        } catch (Doctrine\DBAL\Exception\DriverException $exception) {
            $assert('23514' === $exception->getSQLState(), 'Expected a CHECK violation for the one-photo constraint.');
        }
        try {
            $migration->down(new Doctrine\DBAL\Schema\Schema());
            throw new RuntimeException('Destructive baseline rollback was allowed.');
        } catch (IrreversibleMigration) {
            // Expected: never drop user tables on rollback.
        }
        echo 'PASS: '.$scenario." schema, data preservation and rollback protection\n";
    } finally {
        $connection->rollBack();
    }
}
$kernel->shutdown();
