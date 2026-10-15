<?php

namespace App\Application\Media;

use Symfony\Component\HttpFoundation\Response;

final readonly class PrivateMediaResponseFactory
{
    public function __construct(private MediaStorageInterface $storage)
    {
    }

    public function create(string $storageKey, string $originalName, string $mimeType): ?Response
    {
        return $this->storage->createResponse($storageKey, $originalName, $mimeType);
    }
}
