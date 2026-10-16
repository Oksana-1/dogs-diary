<?php

namespace App\Controller\Api\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class MediaUploadPayload
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $name,
        public string $mimeType,
        #[Assert\Positive]
        public int $sizeBytes,
    ) {
    }
}
