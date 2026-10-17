<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Doctrine\DBAL\Connection;
use Symfony\Component\RateLimiter\LimiterStateInterface;
use Symfony\Component\RateLimiter\Storage\StorageInterface;

/** Database errors propagate: unavailable storage must not reset login budgets. */
final class DatabaseRateLimiterStorage implements StorageInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function save(LimiterStateInterface $limiterState): void
    {
        $ttl = $limiterState->getExpirationTime();
        $this->connection->executeStatement(
            'INSERT INTO security_rate_limits (id, state, expires_at) VALUES (?, ?, ?) '
            .'ON CONFLICT (id) DO UPDATE SET state = EXCLUDED.state, expires_at = EXCLUDED.expires_at',
            [hash('sha256', $limiterState->getId()), base64_encode(serialize($limiterState)), null === $ttl ? null : time() + (int) ceil($ttl)],
        );

        if (1 === random_int(1, 100)) {
            $this->connection->executeStatement('DELETE FROM security_rate_limits WHERE expires_at <= ?', [time()]);
        }
    }

    public function fetch(string $limiterStateId): ?LimiterStateInterface
    {
        $row = $this->connection->fetchAssociative(
            'SELECT state, expires_at FROM security_rate_limits WHERE id = ?',
            [hash('sha256', $limiterStateId)],
        );
        if (false === $row || (null !== $row['expires_at'] && (int) $row['expires_at'] <= time())) {
            return null;
        }

        $state = unserialize(base64_decode($row['state'], true), ['allowed_classes' => [
            \Symfony\Component\RateLimiter\Policy\Window::class,
        ]]);
        if (!$state instanceof LimiterStateInterface) {
            throw new \UnexpectedValueException('Invalid stored login rate-limit state.');
        }

        return $state;
    }

    public function delete(string $limiterStateId): void
    {
        $this->connection->executeStatement('DELETE FROM security_rate_limits WHERE id = ?', [hash('sha256', $limiterStateId)]);
    }
}
