<?php

namespace App\Application\Media;

use App\Entity\DogMedia;
use App\Entity\TreatmentMedia;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class MediaUrlGenerator
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function forDogMedia(DogMedia $media): string
    {
        return $this->urlGenerator->generate('app_api_dog_media_download', [
            'dogId' => $media->getDog()->getId(),
            'id' => $media->getId(),
        ]);
    }

    public function forTreatmentMedia(TreatmentMedia $media): string
    {
        $treatment = $media->getTreatment();

        return $this->urlGenerator->generate('app_api_treatment_media_download', [
            'dogId' => $treatment->getDog()?->getId(),
            'treatmentId' => $treatment->getId(),
            'id' => $media->getId(),
        ]);
    }
}
