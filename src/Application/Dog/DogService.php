<?php

namespace App\Application\Dog;

use App\Application\Dog\Data\CreateDogData;
use App\Application\Dog\Data\UpdateDogData;
use App\Application\Media\MediaStorageInterface;
use App\Entity\Dog;
use App\Entity\User;
use App\Enum\GenderTypeEnum;
use App\Repository\DogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class DogService
{
    public function __construct(
        private DogRepository $dogRepository,
        private EntityManagerInterface $em,
        private MediaStorageInterface $mediaStorage,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<int, Dog>
     */
    public function list(User $owner): array
    {
        return $this->dogRepository->findAllWithMediaForOwner($owner);
    }

    public function get(int $id, User $owner): ?Dog
    {
        return $this->dogRepository->findWithMediaForOwner($id, $owner);
    }

    public function create(CreateDogData $data, User $owner): Dog
    {
        $dog = $this->hydrateDog(
            new Dog(),
            $data->name,
            new \DateTimeImmutable($data->birthDate),
            $data->gender,
            $this->parseDateOrNull($data->adoptDate),
            $data->status,
            $data->weight,
            $data->height,
        );
        $dog->addOwner($owner);

        $this->em->persist($dog);
        $this->em->flush();

        return $dog;
    }

    public function update(UpdateDogData $data, User $owner): ?Dog
    {
        $dog = $this->dogRepository->findForOwner($data->id, $owner);
        if (!$dog) {
            return null;
        }

        $this->hydrateDog(
            $dog,
            $data->name,
            new \DateTimeImmutable($data->birthDate),
            $data->gender,
            $this->parseDateOrNull($data->adoptDate),
            $data->status,
            $data->weight,
            $data->height,
        );

        $this->em->flush();

        return $dog;
    }

    public function delete(int $id, User $owner): bool
    {
        $dog = $this->dogRepository->findForOwner($id, $owner);
        if (!$dog) {
            return false;
        }

        $storageKeys = $dog->getMedia()
            ->map(static fn ($media): string => $media->getStorageKey())
            ->toArray();
        foreach ($dog->getTreatments() as $treatment) {
            foreach ($treatment->getMedia() as $media) {
                $storageKeys[] = $media->getStorageKey();
            }
        }

        $this->em->remove($dog);
        $this->em->flush();

        foreach ($storageKeys as $storageKey) {
            try {
                $this->mediaStorage->delete($storageKey);
            } catch (\Throwable $exception) {
                $this->logger->error('Failed to delete a media file after deleting its dog.', [
                    'dogId' => $id,
                    'storageKey' => $storageKey,
                    'exception' => $exception,
                ]);
            }
        }

        return true;
    }

    private function hydrateDog(
        Dog $dog,
        string $name,
        \DateTimeImmutable $birthDate,
        ?string $gender,
        ?\DateTimeImmutable $adoptDate,
        ?string $status,
        ?int $weight,
        ?int $height,
    ): Dog {
        $dog->setName($name);
        $dog->setBusinessDates($birthDate, $adoptDate);
        $dog->setGender(null !== $gender ? GenderTypeEnum::from($gender) : null);
        $dog->setStatus($status);
        $dog->setWeight($weight);
        $dog->setHeight($height);

        return $dog;
    }

    private function parseDateOrNull(?string $date): ?\DateTimeImmutable
    {
        if (null === $date || '' === $date) {
            return null;
        }

        return new \DateTimeImmutable($date);
    }
}
