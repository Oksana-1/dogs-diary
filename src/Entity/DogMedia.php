<?php

namespace App\Entity;

use App\Enum\MediaTypeEnum;
use App\Repository\DogMediaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DogMediaRepository::class)]
#[ORM\Index(name: 'IDX_DOG_MEDIA_DOG_CREATED', columns: ['dog_id', 'created_at'])]
#[ORM\UniqueConstraint(name: 'UNIQ_DOG_MEDIA_STORAGE_KEY', columns: ['storage_key'])]
#[ORM\UniqueConstraint(
    name: 'UNIQ_DOG_MEDIA_THUMBNAIL',
    columns: ['dog_id'],
    options: ['where' => '(is_thumbnail = true)'],
)]
#[ORM\UniqueConstraint(
    name: 'UNIQ_DOG_MEDIA_PROFILE',
    columns: ['dog_id'],
    options: ['where' => '(is_profile = true)'],
)]
class DogMedia
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'media')]
    #[ORM\JoinColumn(name: 'dog_id', nullable: false, onDelete: 'CASCADE')]
    private Dog $dog;

    #[ORM\Column(name: 'media_type', type: Types::STRING, enumType: MediaTypeEnum::class)]
    private MediaTypeEnum $type;

    #[ORM\Column(name: 'storage_key', length: 255)]
    private string $storageKey;

    #[ORM\Column(name: 'original_name', length: 255)]
    private string $originalName;

    #[ORM\Column(name: 'mime_type', length: 100)]
    private string $mimeType;

    #[ORM\Column(name: 'size_bytes', type: Types::BIGINT)]
    private int $sizeBytes;

    #[ORM\Column(nullable: true)]
    private ?int $width = null;

    #[ORM\Column(nullable: true)]
    private ?int $height = null;

    #[ORM\Column(name: 'is_thumbnail', options: ['default' => false])]
    private bool $isThumbnail = false;

    #[ORM\Column(name: 'is_profile', options: ['default' => false])]
    private bool $isProfile = false;

    #[ORM\Column(name: 'created_at', type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Dog $dog,
        MediaTypeEnum $type,
        string $storageKey,
        string $originalName,
        string $mimeType,
        int $sizeBytes,
        ?int $width = null,
        ?int $height = null,
    ) {
        self::assertMetadataIsValid(
            $type,
            $storageKey,
            $originalName,
            $mimeType,
            $sizeBytes,
            $width,
            $height,
        );

        $this->dog = $dog;
        $this->type = $type;
        $this->storageKey = $storageKey;
        $this->originalName = $originalName;
        $this->mimeType = $mimeType;
        $this->sizeBytes = $sizeBytes;
        $this->width = $width;
        $this->height = $height;
        $this->createdAt = new \DateTimeImmutable();
        $dog->addMedia($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDog(): Dog
    {
        return $this->dog;
    }

    public function getType(): MediaTypeEnum
    {
        return $this->type;
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

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function isThumbnail(): bool
    {
        return $this->isThumbnail;
    }

    /**
     * @internal select roles through the owning Dog aggregate
     */
    public function setThumbnail(bool $isThumbnail): static
    {
        if ($isThumbnail && MediaTypeEnum::IMAGE !== $this->type) {
            throw new \DomainException('Only an image can be used as a thumbnail.');
        }

        $this->isThumbnail = $isThumbnail;

        return $this;
    }

    public function isProfile(): bool
    {
        return $this->isProfile;
    }

    /**
     * @internal select roles through the owning Dog aggregate
     */
    public function setProfile(bool $isProfile): static
    {
        $this->isProfile = $isProfile;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    private static function assertMetadataIsValid(
        MediaTypeEnum $type,
        string $storageKey,
        string $originalName,
        string $mimeType,
        int $sizeBytes,
        ?int $width,
        ?int $height,
    ): void {
        if ('' === trim($storageKey) || strlen($storageKey) > 255) {
            throw new \DomainException('The media storage key must contain between 1 and 255 bytes.');
        }

        if ('' === trim($originalName) || strlen($originalName) > 255) {
            throw new \DomainException('The original media name must contain between 1 and 255 bytes.');
        }

        if ('' === trim($mimeType) || strlen($mimeType) > 100 || !str_starts_with($mimeType, $type->value.'/')) {
            throw new \DomainException('The MIME type must match the media type.');
        }

        if ($sizeBytes <= 0) {
            throw new \DomainException('The media size must be positive.');
        }

        if ((null === $width) !== (null === $height)) {
            throw new \DomainException('Media dimensions must either both be present or both be absent.');
        }

        if ((null !== $width && $width <= 0) || (null !== $height && $height <= 0)) {
            throw new \DomainException('Media dimensions must be positive.');
        }

        if (MediaTypeEnum::IMAGE === $type && (null === $width || null === $height)) {
            throw new \DomainException('Image dimensions are required.');
        }
    }
}
