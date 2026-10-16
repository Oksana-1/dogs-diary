<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Media;

use App\Infrastructure\Media\LocalMediaStorage;
use App\Infrastructure\Media\MediaStorageFactory;
use App\Infrastructure\Media\R2MediaStorage;
use PHPUnit\Framework\TestCase;

final class MediaStorageFactoryTest extends TestCase
{
    public function testLocalDevelopmentNeedsNoR2Credentials(): void
    {
        $local = new LocalMediaStorage(sys_get_temp_dir());
        self::assertSame($local, (new MediaStorageFactory($local, 'local', '', 'auto', '', '', ''))->create());
    }

    public function testR2ConfigurationCreatesTheRemoteAdapterWithoutNetworkAccess(): void
    {
        $factory = new MediaStorageFactory(new LocalMediaStorage(sys_get_temp_dir()), 'r2', 'https://'.str_repeat('a', 32).'.eu.r2.cloudflarestorage.com', 'auto', 'dogs-diary-media', 'test-key', 'test-secret');
        self::assertInstanceOf(R2MediaStorage::class, $factory->create());
    }

    public function testUnexpectedEndpointsAreRejectedBeforeSendingCredentials(): void
    {
        $factory = new MediaStorageFactory(new LocalMediaStorage(sys_get_temp_dir()), 'r2', 'https://untrusted.example', 'auto', 'dogs-diary-media', 'test-key', 'test-secret');
        $this->expectException(\InvalidArgumentException::class);
        $factory->create();
    }
}
