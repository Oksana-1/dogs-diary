<?php

namespace App\Application\Dog\Data;

final readonly class UpdateDogData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $birthDate,
        public ?string $status,
        public ?int $weight,
        public ?int $height,
    ) {
    }
}
