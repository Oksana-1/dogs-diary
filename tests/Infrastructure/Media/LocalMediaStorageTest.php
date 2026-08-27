<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Media;

use App\Infrastructure\Media\LocalMediaStorage;
use PHPUnit\Framework\TestCase;

final class LocalMediaStorageTest extends TestCase
{
    public function testInvalidStorageKeysCannotEscapeThePrivateDirectory(): void
    {
        $storage = new LocalMediaStorage(sys_get_temp_dir());

        self::assertFalse($storage->exists('../../etc/passwd'));
        self::assertNull($storage->resolvePath('../../etc/passwd'));

        $this->expectException(\InvalidArgumentException::class);
        $storage->delete('../../etc/passwd');
    }
}
