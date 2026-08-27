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

    public function exists(string $storageKey): bool;

    /**
     * Returns the absolute path only for an existing, valid storage key.
     */
    public function resolvePath(string $storageKey): ?string;

    /**
     * @return string[]
     */
    public function allStorageKeys(): array;
}
