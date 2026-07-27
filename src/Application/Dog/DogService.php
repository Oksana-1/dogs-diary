<?php

namespace App\Application\Dog;

use App\Application\Dog\Data\CreateDogData;
use App\Application\Dog\Data\UpdateDogData;
use App\Entity\Dog;
use App\Repository\DogRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DogService
{
    public function __construct(
        private DogRepository $dogRepository,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @return array<int, Dog>
     */
    public function list(): array
    {
        return $this->dogRepository->findAll();
    }

    public function get(int $id): ?Dog
    {
        return $this->dogRepository->find($id);
    }

    public function create(CreateDogData $data): Dog
    {
        $dog = $this->hydrateDog(
            new Dog(),
            $data->name,
            new \DateTimeImmutable($data->birthDate),
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

        $this->em->remove($dog);
        $this->em->flush();

        return true;
    }

    private function hydrateDog(
        Dog $dog,
        string $name,
        \DateTimeImmutable $birthDate,
        ?\DateTimeImmutable $adoptDate,
        ?string $status,
        ?string $avatar,
        ?int $weight,
        ?int $height,
    ): Dog {
        $dog->setName($name);
        $dog->setBirthDate($birthDate);
        $dog->setAdoptDate($adoptDate);
        $dog->setStatus($status);
        $dog->setAvatar($avatar);
        $dog->setWeight($weight);
        $dog->setHeight($height);

        return $dog;
    }

    private function parseDateOrNull(?string $date): ?\DateTimeImmutable
    {
        if ($date === null || $date === '') {
            return null;
        }

        return new \DateTimeImmutable($date);
    }
}
