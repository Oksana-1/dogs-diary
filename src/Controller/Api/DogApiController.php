<?php

namespace App\Controller\Api;

use App\Application\Dog\DogService;
use App\Controller\Api\Dto\CreateDogPayload;
use App\Controller\Api\Dto\UpdateDogPayload;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dogs')]
class DogApiController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function getCollection(DogService $dogService): Response
    {
        return $this->json($dogService->list());
    }

    #[Route('/{id<\d+>}', methods: ['GET'])]
    public function getItem(int $id, DogService $dogService): Response
    {
        $dog = $dogService->get($id);
        if (!$dog) {
            throw $this->createNotFoundException('Dog not found');
        }

        return $this->json($dog);
    }

    #[Route('/{id<\d+>}', methods: ['PUT'])]
    public function updateItem(
        int $id,
        #[MapRequestPayload] UpdateDogPayload $payload,
        DogService $dogService,
    ): Response {
        $dog = $dogService->update($id, $payload->name, $payload->birthDate, $payload->status, $payload->weight, $payload->height);
        if (!$dog) {
            throw $this->createNotFoundException('Dog not found');
        }

        return $this->json($dog);
    }

    #[Route('', methods: ['POST'])]
    public function createItem(
        #[MapRequestPayload] CreateDogPayload $payload,
        DogService $dogService,
    ): Response {
        $dog = $dogService->create($payload->name, $payload->birthDate, $payload->status, $payload->weight, $payload->height);

        return $this->json($dog, 201);
    }

    #[Route('/{id<\d+>}', methods: ['DELETE'])]
    public function deleteItem(
        int $id,
        DogService $dogService,
    ): Response {
        if (!$dogService->delete($id)) {
            throw $this->createNotFoundException('Dog not found');
        }

        return new Response(null, 204);
    }
}
