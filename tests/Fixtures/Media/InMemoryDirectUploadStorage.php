<?php

namespace App\Tests\Fixtures\Media;

use App\Application\Media\DirectUploadStorageInterface;
use App\Enum\MediaOwnerTypeEnum;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class InMemoryDirectUploadStorage implements DirectUploadStorageInterface
{
    public array $staging = [];
    public array $objects = [];
    public array $retained = [];
    public int $downloads = 0;
    public bool $failStore = false;

    public function createUploadUrl(string $uploadId, string $mimeType): string
    {
        return 'https://account.r2.cloudflarestorage.com/media/staging/'.$uploadId;
    }

    public function downloadUpload(string $uploadId, string $originalName, string $mimeType, int $sizeBytes): UploadedFile
    {
        ++$this->downloads;
        $path = tempnam(sys_get_temp_dir(), 'dogs-diary-upload-test-');
        file_put_contents($path, $this->staging[$uploadId]);

        return new UploadedFile($path, $originalName, $mimeType, null, true);
    }

    public function store(MediaOwnerTypeEnum $ownerType, int $ownerId, UploadedFile $file, string $extension): string
    {
        if ($this->failStore) {
            throw new \RuntimeException('Synthetic storage failure');
        }
        $key = $ownerType->value.'/'.$ownerId.'/'.bin2hex(random_bytes(16)).'.'.$extension;
        $this->objects[$key] = file_get_contents($file->getPathname());

        return $key;
    }

    public function delete(string $storageKey): void
    {
        if (isset($this->objects[$storageKey])) {
            $this->retained[$storageKey] = $this->objects[$storageKey];
            unset($this->objects[$storageKey]);
        }
    }

    public function exists(string $storageKey): bool
    {
        return isset($this->objects[$storageKey]);
    }

    public function createResponse(string $storageKey, string $originalName, string $mimeType): ?RedirectResponse
    {
        return $this->exists($storageKey)
            ? new RedirectResponse('https://account.r2.cloudflarestorage.com/media/'.$storageKey, 307, ['Cache-Control' => 'private, no-store'])
            : null;
    }

    public function allStorageKeys(): array
    {
        return array_keys($this->objects);
    }
}
