<?php

namespace App\Entity;

use App\Enum\TreatmentTypeEnum;
use App\Repository\TreatmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TreatmentRepository::class)]
class Treatment
{
    public const MAX_MEDIA_COUNT = 1;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'treatments')]
    #[ORM\JoinColumn(name: 'dog_id', nullable: false, onDelete: 'RESTRICT')]
    private ?Dog $dog = null;

    /** @var array<int, TreatmentTypeEnum> */
    #[ORM\Column(type: Types::SIMPLE_ARRAY, enumType: TreatmentTypeEnum::class)]
    private array $type = [];

    #[ORM\Column(length: 255)]
    private ?string $productName = null;

    #[ORM\Column(name: 'treatment_date', type: Types::DATE_MUTABLE)]
    private ?\DateTime $treatmentDate = null;

    #[ORM\Column(name: 'due_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $dueDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $note = null;

    /**
     * @var Collection<int, TreatmentMedia>
     */
    #[ORM\OneToMany(targetEntity: TreatmentMedia::class, mappedBy: 'treatment', cascade: ['remove'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $media;

    public function __construct()
    {
        $this->media = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDog(): ?Dog
    {
        return $this->dog;
    }

    public function setDog(?Dog $dog): static
    {
        $this->dog = $dog;

        return $this;
    }

    /**
     * @return TreatmentTypeEnum[]
     */
    public function getType(): array
    {
        return $this->type;
    }

    /**
     * @param array<int, TreatmentTypeEnum> $type
     */
    public function setType(array $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): static
    {
        $this->productName = $productName;

        return $this;
    }

    public function getTreatmentDate(): ?\DateTime
    {
        return $this->treatmentDate;
    }

    public function setTreatmentDate(\DateTime $treatmentDate): static
    {
        $this->treatmentDate = $treatmentDate;

        return $this;
    }

    public function getDueDate(): ?\DateTime
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTime $dueDate): static
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    /**
     * @return Collection<int, TreatmentMedia>
     */
    public function getMedia(): Collection
    {
        return $this->media;
    }

    public function getPhoto(): ?TreatmentMedia
    {
        $photo = $this->media->first();

        return false === $photo ? null : $photo;
    }

    public function addMedia(TreatmentMedia $media): static
    {
        if ($media->getTreatment() !== $this) {
            throw new \DomainException('Media cannot be attached to a different treatment.');
        }

        foreach ($this->media as $existingMedia) {
            if ($existingMedia !== $media && $existingMedia->getPosition() === $media->getPosition()) {
                throw new \DomainException('The treatment media position is already occupied.');
            }
        }

        if (!$this->media->contains($media)) {
            $this->media->add($media);
        }

        return $this;
    }

    public function nextMediaPosition(): ?int
    {
        $occupiedPositions = [];
        foreach ($this->media as $media) {
            $occupiedPositions[$media->getPosition()] = true;
        }

        for ($position = 1; $position <= self::MAX_MEDIA_COUNT; ++$position) {
            if (!isset($occupiedPositions[$position])) {
                return $position;
            }
        }

        return null;
    }
}
