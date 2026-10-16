<?php

declare(strict_types=1);

namespace App\Tests\Application\Media;

use App\Application\Media\Exception\MediaValidationException;
use App\Application\Media\MediaUploadValidator;
use App\Enum\MediaTypeEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class MediaUploadValidatorTest extends TestCase
{
    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testAcceptsAnImageBasedOnItsContentAndSanitizesItsOriginalName(): void
    {
        $file = $this->uploadedFile($this->png(), '../pet-photo.png', 'application/octet-stream');

        $metadata = (new MediaUploadValidator(1024, 1024))->validate($file);

        self::assertSame(MediaTypeEnum::IMAGE, $metadata->type);
        self::assertSame('image/png', $metadata->mimeType);
        self::assertSame('png', $metadata->extension);
        self::assertSame('pet-photo.png', $metadata->originalName);
        self::assertSame(1, $metadata->width);
        self::assertSame(1, $metadata->height);
    }

    public function testRejectsUnsupportedContentRegardlessOfClientMimeType(): void
    {
        $file = $this->uploadedFile('<?php echo "unsafe";', 'photo.png', 'image/png');

        try {
            (new MediaUploadValidator(1024, 1024))->validate($file);
            self::fail('Unsupported upload content was accepted.');
        } catch (MediaValidationException $exception) {
            self::assertSame(415, $exception->getStatusCode());
            self::assertSame('file', $exception->getField());
        }
    }

    public function testRejectsContentAboveTheConfiguredTypeLimit(): void
    {
        $file = $this->uploadedFile($this->png(), 'large.png', 'image/png');

        try {
            (new MediaUploadValidator(10, 1024))->validate($file);
            self::fail('Oversized upload content was accepted.');
        } catch (MediaValidationException $exception) {
            self::assertSame(413, $exception->getStatusCode());
            self::assertSame('file', $exception->getField());
        }
    }

    private function uploadedFile(string $contents, string $name, string $clientMimeType): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'dogs-diary-upload-');
        self::assertIsString($path);
        self::assertSame(strlen($contents), file_put_contents($path, $contents));
        $this->temporaryFiles[] = $path;

        return new UploadedFile($path, $name, $clientMimeType, null, true);
    }

    private function png(): string
    {
        $contents = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );
        self::assertIsString($contents);

        return $contents;
    }
}
