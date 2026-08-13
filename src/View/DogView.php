<?php

namespace App\View;

use App\Application\Media\MediaStorageInterface;
use App\Entity\Dog;

final readonly class DogView extends AbstractView
{
    public function __construct(
        private Dog $dog,
        private MediaStorageInterface $storage,
    ) {
    }

    public static function from(Dog $dog, MediaStorageInterface $storage): self
    {
        return new self($dog, $storage);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $thumbnail = $this->dog->getThumbnailMedia();
        $profileMedia = $this->dog->getProfileMedia();

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
                ? DogMediaView::from($thumbnail, $this->storage)->toArray()
                : null,
            'profileMedia' => $profileMedia
                ? DogMediaView::from($profileMedia, $this->storage)->toArray()
                : null,
            'treatments' => $this->dog->getTreatments()->map(
                fn ($treatment) => TreatmentView::from($treatment, $this->storage)->toArray()
            )->toArray(),
        ];
    }
}
