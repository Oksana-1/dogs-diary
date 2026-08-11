<?php

namespace App\Infrastructure\Media;

use App\Application\Media\MediaStorageInterface;
use App\Enum\MediaOwnerTypeEnum;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class LocalMediaStorage implements MediaStorageInterface
{
    private const STORAGE_KEY_PATTERN = '#^(?:dogs/[1-9]\d*/[a-f0-9]{32}\.(?:jpg|png|webp|mp4|webm)|treatments/[1-9]\d*/[a-f0-9]{32}\.(?:jpg|png|webp))$#';

    public function __construct(
        private string $uploadDirectory,
        private string $publicPrefix,
    ) {
    }

    public function store(
        MediaOwnerTypeEnum $ownerType,
        int $ownerId,
        UploadedFile $file,
        string $extension,
    ): string {
        if ($ownerId <= 0) {
            throw new \InvalidArgumentException('Media owner ID must be positive.');
        }

        $relativeDirectory = $ownerType->value.'/'.$ownerId;
        $targetDirectory = rtrim($this->uploadDirectory, '/').'/'.$relativeDirectory;
        $filename = bin2hex(random_bytes(16)).'.'.$extension;

        try {
            $file->move($targetDirectory, $filename);
        } catch (FileException $exception) {
            throw new \RuntimeException('Unable to store the uploaded media.', 0, $exception);
        }

        return $relativeDirectory.'/'.$filename;
    }

    public function delete(string $storageKey): void
    {
        if (!preg_match(self::STORAGE_KEY_PATTERN, $storageKey)) {
            throw new \InvalidArgumentException('Invalid media storage key.');
        }

        $path = rtrim($this->uploadDirectory, '/').'/'.$storageKey;
        if (is_file($path) && !unlink($path)) {
            throw new \RuntimeException('Unable to delete the stored media.');
        }
    }

    public function publicUrl(string $storageKey): string
    {
        $encodedKey = implode('/', array_map('rawurlencode', explode('/', $storageKey)));

        return rtrim($this->publicPrefix, '/').'/'.$encodedKey;
    }

    public function exists(string $storageKey): bool
    {
        return is_file(rtrim($this->uploadDirectory, '/').'/'.$storageKey);
    }

    public function allStorageKeys(): array
    {
        if (!is_dir($this->uploadDirectory)) {
            return [];
        }

        $keys = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->uploadDirectory, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || '.gitignore' === $file->getFilename()) {
                continue;
            }

            $key = str_replace('\\', '/', substr($file->getPathname(), strlen(rtrim($this->uploadDirectory, '/')) + 1));
            if (preg_match(self::STORAGE_KEY_PATTERN, $key)) {
                $keys[] = $key;
            }
        }

        sort($keys);

        return $keys;
    }
}
