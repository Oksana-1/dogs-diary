<?php

namespace App\Application\Treatment;

use App\Application\Media\MediaStorageInterface;
use App\Application\Treatment\Data\CreateTreatmentData;
use App\Application\Treatment\Data\UpdateTreatmentData;
use App\Entity\Treatment;
use App\Repository\DogRepository;
use App\Repository\TreatmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class TreatmentService
{
    public function __construct(
        private DogRepository $dogRepository,
        private TreatmentRepository $treatmentRepository,
        private EntityManagerInterface $em,
        private MediaStorageInterface $mediaStorage,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<int, Treatment>|null
     */
    public function listForDog(int $dogId): ?array
    {
        $dog = $this->dogRepository->find($dogId);
        if (!$dog) {
            return null;
        }

        return $this->treatmentRepository->findBy(['dog' => $dog], ['treatmentDate' => 'DESC']);
    }

    public function get(int $id): ?Treatment
    {
        return $this->treatmentRepository->find($id);
    }

    public function create(CreateTreatmentData $data): ?Treatment
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

        return $treatment;
    }

    public function update(UpdateTreatmentData $data): ?Treatment
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

        return $treatment;
    }

    public function delete(int $id): bool
    {
        $treatment = $this->treatmentRepository->find($id);
        if (!$treatment) {
            return false;
        }

        $storageKeys = $treatment->getMedia()
            ->map(static fn ($media): string => $media->getStorageKey())
            ->toArray();

        $this->em->remove($treatment);
        $this->em->flush();

        foreach ($storageKeys as $storageKey) {
            try {
                $this->mediaStorage->delete($storageKey);
            } catch (\Throwable $exception) {
                $this->logger->error('Failed to delete a media file after deleting its treatment.', [
                    'treatmentId' => $id,
                    'storageKey' => $storageKey,
                    'exception' => $exception,
                ]);
            }
        }

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
        $treatment->setBusinessDates($treatmentDate, $dueDate);
        $treatment->setNote($note);

        return $treatment;
    }
}
