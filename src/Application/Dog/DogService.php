<?php

namespace App\Application\Dog;

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
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        return array_map($this->normalizeDog(...), $this->dogRepository->findAll());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(int $id): ?array
    {
        $dog = $this->dogRepository->find($id);
        if (!$dog) {
            return null;
        }

        return $this->normalizeDog($dog);
    }

    /**
     * @return array<string, mixed>
     */
    public function create(
        string $name,
        string $birthDate,
        ?string $status,
        ?int $weight,
        ?int $height,
    ): array {
        $dog = $this->hydrateDog(
            new Dog(),
            $name,
            new \DateTimeImmutable($birthDate),
            $status,
            $weight,
            $height,
        );

        $this->em->persist($dog);
        $this->em->flush();

        return $this->normalizeDog($dog);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function update(
        int $id,
        string $name,
        string $birthDate,
        ?string $status,
        ?int $weight,
        ?int $height,
    ): ?array {
        $dog = $this->dogRepository->find($id);
        if (!$dog) {
            return null;
        }

        $this->hydrateDog(
            $dog,
            $name,
            new \DateTimeImmutable($birthDate),
            $status,
            $weight,
            $height,
        );

        $this->em->flush();

        return $this->normalizeDog($dog);
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
        ?string $status,
        ?int $weight,
        ?int $height,
    ): Dog {
        $dog->setName($name);
        $dog->setBirthDate($birthDate);
        $dog->setStatus($status);
        $dog->setWeight($weight);
        $dog->setHeight($height);

        return $dog;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeDog(Dog $dog): array
    {
        return [
            'id' => $dog->getId(),
            'name' => $dog->getName(),
            'birthDate' => $dog->getBirthDate()?->format('Y-m-d'),
            'weight' => $dog->getWeight(),
            'height' => $dog->getHeight(),
            'status' => $dog->getStatus(),
        ];
    }
}
