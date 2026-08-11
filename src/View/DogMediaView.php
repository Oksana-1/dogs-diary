<?php

namespace App\View;

use App\Application\Media\MediaStorageInterface;
use App\Entity\DogMedia;

final readonly class DogMediaView extends AbstractView
{
    public function __construct(
        private DogMedia $media,
        private MediaStorageInterface $storage,
    ) {
    }

    public static function from(DogMedia $media, MediaStorageInterface $storage): self
    {
        return new self($media, $storage);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->media->getId(),
            'type' => $this->media->getType()->value,
            'url' => $this->storage->publicUrl($this->media->getStorageKey()),
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
