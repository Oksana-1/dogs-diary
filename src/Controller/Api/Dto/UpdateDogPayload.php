<?php

namespace App\Controller\Api\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateDogPayload
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 100)]
        public string $name,
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $birthDate,
        #[Assert\Length(max: 100)]
        public ?string $status = null,
        #[Assert\Length(max: 255)]
        public ?string $avatar = null,
        #[Assert\Positive]
        public ?int $weight = null,
        #[Assert\Positive]
        public ?int $height = null,
    ) {
    }
}
