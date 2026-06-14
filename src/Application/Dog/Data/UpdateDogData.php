<?php

namespace App\Application\Dog\Data;

final readonly class UpdateDogData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $birthDate,
        public ?string $status = null,
        public ?string $avatar = null,
        public ?int $weight = null,
        public ?int $height = null,
    ) {
    }
}
