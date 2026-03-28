<?php

namespace App\Controller;

use App\Entity\Dog;
use App\Repository\DogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dogs')]
class DogApiController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function getCollection(DogRepository $repository): Response
    {
        return $this->json(array_map($this->normalizeDog(...), $repository->findAll()));
    }

    #[Route('/{id<\d+>}')]
    public function getItem(int $id, DogRepository $repository): Response
    {
        $dog = $repository->find($id);
        if (!$dog) {
            throw $this->createNotFoundException('Dog not found');
        }

        return $this->json($this->normalizeDog($dog));
    }

    private function normalizeDog(Dog $dog): array
    {
        return [
            'id' => $dog->getId(),
            'name' => $dog->getName(),
            'birthDate' => $dog->getBirthDate()?->format('Y-m-d'),
            'weight' => $dog->getWeight(),
            'height' => $dog->getHeight(),
        ];
    }
}
