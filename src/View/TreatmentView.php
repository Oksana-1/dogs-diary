<?php

namespace App\View;

use App\Application\Media\MediaStorageInterface;
use App\Entity\Treatment;

final readonly class TreatmentView extends AbstractView
{
    public function __construct(
        private Treatment $treatment,
        private MediaStorageInterface $storage,
    ) {
    }

    public static function from(Treatment $treatment, MediaStorageInterface $storage): self
    {
        return new self($treatment, $storage);
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
                ? TreatmentMediaView::from($photo, $this->storage)->toArray()
                : null,
        ];
    }
}
