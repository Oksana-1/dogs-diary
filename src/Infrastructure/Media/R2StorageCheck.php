<?php

namespace App\Infrastructure\Media;

use App\Application\Media\MediaUploadValidator;
use App\Enum\MediaOwnerTypeEnum;
use Aws\CommandInterface;
use Aws\Middleware;
use Aws\S3\S3ClientInterface;
use GuzzleHttp\ClientInterface;

final readonly class R2StorageCheck
{
    public function __construct(private S3ClientInterface $client, private ClientInterface $http, private string $bucket)
    {
    }

    public function run(): array
    {
        $id = bin2hex(random_bytes(16));
        $keys = ['staging/'.$id];
        $retained = null;
        $canonical = null;
        $file = null;
        $cleanupFailed = false;
        $this->client->getHandlerList()->appendInit(Middleware::tap(static function (CommandInterface $command) use (&$keys, &$retained): void {
            if (in_array($command->getName(), ['PutObject', 'CopyObject'], true)) {
                $keys[] = $command['Key'];
            }
            if ('CopyObject' === $command->getName()) {
                $retained = $command['Key'];
            }
        }), 'r2-synthetic-check');
        $storage = new R2MediaStorage($this->client, $this->bucket);
        $bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=', true);
        $checks = [];
        try {
            $put = $this->http->request('PUT', $storage->createUploadUrl($id, 'image/png'), [
                'body' => $bytes, 'headers' => ['Content-Type' => 'image/png'], 'http_errors' => false, 'allow_redirects' => false,
            ]);
            $this->require($put->getStatusCode() < 300 && $put->getStatusCode() >= 200, 'Signed upload failed.');
            $checks[] = 'signed-put';
            $file = $storage->downloadUpload($id, 'synthetic.png', 'image/png', strlen($bytes));
            $metadata = (new MediaUploadValidator(10485760, 104857600))->validate($file);
            $this->require(hash('sha256', $bytes) === hash_file('sha256', $file->getPathname()), 'Staged bytes did not match.');
            $checks[] = 'staging-validation';
            $canonical = $storage->store(MediaOwnerTypeEnum::DOG, PHP_INT_MAX, $file, $metadata->extension);
            $this->require($storage->exists($canonical), 'Canonical object was not found.');
            $response = $storage->createResponse($canonical, 'synthetic.png', 'image/png');
            $this->require(null !== $response, 'Signed download was not created.');
            $url = $response->headers->get('Location');
            $get = $this->http->request('GET', $url, ['http_errors' => false, 'allow_redirects' => false]);
            $this->require(200 === $get->getStatusCode() && hash('sha256', $bytes) === hash('sha256', (string) $get->getBody()), 'Signed download did not match.');
            $checks[] = 'signed-get';
            $range = $this->http->request('GET', $url, ['headers' => ['Range' => 'bytes=0-7'], 'http_errors' => false, 'allow_redirects' => false]);
            $this->require(206 === $range->getStatusCode() && substr($bytes, 0, 8) === (string) $range->getBody(), 'Byte-range download failed.');
            $checks[] = 'byte-range';
            $unsigned = $this->http->request('GET', strtok($url, '?'), ['http_errors' => false, 'allow_redirects' => false]);
            $this->require(in_array($unsigned->getStatusCode(), [400, 401, 403, 404], true), 'Unsigned access was not denied.');
            $checks[] = 'unsigned-access-denied';
            $listing = $this->client->listObjectsV2(['Bucket' => $this->bucket, 'Prefix' => $canonical]);
            $this->require(in_array($canonical, array_column($listing['Contents'] ?? [], 'Key'), true), 'Synthetic prefix listing failed.');
            $checks[] = 'synthetic-prefix-list';
            $storage->delete($canonical);
            $this->require(null !== $retained && !$storage->exists($canonical), 'Retained deletion failed.');
            $copy = $this->client->getObject(['Bucket' => $this->bucket, 'Key' => $retained]);
            $this->require(hash('sha256', $bytes) === hash('sha256', (string) $copy['Body']), 'Retained copy did not match.');
            $checks[] = 'retained-delete';
            $this->client->copyObject(['Bucket' => $this->bucket, 'Key' => $canonical, 'CopySource' => $this->bucket.'/'.$retained]);
            $restored = $this->client->getObject(['Bucket' => $this->bucket, 'Key' => $canonical]);
            $this->require(hash('sha256', $bytes) === hash('sha256', (string) $restored['Body']), 'Restored copy did not match.');
            $checks[] = 'synthetic-restore';
        } finally {
            $this->client->getHandlerList()->remove('r2-synthetic-check');
            if (null !== $file && is_file($file->getPathname())) {
                unlink($file->getPathname());
            }
            foreach (array_unique($keys) as $key) {
                try {
                    $this->client->deleteObject(['Bucket' => $this->bucket, 'Key' => $key]);
                } catch (\Throwable) {
                    $cleanupFailed = true;
                }
            }
            if ($cleanupFailed) {
                throw new \RuntimeException('Synthetic R2 cleanup could not be confirmed.');
            }
        }
        foreach (array_unique($keys) as $key) {
            try {
                $this->client->headObject(['Bucket' => $this->bucket, 'Key' => $key]);
                throw new \RuntimeException('A synthetic R2 object remains after cleanup.');
            } catch (\Aws\Exception\AwsException $exception) {
                if (404 !== $exception->getStatusCode()) {
                    throw new \RuntimeException('Synthetic R2 cleanup verification failed.');
                }
            }
        }
        $checks[] = 'cleanup-verified';

        return $checks;
    }

    private function require(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }
}
