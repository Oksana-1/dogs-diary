<?php

namespace App\View;

use App\Application\Media\MediaUrlGenerator;
use App\Entity\Dog;

final readonly class DogSummaryView extends AbstractView
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
        $thumbnail = $this->dog->getThumbnailMedia();

        return [
            'id' => $this->dog->getId(),
            'name' => $this->dog->getName(),
            'birthDate' => $this->dog->getBirthDate()?->format('Y-m-d'),
            'gender' => $this->dog->getGender()?->value,
            'adoptDate' => $this->dog->getAdoptDate()?->format('Y-m-d'),
            'weight' => $this->dog->getWeight(),
            'height' => $this->dog->getHeight(),
            'status' => $this->dog->getStatus(),
            'thumbnail' => $thumbnail
                ? DogMediaView::from($thumbnail, $this->urlGenerator)->toArray()
                : null,
        ];
    }
}
