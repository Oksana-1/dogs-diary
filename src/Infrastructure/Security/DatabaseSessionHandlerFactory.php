<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class DatabaseSessionHandlerFactory
{
    public function __construct(private readonly Connection $connection, #[Autowire(service: 'monolog.logger.runtime')] private readonly LoggerInterface $logger = new NullLogger())
    {
    }

    public function create(): TimedSessionHandler
    {
        // Keep the session transaction separate from ORM transactions. DBAL also
        // preserves sslmode, which PdoSessionHandler's URL parser discards.
        $connection = DriverManager::getConnection($this->connection->getParams());
        $pdo = $connection->getNativeConnection();
        if (!$pdo instanceof \PDO) {
            throw new \LogicException('Database sessions require a PDO driver.');
        }

        if ('pgsql' === $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
            $pdo = new BoundedSessionPdo($pdo);
        }

        return new TimedSessionHandler($pdo, $this->logger);
    }
}
