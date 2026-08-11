<?php

namespace App\Application\Media;

use App\Application\Media\Exception\MediaValidationException;
use App\Enum\MediaTypeEnum;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class MediaUploadValidator
{
    private const MIME_CONFIGURATION = [
        'image/jpeg' => [MediaTypeEnum::IMAGE, 'jpg'],
        'image/png' => [MediaTypeEnum::IMAGE, 'png'],
        'image/webp' => [MediaTypeEnum::IMAGE, 'webp'],
        'video/mp4' => [MediaTypeEnum::VIDEO, 'mp4'],
        'video/webm' => [MediaTypeEnum::VIDEO, 'webm'],
    ];

    public function __construct(
        private int $maxImageBytes,
        private int $maxVideoBytes,
    ) {
    }

    public function validate(UploadedFile $file): UploadMetadata
    {
        if (!$file->isValid()) {
            $statusCode = \UPLOAD_ERR_INI_SIZE === $file->getError()
                || \UPLOAD_ERR_FORM_SIZE === $file->getError()
                ? 413
                : 422;

            throw new MediaValidationException('The media upload did not complete successfully.', $statusCode);
        }

        $path = $file->getPathname();
        $size = filesize($path);
        if (false === $size || 0 === $size) {
            throw new MediaValidationException('The uploaded file is empty.');
        }

        $mimeType = (new \finfo(\FILEINFO_MIME_TYPE))->file($path);
        if (!is_string($mimeType) || !isset(self::MIME_CONFIGURATION[$mimeType])) {
            throw new MediaValidationException('Only JPEG, PNG, WebP, MP4, and WebM files are supported.');
        }

        [$type, $extension] = self::MIME_CONFIGURATION[$mimeType];
        $limit = MediaTypeEnum::IMAGE === $type ? $this->maxImageBytes : $this->maxVideoBytes;
        if ($size > $limit) {
            $limitMb = (int) ceil($limit / 1024 / 1024);

            throw new MediaValidationException(sprintf('The uploaded %s must not exceed %d MB.', $type->value, $limitMb), 413);
        }

        [$width, $height] = MediaTypeEnum::IMAGE === $type
            ? $this->validateImage($path, $mimeType)
            : $this->validateVideoContainer($path, $mimeType);

        return new UploadMetadata(
            type: $type,
            mimeType: $mimeType,
            extension: $extension,
            originalName: $this->sanitizeOriginalName($file->getClientOriginalName(), $extension),
            sizeBytes: $size,
            width: $width,
            height: $height,
        );
    }

    /**
     * @return array{int, int}
     */
    private function validateImage(string $path, string $mimeType): array
    {
        $image = @getimagesize($path);
        if (false === $image) {
            throw new MediaValidationException('The uploaded image is corrupt or unreadable.');
        }

        $expectedTypes = [
            'image/jpeg' => \IMAGETYPE_JPEG,
            'image/png' => \IMAGETYPE_PNG,
            'image/webp' => \IMAGETYPE_WEBP,
        ];
        if (($image[2] ?? null) !== $expectedTypes[$mimeType]) {
            throw new MediaValidationException('The uploaded image content does not match its media type.');
        }

        return [(int) $image[0], (int) $image[1]];
    }

    /**
     * Video dimensions require a dedicated metadata reader and remain null in v1.
     *
     * @return array{null, null}
     */
    private function validateVideoContainer(string $path, string $mimeType): array
    {
        $header = file_get_contents($path, false, null, 0, 12);
        if (false === $header) {
            throw new MediaValidationException('The uploaded video is unreadable.');
        }

        $isMp4 = 'video/mp4' === $mimeType && 'ftyp' === substr($header, 4, 4);
        $isWebm = 'video/webm' === $mimeType && str_starts_with($header, "\x1A\x45\xDF\xA3");
        if (!$isMp4 && !$isWebm) {
            throw new MediaValidationException('The uploaded video container is invalid or unsupported.');
        }

        return [null, null];
    }

    private function sanitizeOriginalName(string $name, string $extension): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';
        $name = trim($name);
        if ('' === $name) {
            $name = 'upload.'.$extension;
        }

        return function_exists('mb_strcut')
            ? mb_strcut($name, 0, 255, 'UTF-8')
            : substr($name, 0, 255);
    }
}
