<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

/** PDO adapter used only by Symfony's session handler, never by the ORM. */
final class BoundedSessionPdo extends \PDO
{
    public function __construct(private readonly \PDO $connection)
    {
    }

    public function beginTransaction(): bool
    {
        $result = $this->connection->beginTransaction();
        try {
            $this->connection->exec("SET LOCAL lock_timeout = '3s'");
            $this->connection->exec("SET LOCAL statement_timeout = '10s'");
        } catch (\Throwable $error) {
            $this->connection->rollBack();
            throw $error;
        }

        return $result;
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
    }

    public function getAttribute(int $attribute): mixed
    {
        return $this->connection->getAttribute($attribute);
    }

    public function setAttribute(int $attribute, mixed $value): bool
    {
        return $this->connection->setAttribute($attribute, $value);
    }

    public function prepare(string $query, array $options = []): \PDOStatement|false
    {
        return $this->connection->prepare($query, $options);
    }

    public function exec(string $statement): int|false
    {
        return $this->connection->exec($statement);
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): \PDOStatement|false
    {
        return $this->connection->query($query, $fetchMode, ...$fetchModeArgs);
    }

    public function quote(string $string, int $type = \PDO::PARAM_STR): string|false
    {
        return $this->connection->quote($string, $type);
    }
}
