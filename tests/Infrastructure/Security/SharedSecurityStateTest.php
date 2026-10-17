<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Security;

use App\Infrastructure\Security\DatabaseRateLimiterStorage;
use App\Infrastructure\Security\DatabaseSessionHandlerFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\DoctrineDbalStore;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class SharedSecurityStateTest extends TestCase
{
    private string $path;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->path = tempnam(sys_get_temp_dir(), 'diary-security-');
        $this->connection = $this->connect();
        $this->connection->executeStatement('CREATE TABLE security_rate_limits (id VARCHAR(64) PRIMARY KEY, state TEXT NOT NULL, expires_at INT)');
        (new DoctrineDbalStore($this->connection))->createTable();
    }

    protected function tearDown(): void
    {
        $this->connection->close();
        unlink($this->path);
    }

    public function testLoginBudgetSurvivesNewConnectionsAndIsResetAcrossInstances(): void
    {
        $first = $this->limiter($this->connection);
        $other = $this->connect();
        $second = $this->limiter($other);

        for ($attempt = 0; $attempt < 5; ++$attempt) {
            self::assertTrue(($attempt % 2 ? $first : $second)->create('same-user-ip')->consume()->isAccepted());
        }
        $other->close();
        self::assertFalse($this->limiter($this->connect())->create('same-user-ip')->consume()->isAccepted());
        self::assertTrue($first->create('other-user-ip')->consume()->isAccepted());
        $second->create('same-user-ip')->reset();
        self::assertTrue($first->create('same-user-ip')->consume()->isAccepted());
    }

    public function testExpiredBudgetCanBeUsedAgain(): void
    {
        $limiter = $this->limiter($this->connection)->create('expired');
        self::assertTrue($limiter->consume(5)->isAccepted());
        $this->connection->executeStatement('UPDATE security_rate_limits SET expires_at = ?', [time() - 1]);
        self::assertTrue($this->limiter($this->connect())->create('expired')->consume()->isAccepted());
    }

    public function testMissingStorageFailsClosed(): void
    {
        $this->connection->executeStatement('DROP TABLE security_rate_limits');
        $this->expectException(\Doctrine\DBAL\Exception::class);
        $this->limiter($this->connection)->create('unavailable')->consume();
    }

    public function testSessionSurvivesNewHandlerAndLogoutDeletesSharedState(): void
    {
        $first = (new DatabaseSessionHandlerFactory($this->connection))->create();
        $first->createTable();
        $first->open('', 'TESTSESSID');
        $first->read('session-one');
        self::assertTrue($first->write('session-one', "login|user-1\0csrf|token"));
        $first->close();

        $second = (new DatabaseSessionHandlerFactory($this->connect()))->create();
        $second->open('', 'TESTSESSID');
        self::assertSame("login|user-1\0csrf|token", $second->read('session-one'));
        self::assertTrue($second->destroy('session-one'));
        $second->close();
        $first->open('', 'TESTSESSID');
        self::assertSame('', $first->read('session-one'));
        $first->close();
    }

    public function testSessionDoesNotCommitTheApplicationTransactionAndExpires(): void
    {
        $handler = (new DatabaseSessionHandlerFactory($this->connection))->create();
        $handler->createTable();
        $this->connection->beginTransaction();
        $handler->open('', 'TESTSESSID');
        $handler->read('expiring-session');
        $handler->write('expiring-session', 'data');
        $handler->close();
        self::assertTrue($this->connection->isTransactionActive());
        $this->connection->rollBack();
        $this->connection->executeStatement('UPDATE sessions SET sess_lifetime = 0');
        $handler->open('', 'TESTSESSID');
        self::assertSame('', $handler->read('expiring-session'));
        $handler->close();
    }

    private function connect(): Connection
    {
        return DriverManager::getConnection(['driver' => 'pdo_sqlite', 'path' => $this->path]);
    }

    private function limiter(Connection $connection): RateLimiterFactory
    {
        return new RateLimiterFactory(
            ['id' => 'login-test', 'policy' => 'fixed_window', 'limit' => 5, 'interval' => '1 minute'],
            new DatabaseRateLimiterStorage($connection),
            new LockFactory(new DoctrineDbalStore($connection)),
        );
    }
}
