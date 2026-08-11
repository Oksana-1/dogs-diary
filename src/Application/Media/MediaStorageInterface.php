<?php

namespace App\Application\Media;

use App\Enum\MediaOwnerTypeEnum;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface MediaStorageInterface
{
    public function store(
        MediaOwnerTypeEnum $ownerType,
        int $ownerId,
        UploadedFile $file,
        string $extension,
    ): string;

    public function delete(string $storageKey): void;

    public function publicUrl(string $storageKey): string;

    public function exists(string $storageKey): bool;

    /**
     * @return string[]
     */
    public function allStorageKeys(): array;
}
