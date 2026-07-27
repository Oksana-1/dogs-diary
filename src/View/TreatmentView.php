<?php

namespace App\View;

use App\Entity\Treatment;

final readonly class TreatmentView extends AbstractView
{
    public function __construct(private Treatment $treatment) {}

    public static function from(Treatment $treatment): self
    {
        return new self($treatment);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->treatment->getId(),
            'dogId' => $this->treatment->getDog()?->getId(),
            'types' => array_map(static fn ($type) => $type->value, $this->treatment->getType()),
            'productName' => $this->treatment->getProductName(),
            'treatmentDate' => $this->treatment->getTreatmentDate()?->format('Y-m-d'),
            'dueDate' => $this->treatment->getDueDate()?->format('Y-m-d'),
            'note' => $this->treatment->getNote(),
        ];
    }
}
