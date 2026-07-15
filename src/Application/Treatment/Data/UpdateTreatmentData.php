<?php

namespace App\Application\Treatment\Data;

use App\Enum\TreatmentTypeEnum;

final readonly class UpdateTreatmentData
{
    /**
     * @param array<int, TreatmentTypeEnum> $types
     */
    public function __construct(
        public int $id,
        public array $types,
        public string $productName,
        public string $treatmentDate,
        public ?string $dueDate = null,
        public ?string $note = null,
    ) {
    }
}
