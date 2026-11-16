<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

final class TimedSessionHandler extends PdoSessionHandler
{
    public function __construct(\PDO $pdo, private readonly LoggerInterface $logger)
    {
        parent::__construct($pdo, ['lock_mode' => self::LOCK_TRANSACTIONAL, 'ttl' => 1440]);
    }

    public function read(#[\SensitiveParameter] string $sessionId): string|false
    {
        $started = hrtime(true);
        $outcome = 'error';
        try {
            $data = parent::read($sessionId);
            $outcome = 'success';

            return $data;
        } finally {
            $this->logger->info('Session read completed.', [
                'duration_ms' => round((hrtime(true) - $started) / 1e6, 2),
                'outcome' => $outcome,
            ]);
        }
    }
}
