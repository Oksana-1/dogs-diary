<?php

namespace App\Controller\Api\Dto;

use App\Enum\TreatmentTypeEnum;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateTreatmentPayload
{
    public function __construct(
        /** @var array<int, string> */
        #[Assert\Count(min: 1)]
        #[Assert\All([
            new Assert\Choice(callback: [TreatmentTypeEnum::class, 'values']),
        ])]
        public array $types,
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $productName,
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $treatmentDate,
        #[Assert\Date]
        public ?string $dueDate = null,
        #[Assert\Length(max: 255)]
        public ?string $note = null,
    ) {
    }
}
