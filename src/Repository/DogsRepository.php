<?php

namespace App\Repository;

use App\Model\Dog;
use Psr\Log\LoggerInterface;

class DogsRepository
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function findAll(): array
    {
        $this->logger->info('Dogs collection retrieved!');

        return [
            new Dog(1, 'Sharik', '15-08-2017'),
            new Dog(2, 'Rusty', '15-09-2021'),
        ];
    }

    public function find(int $id): ?Dog
    {
        foreach ($this->findAll() as $dog) {
            if ($dog->getId() === $id) {
                return $dog;
            }
        }

        return null;
    }
}
