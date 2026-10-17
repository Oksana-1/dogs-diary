<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Media;

use App\Application\Media\Exception\MediaValidationException;
use App\Enum\MediaOwnerTypeEnum;
use App\Infrastructure\Media\R2MediaStorage;
use Aws\Command;
use Aws\Exception\AwsException;
use Aws\MockHandler;
use Aws\Result;
use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class R2MediaStorageTest extends TestCase
{
    private const KEY = 'dogs/1/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.png';
    private const ID = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';
    private array $commands = [];

    public function testSignedUrlsExpireAndKeepReadsPrivate(): void
    {
        $storage = $this->storage([new Result()]);
        $url = $storage->createUploadUrl(self::ID, 'image/png');
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        self::assertSame('600', $query['X-Amz-Expires']);
        self::assertStringContainsString('/media/staging/'.self::ID, $url);
        self::assertSame('host', $query['X-Amz-SignedHeaders']);
        $response = $storage->createResponse(self::KEY, 'my photo.png', 'image/png');
        self::assertSame(307, $response->getStatusCode());
        self::assertSame('no-referrer', $response->headers->get('Referrer-Policy'));
        self::assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        self::assertSame('60', $query['X-Amz-Expires']);
        self::assertSame('private, no-store', $query['response-cache-control']);
    }

    public function testDeletionRetainsBeforeRemovingTheCanonicalObject(): void
    {
        $this->storage([new Result(), new Result(), new Result()])->delete(self::KEY);
        self::assertSame(['HeadObject', 'CopyObject', 'DeleteObject'], array_column($this->commands, 'name'));
        self::assertSame('media/'.self::KEY, $this->commands[1]['args']['CopySource']);
        self::assertMatchesRegularExpression('#^retained/\d{8}T\d{6}Z/[a-f0-9]{32}/'.preg_quote(self::KEY, '#').'$#', $this->commands[1]['args']['Key']);
        self::assertSame(self::KEY, $this->commands[2]['args']['Key']);
    }

    public function testFailedRetentionNeverDeletesTheOriginal(): void
    {
        try {
            $this->storage([new Result(), $this->error(503)])->delete(self::KEY);
            self::fail('A failed retained copy must abort deletion.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Unable to retain and delete the media object in R2.', $exception->getMessage());
        }
        self::assertSame(['HeadObject', 'CopyObject'], array_column($this->commands, 'name'));
    }

    public function testMissingObjectsAndPermissionFailuresAreDistinguished(): void
    {
        self::assertFalse($this->storage([$this->error(404)])->exists(self::KEY));
        $this->expectException(\RuntimeException::class);
        $this->storage([$this->error(403)])->exists(self::KEY);
    }

    public function testListingsPaginateAndExcludeStagingAndRetention(): void
    {
        $other = 'treatments/2/cccccccccccccccccccccccccccccccc.jpg';
        $storage = $this->storage([
            new Result(['Contents' => [['Key' => self::KEY], ['Key' => 'staging/'.self::ID]], 'IsTruncated' => true, 'NextContinuationToken' => 'page-two']),
            new Result(['Contents' => [['Key' => 'retained/old/'.self::KEY]]]),
            new Result(['Contents' => [['Key' => $other]]]),
        ]);
        self::assertSame([self::KEY, $other], $storage->allStorageKeys());
        self::assertSame('page-two', $this->commands[1]['args']['ContinuationToken']);
        self::assertSame('treatments/', $this->commands[2]['args']['Prefix']);
    }

    public function testDownloadUsesTheInspectedVersionAndActualBytes(): void
    {
        $storage = $this->storage([new Result(['ContentLength' => 3, 'ETag' => 'version-one']), new Result(['Body' => Utils::streamFor('abc')])]);
        $file = $storage->downloadUpload(self::ID, 'photo.png', 'image/png', 3);
        try {
            self::assertSame('abc', file_get_contents($file->getPathname()));
            self::assertSame('version-one', $this->commands[1]['args']['IfMatch']);
            self::assertTrue($this->commands[1]['args']['@http']['stream']);
        } finally {
            unlink($file->getPathname());
        }
    }

    #[DataProvider('invalidDownloads')]
    public function testMismatchedAndPartialDownloadsAreRejected(int $headSize, string $body): void
    {
        $storage = $this->storage([new Result(['ContentLength' => $headSize, 'ETag' => 'version']), new Result(['Body' => Utils::streamFor($body)])]);
        $this->expectException(MediaValidationException::class);
        $storage->downloadUpload(self::ID, 'photo.png', 'image/png', 3);
    }

    public static function invalidDownloads(): iterable
    {
        yield 'HEAD mismatch' => [4, 'abcd'];
        yield 'stream longer than declared' => [3, 'abcd'];
        yield 'truncated stream' => [3, 'ab'];
    }

    public function testStoreUsesCanonicalRandomKeysAndPrivateMetadata(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'r2-store-test-');
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
        try {
            $key = $this->storage([new Result()])->store(MediaOwnerTypeEnum::DOG, 7, new UploadedFile($path, 'photo.png', 'image/png', null, true), 'png');
            self::assertMatchesRegularExpression('#^dogs/7/[a-f0-9]{32}\.png$#', $key);
            self::assertSame('image/png', $this->commands[0]['args']['ContentType']);
            self::assertSame('private, no-store', $this->commands[0]['args']['CacheControl']);
        } finally {
            unlink($path);
        }
    }

    public function testInvalidKeysNeverReachR2(): void
    {
        $storage = $this->storage([]);
        self::assertFalse($storage->exists('../../etc/passwd'));
        $this->expectException(\InvalidArgumentException::class);
        $storage->delete('retained/'.self::KEY);
    }

    private function storage(array $results): R2MediaStorage
    {
        $mock = new MockHandler($results);
        $client = new S3Client([
            'version' => 'latest', 'region' => 'auto', 'endpoint' => 'https://'.str_repeat('a', 32).'.r2.cloudflarestorage.com',
            'credentials' => ['key' => 'test-key', 'secret' => 'test-secret'], 'use_path_style_endpoint' => true,
            'request_checksum_calculation' => 'when_required', 'response_checksum_validation' => 'when_required', 'retries' => 0,
            'handler' => function ($command, $request) use ($mock) {
                $this->commands[] = ['name' => $command->getName(), 'args' => $command->toArray()];

                return $mock($command, $request);
            },
        ]);

        return new R2MediaStorage($client, 'media');
    }

    private function error(int $status): AwsException
    {
        return new AwsException('Synthetic private SDK details', new Command('HeadObject'), ['response' => new Response($status)]);
    }
}
