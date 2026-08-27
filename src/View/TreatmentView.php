<?php

namespace App\View;

use App\Application\Media\MediaUrlGenerator;
use App\Entity\Treatment;

final readonly class TreatmentView extends AbstractView
{
    public function __construct(
        private Treatment $treatment,
        private MediaUrlGenerator $urlGenerator,
    ) {
    }

    public static function from(Treatment $treatment, MediaUrlGenerator $urlGenerator): self
    {
        return new self($treatment, $urlGenerator);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $photo = $this->treatment->getPhoto();

        return [
            'id' => $this->treatment->getId(),
            'dogId' => $this->treatment->getDog()?->getId(),
            'types' => array_map(static fn ($type) => $type->value, $this->treatment->getType()),
            'productName' => $this->treatment->getProductName(),
            'treatmentDate' => $this->treatment->getTreatmentDate()?->format('Y-m-d'),
            'dueDate' => $this->treatment->getDueDate()?->format('Y-m-d'),
            'note' => $this->treatment->getNote(),
            'photo' => $photo
                ? TreatmentMediaView::from($photo, $this->urlGenerator)->toArray()
                : null,
        ];
    }
}
