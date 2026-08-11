<?php

namespace App\Application\Dog;

use App\Application\Dog\Data\CreateDogData;
use App\Application\Dog\Data\UpdateDogData;
use App\Application\Media\MediaStorageInterface;
use App\Entity\Dog;
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
    public function list(): array
    {
        return $this->dogRepository->findAllWithMedia();
    }

    public function get(int $id): ?Dog
    {
        return $this->dogRepository->findWithMedia($id);
    }

    public function create(CreateDogData $data): Dog
    {
        $dog = $this->hydrateDog(
            new Dog(),
            $data->name,
            new \DateTimeImmutable($data->birthDate),
            $data->gender,
            $this->parseDateOrNull($data->adoptDate),
            $data->status,
            $data->avatar,
            $data->weight,
            $data->height,
        );

        $this->em->persist($dog);
        $this->em->flush();

        return $dog;
    }

    public function update(UpdateDogData $data): ?Dog
    {
        $dog = $this->dogRepository->find($data->id);
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
            $data->avatar,
            $data->weight,
            $data->height,
        );

        $this->em->flush();

        return $dog;
    }

    public function delete(int $id): bool
    {
        $dog = $this->dogRepository->find($id);
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
        ?string $avatar,
        ?int $weight,
        ?int $height,
    ): Dog {
        $dog->setName($name);
        $dog->setBirthDate($birthDate);
        $dog->setGender(null !== $gender ? GenderTypeEnum::from($gender) : null);
        $dog->setAdoptDate($adoptDate);
        $dog->setStatus($status);
        $dog->setAvatar($avatar);
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
