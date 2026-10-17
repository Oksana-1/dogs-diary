<?php

namespace App\Infrastructure\Media;

use App\Application\Media\DirectUploadStorageInterface;
use App\Application\Media\Exception\MediaValidationException;
use App\Enum\MediaOwnerTypeEnum;
use Aws\Exception\AwsException;
use Aws\S3\S3ClientInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;

final readonly class R2MediaStorage implements DirectUploadStorageInterface
{
    private const KEY_PATTERN = '#^(?:dogs/[1-9]\d*/[a-f0-9]{32}\.(?:jpg|png|webp|mp4|webm)|treatments/[1-9]\d*/[a-f0-9]{32}\.(?:jpg|png|webp))$#';

    public function __construct(private S3ClientInterface $client, private string $bucket)
    {
    }

    public function store(MediaOwnerTypeEnum $ownerType, int $ownerId, UploadedFile $file, string $extension): string
    {
        $key = $ownerType->value.'/'.$ownerId.'/'.bin2hex(random_bytes(16)).'.'.$extension;
        $this->assertKey($key);
        $body = fopen($file->getPathname(), 'rb');
        if (false === $body) {
            throw new \RuntimeException('Unable to read the validated media file.');
        }
        try {
            $this->client->putObject([
                'Bucket' => $this->bucket, 'Key' => $key, 'Body' => $body,
                'ContentType' => $file->getMimeType(), 'CacheControl' => 'private, no-store',
            ]);
        } catch (AwsException) {
            throw new \RuntimeException('Unable to store the validated media in R2.');
        } finally {
            fclose($body);
        }

        return $key;
    }

    public function delete(string $storageKey): void
    {
        $this->assertKey($storageKey);
        if (!$this->exists($storageKey)) {
            return;
        }
        $retainedKey = 'retained/'.gmdate('Ymd\THis\Z').'/'.bin2hex(random_bytes(16)).'/'.$storageKey;
        try {
            $this->client->copyObject([
                'Bucket' => $this->bucket, 'Key' => $retainedKey,
                'CopySource' => $this->bucket.'/'.$storageKey,
            ]);
            $this->client->deleteObject(['Bucket' => $this->bucket, 'Key' => $storageKey]);
        } catch (AwsException) {
            throw new \RuntimeException('Unable to retain and delete the media object in R2.');
        }
    }

    public function exists(string $storageKey): bool
    {
        if (!preg_match(self::KEY_PATTERN, $storageKey)) {
            return false;
        }
        try {
            $this->client->headObject(['Bucket' => $this->bucket, 'Key' => $storageKey]);

            return true;
        } catch (AwsException $exception) {
            if (404 === $exception->getStatusCode()) {
                return false;
            }

            throw new \RuntimeException('Unable to check the media object in R2.');
        }
    }

    public function createResponse(string $storageKey, string $originalName, string $mimeType): ?RedirectResponse
    {
        if (!$this->exists($storageKey)) {
            return null;
        }
        $command = $this->client->getCommand('GetObject', [
            'Bucket' => $this->bucket, 'Key' => $storageKey,
            'ResponseContentType' => $mimeType, 'ResponseCacheControl' => 'private, no-store',
            'ResponseContentDisposition' => HeaderUtils::makeDisposition('inline', $originalName, 'media'),
        ]);
        $url = (string) $this->client->createPresignedRequest($command, '+60 seconds')->getUri();
        $response = new RedirectResponse($url, 307);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }

    public function allStorageKeys(): array
    {
        $keys = [];
        foreach (['dogs/', 'treatments/'] as $prefix) {
            $token = null;
            do {
                try {
                    $page = $this->client->listObjectsV2(array_filter([
                        'Bucket' => $this->bucket, 'Prefix' => $prefix, 'ContinuationToken' => $token,
                    ], static fn ($value) => null !== $value));
                } catch (AwsException) {
                    throw new \RuntimeException('Unable to list media objects in R2.');
                }
                foreach ($page['Contents'] ?? [] as $object) {
                    if (preg_match(self::KEY_PATTERN, $object['Key'])) {
                        $keys[] = $object['Key'];
                    }
                }
                $token = ($page['IsTruncated'] ?? false) ? ($page['NextContinuationToken'] ?? null) : null;
                if (($page['IsTruncated'] ?? false) && null === $token) {
                    throw new \RuntimeException('R2 returned an incomplete media listing.');
                }
            } while (null !== $token);
        }
        sort($keys);

        return $keys;
    }

    public function createUploadUrl(string $uploadId, string $mimeType): string
    {
        $command = $this->client->getCommand('PutObject', [
            'Bucket' => $this->bucket, 'Key' => $this->stagingKey($uploadId),
            'ContentType' => $mimeType,
        ]);

        return (string) $this->client->createPresignedRequest($command, '+10 minutes')->getUri();
    }

    public function downloadUpload(string $uploadId, string $originalName, string $mimeType, int $sizeBytes): UploadedFile
    {
        $key = $this->stagingKey($uploadId);
        try {
            $head = $this->client->headObject(['Bucket' => $this->bucket, 'Key' => $key]);
            if ($sizeBytes !== (int) $head['ContentLength']) {
                throw new MediaValidationException('The uploaded file size does not match the upload request.', 422, 'file');
            }
            $result = $this->client->getObject([
                'Bucket' => $this->bucket, 'Key' => $key, 'IfMatch' => $head['ETag'],
                '@http' => ['stream' => true],
            ]);
        } catch (AwsException $exception) {
            throw new MediaValidationException(404 === $exception->getStatusCode() ? 'The uploaded file was not found.' : 'Unable to read the uploaded file. Try uploading it again.', 404 === $exception->getStatusCode() ? 422 : 503, 'file');
        }
        $path = tempnam(sys_get_temp_dir(), 'dogs-diary-r2-');
        if (false === $path) {
            $result['Body']->close();
            throw new \RuntimeException('Unable to prepare media validation.');
        }
        $destination = fopen($path, 'wb');
        $body = $result['Body'];
        try {
            if (false === $destination) {
                throw new \RuntimeException('Unable to prepare media validation.');
            }
            $received = 0;
            while (!$body->eof()) {
                $chunk = $body->read(1048576);
                if ('' === $chunk && !$body->eof()) {
                    throw new \RuntimeException('The media download stopped unexpectedly.');
                }
                $received += strlen($chunk);
                if ($received > $sizeBytes) {
                    throw new MediaValidationException('The uploaded file exceeds the requested size.', 413, 'file');
                }
                if (strlen($chunk) !== fwrite($destination, $chunk)) {
                    throw new \RuntimeException('Unable to prepare media validation.');
                }
            }
            if ($received !== $sizeBytes) {
                throw new MediaValidationException('The uploaded file is incomplete.', 422, 'file');
            }
        } catch (\Throwable $exception) {
            unlink($path);
            throw $exception;
        } finally {
            $body->close();
            if (is_resource($destination)) {
                fclose($destination);
            }
        }

        return new UploadedFile($path, $originalName, $mimeType, null, true);
    }

    private function assertKey(string $key): void
    {
        if (!preg_match(self::KEY_PATTERN, $key)) {
            throw new \InvalidArgumentException('Invalid media storage key.');
        }
    }

    private function stagingKey(string $id): string
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $id)) {
            throw new \InvalidArgumentException('Invalid media upload ID.');
        }

        return 'staging/'.$id;
    }
}
