<?php

namespace App\Entity;

use App\Enum\TreatmentTypeEnum;
use App\Repository\TreatmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TreatmentRepository::class)]
class Treatment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'treatments')]
    #[ORM\JoinColumn(name: 'dog_id', nullable: false, onDelete: 'RESTRICT')]
    private ?Dog $dog = null;

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

}
