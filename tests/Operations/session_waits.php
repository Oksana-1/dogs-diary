<?php

declare(strict_types=1);

use App\Infrastructure\Security\BoundedSessionPdo;
use App\Infrastructure\Security\DatabaseSessionHandlerFactory;

require '/app/vendor/autoload.php';
// Read the application connection configuration, but refuse anything except the disposable smoke DB.
$kernel = new App\Kernel('prod', false);
$kernel->boot();
$connection = $kernel->getContainer()->get('doctrine')->getConnection();
if ('dogs_diary_runtime_test' !== $connection->fetchOne('SELECT current_database()')) {
    throw new RuntimeException('Refusing a non-test database.');
}
$id = 'bounded-wait-'.bin2hex(random_bytes(8));
$first = (new DatabaseSessionHandlerFactory($connection))->create();
$second = (new DatabaseSessionHandlerFactory($connection))->create();
$first->read($id);
$first->write($id, 'synthetic');
$first->close();
$first->read($id);
try {
    $started = microtime(true);
    try {
        $second->read($id);
        throw new RuntimeException('Contended session did not time out.');
    } catch (PDOException $error) {
        $elapsed = microtime(true) - $started;
        if ('55P03' !== $error->getCode() || $elapsed < 2.5 || $elapsed > 6) {
            throw $error;
        }
        printf("PASS: contended session failed closed after %.2fs\n", $elapsed);
    }
} finally {
    $first->close();
    $second->close();
}
if ('synthetic' !== $second->read($id)) {
    throw new RuntimeException('Timeout corrupted session data.');
}
$second->destroy($id);
$second->close();
$native = $connection->getNativeConnection();
$before = $native->query('SHOW lock_timeout')->fetchColumn();
$pdo = new BoundedSessionPdo($native);
$pdo->beginTransaction();
if ('3s' !== $pdo->query('SHOW lock_timeout')->fetchColumn() || '10s' !== $pdo->query('SHOW statement_timeout')->fetchColumn()) {
    throw new RuntimeException('Transaction budgets were not applied.');
}
try {
    $pdo->query('SELECT pg_sleep(11)');
    throw new RuntimeException('Statement did not time out.');
} catch (PDOException $error) {
    if ('57014' !== $error->getCode()) {
        throw $error;
    }
} finally {
    $pdo->rollBack();
}
if ($before !== $native->query('SHOW lock_timeout')->fetchColumn()) {
    throw new RuntimeException('Transaction timeout leaked after rollback.');
}
echo "PASS: statement timeout, rollback, session recovery and transaction-local settings\n";
