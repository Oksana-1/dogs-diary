<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

final class DatabaseSessionHandlerFactory
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function create(): PdoSessionHandler
    {
        // Keep the session transaction separate from ORM transactions. DBAL also
        // preserves sslmode, which PdoSessionHandler's URL parser discards.
        $connection = DriverManager::getConnection($this->connection->getParams());
        $pdo = $connection->getNativeConnection();
        if (!$pdo instanceof \PDO) {
            throw new \LogicException('Database sessions require a PDO driver.');
        }

        return new PdoSessionHandler($pdo, [
            'lock_mode' => PdoSessionHandler::LOCK_TRANSACTIONAL,
            'ttl' => 1440,
        ]);
    }
}
