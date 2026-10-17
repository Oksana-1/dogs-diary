<?php

namespace App\View;

use App\Application\Media\MediaUrlGenerator;
use App\Entity\Dog;

final readonly class DogView extends AbstractView
{
    public function __construct(
        private Dog $dog,
        private MediaUrlGenerator $urlGenerator,
    ) {
    }

    public static function from(Dog $dog, MediaUrlGenerator $urlGenerator): self
    {
        return new self($dog, $urlGenerator);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $profileMedia = $this->dog->getProfileMedia();

        return [
            ...DogSummaryView::from($this->dog, $this->urlGenerator)->toArray(),
            'profileMedia' => $profileMedia
                ? DogMediaView::from($profileMedia, $this->urlGenerator)->toArray()
                : null,
            'treatments' => $this->dog->getTreatments()->map(
                fn ($treatment) => TreatmentView::from($treatment, $this->urlGenerator)->toArray()
            )->toArray(),
        ];
    }
}
