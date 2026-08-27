<?php

namespace App\Application\Media;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

final readonly class PrivateMediaResponseFactory
{
    public function __construct(private MediaStorageInterface $storage)
    {
    }

    public function create(string $storageKey, string $originalName, string $mimeType): ?BinaryFileResponse
    {
        $path = $this->storage->resolvePath($storageKey);
        if (null === $path) {
            return null;
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $originalName);
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }
}
