<?php

namespace App\Infrastructure\Media;

use App\Application\Media\MediaStorageInterface;
use Aws\S3\S3Client;

final readonly class MediaStorageFactory
{
    public function __construct(
        private LocalMediaStorage $local,
        private string $driver,
        private string $endpoint,
        private string $region,
        private string $bucket,
        private string $accessKeyId,
        private string $secretAccessKey,
    ) {
    }

    public function create(): MediaStorageInterface
    {
        if ('local' === $this->driver) {
            return $this->local;
        }

        return new R2MediaStorage($this->createS3Client(), $this->bucket);
    }

    public function createS3Client(): S3Client
    {
        if ('r2' !== $this->driver) {
            throw new \InvalidArgumentException('MEDIA_STORAGE must be local or r2.');
        }
        if (!preg_match('#^https://[a-f0-9]{32}(?:\.(?:eu|us|fedramp))?\.r2\.cloudflarestorage\.com/?$#', $this->endpoint)
            || 'auto' !== $this->region
            || !preg_match('/^[a-z0-9][a-z0-9-]{1,61}[a-z0-9]$/', $this->bucket)
            || '' === $this->accessKeyId || '' === $this->secretAccessKey) {
            throw new \InvalidArgumentException('Set a valid MEDIA_R2_ENDPOINT, MEDIA_R2_REGION=auto, bucket and S3 credentials.');
        }

        return new S3Client([
            'version' => 'latest',
            'region' => $this->region,
            'endpoint' => rtrim($this->endpoint, '/'),
            'use_path_style_endpoint' => true,
            'signature_version' => 'v4',
            'request_checksum_calculation' => 'when_required',
            'response_checksum_validation' => 'when_required',
            'credentials' => ['key' => $this->accessKeyId, 'secret' => $this->secretAccessKey],
            'http' => ['connect_timeout' => 10, 'timeout' => 120],
            'retries' => 2,
        ]);
    }
}
