<?php

namespace App\Application\Treatment;

use App\Application\Treatment\Data\CreateTreatmentData;
use App\Application\Treatment\Data\UpdateTreatmentData;
use App\Entity\Treatment;
use App\Repository\DogRepository;
use App\Repository\TreatmentRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class TreatmentService
{
    public function __construct(
        private DogRepository $dogRepository,
        private TreatmentRepository $treatmentRepository,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function listForDog(int $dogId): ?array
    {
        $dog = $this->dogRepository->find($dogId);
        if (!$dog) {
            return null;
        }

        $treatments = $this->treatmentRepository->findBy(['dog' => $dog], ['treatmentDate' => 'DESC']);

        return array_map($this->normalizeTreatment(...), $treatments);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(int $id): ?array
    {
        $treatment = $this->treatmentRepository->find($id);
        if (!$treatment) {
            return null;
        }

        return $this->normalizeTreatment($treatment);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function create(CreateTreatmentData $data): ?array
    {
        $dog = $this->dogRepository->find($data->dogId);
        if (!$dog) {
            return null;
        }

        $treatment = $this->hydrateTreatment(
            new Treatment(),
            $data->types,
            $data->productName,
            new \DateTime($data->treatmentDate),
            $data->dueDate ? new \DateTime($data->dueDate) : null,
            $data->note,
        );
        $treatment->setDog($dog);

        $this->em->persist($treatment);
        $this->em->flush();

        return $this->normalizeTreatment($treatment);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function update(UpdateTreatmentData $data): ?array
    {
        $treatment = $this->treatmentRepository->find($data->id);
        if (!$treatment) {
            return null;
        }

        $this->hydrateTreatment(
            $treatment,
            $data->types,
            $data->productName,
            new \DateTime($data->treatmentDate),
            $data->dueDate ? new \DateTime($data->dueDate) : null,
            $data->note,
        );

        $this->em->flush();

        return $this->normalizeTreatment($treatment);
    }

    public function delete(int $id): bool
    {
        $treatment = $this->treatmentRepository->find($id);
        if (!$treatment) {
            return false;
        }

        $this->em->remove($treatment);
        $this->em->flush();

        return true;
    }

    /**
     * @param array<int, \App\Enum\TreatmentTypeEnum> $types
     */
    private function hydrateTreatment(
        Treatment $treatment,
        array $types,
        string $productName,
        \DateTime $treatmentDate,
        ?\DateTime $dueDate,
        ?string $note,
    ): Treatment {
        $treatment->setType($types);
        $treatment->setProductName($productName);
        $treatment->setTreatmentDate($treatmentDate);
        $treatment->setDueDate($dueDate);
        $treatment->setNote($note);

        return $treatment;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeTreatment(Treatment $treatment): array
    {
        return [
            'id' => $treatment->getId(),
            'dogId' => $treatment->getDog()?->getId(),
            'types' => array_map(static fn ($type) => $type->value, $treatment->getType()),
            'productName' => $treatment->getProductName(),
            'treatmentDate' => $treatment->getTreatmentDate()?->format('Y-m-d'),
            'dueDate' => $treatment->getDueDate()?->format('Y-m-d'),
            'note' => $treatment->getNote(),
        ];
    }
}
