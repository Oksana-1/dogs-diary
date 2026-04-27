<?php

namespace App\Application\Dog\Data;

final readonly class CreateDogData
{
    public function __construct(
        public string $name,
        public string $birthDate,
        public ?string $status,
        public ?int $weight,
        public ?int $height,
    ) {
    }
}
