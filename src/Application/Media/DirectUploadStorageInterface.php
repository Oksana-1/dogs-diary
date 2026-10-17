<?php

namespace App\Application\Media;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface DirectUploadStorageInterface extends MediaStorageInterface
{
    public function createUploadUrl(string $uploadId, string $mimeType): string;

    public function downloadUpload(string $uploadId, string $originalName, string $mimeType, int $sizeBytes): UploadedFile;
}
