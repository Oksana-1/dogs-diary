<?php

namespace App\Controller\Api;

use App\Dto\UpdateDogPayload;
use App\Entity\Dog;
use App\Repository\DogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dogs')]
class DogApiController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function getCollection(DogRepository $repository): Response
    {
        return $this->json(array_map($this->normalizeDog(...), $repository->findAll()));
    }

    #[Route('/{id<\d+>}', methods: ['GET'])]
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
            'status' => $dog->getStatus(),
        ];
    }
    #[Route('/{id<\d+>}', methods: ['PUT'])]
    public function updateItem(
        int $id,
        #[MapRequestPayload] UpdateDogPayload $payload,
        DogRepository $repository,
        EntityManagerInterface $em
    ): Response {
        $dog = $repository->find($id);
        if (!$dog) {
            throw $this->createNotFoundException('Dog not found');
        }

        $dog->setName($payload->name);
        $dog->setStatus($payload->status);
        $dog->setWeight($payload->weight);
        $dog->setHeight($payload->height);
        $dog->setBirthDate(new \DateTimeImmutable($payload->birthDate));

        $em->flush();

        return $this->json($this->normalizeDog($dog));
    }
    #[Route('/{id<\d+>}', methods: ['DELETE'])]
    public function deleteItem(
        int $id,
        DogRepository $repository,
        EntityManagerInterface $em
        ): Response {
            $dog = $repository->find($id);
            if (!$dog) {
                throw $this->createNotFoundException('Dog not found');
            }
            $em->remove($dog);
            $em->flush();
            return new Response(null, 204);
        }
}
