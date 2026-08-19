<?php

namespace App\Entity;

use App\Enum\GenderTypeEnum;
use App\Enum\MediaTypeEnum;
use App\Repository\DogRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DogRepository::class)]
class Dog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $name = null;

    #[ORM\Column(nullable: true, type: Types::STRING, enumType: GenderTypeEnum::class)]
    private ?GenderTypeEnum $gender = null;

    #[ORM\Column(name: 'birth_date')]
    private ?\DateTimeImmutable $birthDate = null;

    #[ORM\Column(name: 'adopt_date', nullable: true)]
    private ?\DateTimeImmutable $adoptDate = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive]
    private ?int $weight = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive]
    private ?int $height = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $status = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'dogs')]
    #[ORM\JoinTable(name: 'dog_owner')]
    #[ORM\JoinColumn(name: 'dog_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Collection $owners;

    /**
     * @var Collection<int, Treatment>
     */
    #[ORM\OneToMany(targetEntity: Treatment::class, mappedBy: 'dog', cascade: ['remove'])]
    private Collection $treatments;

    /**
     * @var Collection<int, DogMedia>
     */
    #[ORM\OneToMany(targetEntity: DogMedia::class, mappedBy: 'dog', cascade: ['remove'])]
    #[ORM\OrderBy(['createdAt' => 'DESC', 'id' => 'DESC'])]
    private Collection $media;

    public function __construct()
    {
        $this->owners = new ArrayCollection();
        $this->treatments = new ArrayCollection();
        $this->media = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getGender(): ?GenderTypeEnum
    {
        return $this->gender;
    }

    public function setGender(?GenderTypeEnum $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getBirthDate(): ?\DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(\DateTimeImmutable $birthDate): static
    {
        return $this->setBusinessDates($birthDate, $this->adoptDate);
    }

    public function setBusinessDates(\DateTimeImmutable $birthDate, ?\DateTimeImmutable $adoptDate): static
    {
        if ($birthDate > new \DateTimeImmutable('today')) {
            throw new \DomainException('Birth date cannot be in the future.');
        }

        if (null !== $adoptDate && $adoptDate < $birthDate) {
            throw new \DomainException('Adoption date cannot be before birth date.');
        }

        $this->birthDate = $birthDate;
        $this->adoptDate = $adoptDate;

        return $this;
    }

    public function getAdoptDate(): ?\DateTimeImmutable
    {
        return $this->adoptDate;
    }

    public function setAdoptDate(?\DateTimeImmutable $adoptDate): static
    {
        if (null !== $this->birthDate) {
            return $this->setBusinessDates($this->birthDate, $adoptDate);
        }

        $this->adoptDate = $adoptDate;

        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(?int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getOwners(): Collection
    {
        return $this->owners;
    }

    public function addOwner(User $owner): static
    {
        if (!$this->owners->contains($owner)) {
            $this->owners->add($owner);
            $owner->addDog($this);
        }

        return $this;
    }

    public function removeOwner(User $owner): static
    {
        if ($this->owners->removeElement($owner)) {
            $owner->removeDog($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Treatment>
     */
    public function getTreatments(): Collection
    {
        return $this->treatments;
    }

    public function addTreatment(Treatment $treatment): static
    {
        if (!$this->treatments->contains($treatment)) {
            $this->treatments->add($treatment);
            $treatment->setDog($this);
        }

        return $this;
    }

    public function removeTreatment(Treatment $treatment): static
    {
        if ($this->treatments->removeElement($treatment) && $treatment->getDog() === $this) {
            $treatment->setDog(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, DogMedia>
     */
    public function getMedia(): Collection
    {
        return $this->media;
    }

    public function addMedia(DogMedia $media): static
    {
        if ($media->getDog() !== $this) {
            throw new \DomainException('Media cannot be attached to a different dog.');
        }

        if (!$this->media->contains($media)) {
            $this->media->add($media);
        }

        return $this;
    }

    public function selectThumbnailMedia(DogMedia $selectedMedia): static
    {
        $this->assertOwnsMedia($selectedMedia);
        if (MediaTypeEnum::IMAGE !== $selectedMedia->getType()) {
            throw new \DomainException('Only an image can be used as a thumbnail.');
        }

        foreach ($this->media as $media) {
            $media->setThumbnail($media === $selectedMedia);
        }

        return $this;
    }

    public function clearThumbnailMedia(): static
    {
        foreach ($this->media as $media) {
            $media->setThumbnail(false);
        }

        return $this;
    }

    public function selectProfileMedia(DogMedia $selectedMedia): static
    {
        $this->assertOwnsMedia($selectedMedia);

        foreach ($this->media as $media) {
            $media->setProfile($media === $selectedMedia);
        }

        return $this;
    }

    public function clearProfileMedia(): static
    {
        foreach ($this->media as $media) {
            $media->setProfile(false);
        }

        return $this;
    }

    public function getThumbnailMedia(): ?DogMedia
    {
        foreach ($this->media as $media) {
            if ($media->isThumbnail()) {
                return $media;
            }
        }

        return null;
    }

    public function getProfileMedia(): ?DogMedia
    {
        foreach ($this->media as $media) {
            if ($media->isProfile()) {
                return $media;
            }
        }

        return null;
    }

    private function assertOwnsMedia(DogMedia $media): void
    {
        if ($media->getDog() !== $this || !$this->media->contains($media)) {
            throw new \DomainException('The selected media does not belong to this dog.');
        }
    }
}
