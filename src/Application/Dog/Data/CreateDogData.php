<?php

namespace App\Application\Dog\Data;

final readonly class CreateDogData
{
    public function __construct(
        public string $name,
        public string $birthDate,
        public ?string $status = null,
        public ?string $avatar = null,
        public ?int $weight = null,
        public ?int $height = null,
    ) {
    }
}
