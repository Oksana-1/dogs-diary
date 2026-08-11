<?php

namespace App\View;

use App\Application\Media\MediaStorageInterface;
use App\Entity\TreatmentMedia;

final readonly class TreatmentMediaView extends AbstractView
{
    public function __construct(
        private TreatmentMedia $media,
        private MediaStorageInterface $storage,
    ) {
    }

    public static function from(TreatmentMedia $media, MediaStorageInterface $storage): self
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
            'type' => 'image',
            'url' => $this->storage->publicUrl($this->media->getStorageKey()),
            'originalName' => $this->media->getOriginalName(),
            'mimeType' => $this->media->getMimeType(),
            'sizeBytes' => $this->media->getSizeBytes(),
            'width' => $this->media->getWidth(),
            'height' => $this->media->getHeight(),
            'position' => $this->media->getPosition(),
            'createdAt' => $this->media->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
