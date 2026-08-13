<?php

namespace App\Entity;

use App\Repository\TreatmentMediaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TreatmentMediaRepository::class)]
#[ORM\Index(name: 'IDX_TREATMENT_MEDIA_TREATMENT_CREATED', columns: ['treatment_id', 'created_at'])]
#[ORM\UniqueConstraint(name: 'UNIQ_TREATMENT_MEDIA_STORAGE_KEY', columns: ['storage_key'])]
#[ORM\UniqueConstraint(name: 'UNIQ_TREATMENT_MEDIA_POSITION', columns: ['treatment_id', 'position'])]
class TreatmentMedia
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'media')]
    #[ORM\JoinColumn(name: 'treatment_id', nullable: false, onDelete: 'CASCADE')]
    private Treatment $treatment;

    #[ORM\Column(name: 'storage_key', length: 255)]
    private string $storageKey;

    #[ORM\Column(name: 'original_name', length: 255)]
    private string $originalName;

    #[ORM\Column(name: 'mime_type', length: 100)]
    private string $mimeType;

    #[ORM\Column(name: 'size_bytes', type: Types::BIGINT)]
    private int $sizeBytes;

    #[ORM\Column]
    private int $width;

    #[ORM\Column]
    private int $height;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $position;

    #[ORM\Column(name: 'created_at', type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Treatment $treatment,
        string $storageKey,
        string $originalName,
        string $mimeType,
        int $sizeBytes,
        int $width,
        int $height,
        int $position,
    ) {
        self::assertMetadataIsValid(
            $storageKey,
            $originalName,
            $mimeType,
            $sizeBytes,
            $width,
            $height,
            $position,
        );

        $this->treatment = $treatment;
        $this->storageKey = $storageKey;
        $this->originalName = $originalName;
        $this->mimeType = $mimeType;
        $this->sizeBytes = $sizeBytes;
        $this->width = $width;
        $this->height = $height;
        $this->position = $position;
        $this->createdAt = new \DateTimeImmutable();
        $treatment->addMedia($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTreatment(): Treatment
    {
        return $this->treatment;
    }

    public function getStorageKey(): string
    {
        return $this->storageKey;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getSizeBytes(): int
    {
        return $this->sizeBytes;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function replaceFile(
        string $storageKey,
        string $originalName,
        string $mimeType,
        int $sizeBytes,
        int $width,
        int $height,
    ): void {
        self::assertMetadataIsValid(
            $storageKey,
            $originalName,
            $mimeType,
            $sizeBytes,
            $width,
            $height,
            1,
        );

        $this->storageKey = $storageKey;
        $this->originalName = $originalName;
        $this->mimeType = $mimeType;
        $this->sizeBytes = $sizeBytes;
        $this->width = $width;
        $this->height = $height;
        $this->position = 1;
        $this->createdAt = new \DateTimeImmutable();
    }

    private static function assertMetadataIsValid(
        string $storageKey,
        string $originalName,
        string $mimeType,
        int $sizeBytes,
        int $width,
        int $height,
        int $position,
    ): void {
        if ('' === trim($storageKey) || strlen($storageKey) > 255) {
            throw new \DomainException('The media storage key must contain between 1 and 255 bytes.');
        }

        if ('' === trim($originalName) || strlen($originalName) > 255) {
            throw new \DomainException('The original media name must contain between 1 and 255 bytes.');
        }

        if ('' === trim($mimeType) || strlen($mimeType) > 100 || !str_starts_with($mimeType, 'image/')) {
            throw new \DomainException('Treatment media must have an image MIME type.');
        }

        if ($sizeBytes <= 0 || $width <= 0 || $height <= 0) {
            throw new \DomainException('Treatment image size and dimensions must be positive.');
        }

        if (1 !== $position) {
            throw new \DomainException('A treatment photo must use position 1.');
        }
    }
}
