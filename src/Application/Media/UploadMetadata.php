<?php

namespace App\Application\Media;

use App\Enum\MediaTypeEnum;

final readonly class UploadMetadata
{
    public function __construct(
        public MediaTypeEnum $type,
        public string $mimeType,
        public string $extension,
        public string $originalName,
        public int $sizeBytes,
        public ?int $width = null,
        public ?int $height = null,
    ) {
    }
}
