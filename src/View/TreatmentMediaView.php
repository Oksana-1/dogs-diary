<?php

namespace App\View;

use App\Application\Media\MediaUrlGenerator;
use App\Entity\TreatmentMedia;

final readonly class TreatmentMediaView extends AbstractView
{
    public function __construct(
        private TreatmentMedia $media,
        private MediaUrlGenerator $urlGenerator,
    ) {
    }

    public static function from(TreatmentMedia $media, MediaUrlGenerator $urlGenerator): self
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
            'type' => 'image',
            'url' => $this->urlGenerator->forTreatmentMedia($this->media),
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
