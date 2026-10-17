<?php

namespace App\Entity;

use App\Repository\MediaUploadRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MediaUploadRepository::class)]
#[ORM\Index(name: 'IDX_MEDIA_UPLOAD_EXPIRES', columns: ['expires_at'])]
class MediaUpload
{
    #[ORM\Id]
    #[ORM\Column(length: 32)]
    private string $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $owner;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Dog $dog;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Treatment $treatment;

    #[ORM\Column(length: 255)]
    private string $originalName;

    #[ORM\Column(length: 100)]
    private string $mimeType;

    #[ORM\Column(type: Types::BIGINT)]
    private int $sizeBytes;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(options: ['default' => false])]
    private bool $completed = false;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?DogMedia $dogMedia = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?TreatmentMedia $treatmentMedia = null;

    public function __construct(User $owner, Dog $dog, ?Treatment $treatment, string $originalName, string $mimeType, int $sizeBytes)
    {
        $this->id = bin2hex(random_bytes(16));
        $this->owner = $owner;
        $this->dog = $dog;
        $this->treatment = $treatment;
        $this->originalName = $originalName;
        $this->mimeType = $mimeType;
        $this->sizeBytes = $sizeBytes;
        $this->expiresAt = new \DateTimeImmutable('+10 minutes');
    }

    public function getId(): string
    {
        return $this->id;
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

    public function isExpired(): bool
    {
        return $this->expiresAt <= new \DateTimeImmutable();
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function getResult(): DogMedia|TreatmentMedia|null
    {
        return $this->dogMedia ?? $this->treatmentMedia;
    }

    public function complete(DogMedia|TreatmentMedia $media): void
    {
        $this->completed = true;
        if ($media instanceof DogMedia) {
            $this->dogMedia = $media;
        } else {
            $this->treatmentMedia = $media;
        }
    }
}
