<?php

namespace App\Application\Media\Exception;

final class MediaValidationException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 422,
        private readonly string $field = '',
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getField(): string
    {
        return $this->field;
    }
}
