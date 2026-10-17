<?php

namespace App\Application\Media;

use App\Enum\MediaOwnerTypeEnum;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

interface MediaStorageInterface
{
    public function store(
        MediaOwnerTypeEnum $ownerType,
        int $ownerId,
        UploadedFile $file,
        string $extension,
    ): string;

    public function delete(string $storageKey): void;

    public function exists(string $storageKey): bool;

    public function createResponse(string $storageKey, string $originalName, string $mimeType): ?Response;

    /**
     * @return string[]
     */
    public function allStorageKeys(): array;
}
