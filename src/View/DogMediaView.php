<?php

namespace App\View;

use App\Application\Media\MediaUrlGenerator;
use App\Entity\DogMedia;

final readonly class DogMediaView extends AbstractView
{
    public function __construct(
        private DogMedia $media,
        private MediaUrlGenerator $urlGenerator,
    ) {
    }

    public static function from(DogMedia $media, MediaUrlGenerator $urlGenerator): self
    {
        return new self($media, $urlGenerator);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->media->getId(),
            'type' => $this->media->getType()->value,
            'url' => $this->urlGenerator->forDogMedia($this->media),
            'originalName' => $this->media->getOriginalName(),
            'mimeType' => $this->media->getMimeType(),
            'sizeBytes' => $this->media->getSizeBytes(),
            'width' => $this->media->getWidth(),
            'height' => $this->media->getHeight(),
            'isThumbnail' => $this->media->isThumbnail(),
            'isProfile' => $this->media->isProfile(),
            'createdAt' => $this->media->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
