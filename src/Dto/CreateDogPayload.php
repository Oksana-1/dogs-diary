<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateDogPayload
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 100)]
        public string $name,
        #[Assert\Length(max: 100)]
        public ?string $status,
        #[Assert\Positive]
        public ?int $weight,
        #[Assert\Positive]
        public ?int $height,
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $birthDate,
    ) {
    }
}
