<?php

namespace App\Controller\Api\Dto;

use App\Enum\GenderTypeEnum;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateDogPayload
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 100)]
        public string $name,
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $birthDate,
        #[Assert\Choice(callback: [GenderTypeEnum::class, 'values'])]
        public ?string $gender = null,
        #[Assert\Date]
        public ?string $adoptDate = null,
        #[Assert\Length(max: 100)]
        public ?string $status = null,
        #[Assert\Positive]
        public ?int $weight = null,
        #[Assert\Positive]
        public ?int $height = null,
    ) {
    }
}
