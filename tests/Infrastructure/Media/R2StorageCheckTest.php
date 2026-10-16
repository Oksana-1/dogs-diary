<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Media;

use App\Infrastructure\Media\R2StorageCheck;
use Aws\Exception\AwsException;
use Aws\MockHandler;
use Aws\Result;
use Aws\S3\S3Client;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler as HttpMockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;

final class R2StorageCheckTest extends TestCase
{
    private array $objects = [];
    private array $keys = [];

    public function testSyntheticCheckExercisesStorageAndRemovesOnlyItsOwnObjects(): void
    {
        $checks = $this->check()->run();
        self::assertContains('signed-put', $checks);
        self::assertContains('signed-get', $checks);
        self::assertContains('byte-range', $checks);
        self::assertContains('unsigned-access-denied', $checks);
        self::assertContains('synthetic-restore', $checks);
        self::assertContains('cleanup-verified', $checks);
        self::assertSame([], $this->objects);
        foreach ($this->keys as $key) {
            self::assertMatchesRegularExpression('#^(?:staging/[a-f0-9]{32}|(?:retained/\d{8}T\d{6}Z/[a-f0-9]{32}/)?dogs/'.PHP_INT_MAX.'/[a-f0-9]{32}\.png)$#', $key);
        }
    }

    public function testFailedVerificationStillCleansUpSyntheticObjects(): void
    {
        try {
            $this->check(publicReads: true)->run();
            self::fail('Public reads must fail verification.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Unsigned access was not denied.', $exception->getMessage());
        }
        self::assertSame([], $this->objects);
    }

    private function check(bool $publicReads = false): R2StorageCheck
    {
        $sdk = new MockHandler(array_fill(0, 50, function ($command): Result {
            $key = $command['Key'];
            if (null !== $key) {
                $this->keys[] = $key;
            }
            switch ($command->getName()) {
                case 'HeadObject':
                    if (!isset($this->objects[$key])) {
                        throw new AwsException('Missing', $command, ['response' => new Response(404)]);
                    }

                    return new Result(['ContentLength' => strlen($this->objects[$key]), 'ETag' => 'synthetic-version']);
                case 'GetObject':
                    return new Result(['Body' => Utils::streamFor($this->objects[$key])]);
                case 'PutObject':
                    $this->objects[$key] = is_resource($command['Body']) ? stream_get_contents($command['Body']) : (string) $command['Body'];
                    break;
                case 'CopyObject':
                    $this->objects[$key] = $this->objects[substr($command['CopySource'], strlen('media/'))];
                    break;
                case 'DeleteObject':
                    unset($this->objects[$key]);
                    break;
                case 'ListObjectsV2':
                    $matching = array_filter(array_keys($this->objects), static fn ($candidate) => str_starts_with($candidate, $command['Prefix']));

                    return new Result(['Contents' => array_map(static fn ($candidate) => ['Key' => $candidate], $matching)]);
                default:
                    throw new \LogicException('Unexpected SDK command.');
            }

            return new Result();
        }));
        $client = new S3Client([
            'version' => 'latest', 'region' => 'auto', 'endpoint' => 'https://'.str_repeat('a', 32).'.r2.cloudflarestorage.com',
            'credentials' => ['key' => 'test-key', 'secret' => 'test-secret'], 'use_path_style_endpoint' => true,
            'request_checksum_calculation' => 'when_required', 'response_checksum_validation' => 'when_required', 'retries' => 0, 'handler' => $sdk,
        ]);
        $http = new HttpMockHandler(array_fill(0, 10, function ($request) use ($publicReads): Response {
            $key = substr($request->getUri()->getPath(), strlen('/media/'));
            $this->keys[] = $key;
            if ('PUT' === $request->getMethod()) {
                $this->objects[$key] = (string) $request->getBody();

                return new Response(200);
            }
            if ('' === $request->getUri()->getQuery() && !$publicReads) {
                return new Response(403);
            }
            if ($request->hasHeader('Range')) {
                return new Response(206, [], substr($this->objects[$key], 0, 8));
            }

            return new Response(200, [], $this->objects[$key]);
        }));

        return new R2StorageCheck($client, new Client(['handler' => HandlerStack::create($http)]), 'media');
    }
}
