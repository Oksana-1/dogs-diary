<?php

namespace App\Controller\Api;

use App\Application\Dog\Data\CreateDogData;
use App\Application\Dog\Data\UpdateDogData;
use App\Application\Dog\DogService;
use App\Application\Media\MediaUrlGenerator;
use App\Controller\Api\Dto\CreateDogPayload;
use App\Controller\Api\Dto\UpdateDogPayload;
use App\Entity\User;
use App\View\DogView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/dogs')]
class DogApiController extends AbstractController
{
    public function __construct(private readonly MediaUrlGenerator $mediaUrlGenerator)
    {
    }

    #[Route('', methods: ['GET'])]
    public function getCollection(#[CurrentUser] User $owner, DogService $dogService): Response
    {
        return $this->json(array_map(
            fn ($dog) => DogView::from($dog, $this->mediaUrlGenerator)->toArray(),
            $dogService->list($owner),
        ));
    }

    #[Route('/{id<\d+>}', methods: ['GET'])]
    public function getItem(int $id, #[CurrentUser] User $owner, DogService $dogService): Response
    {
        $dog = $dogService->get($id, $owner);
        if (!$dog) {
            throw $this->createNotFoundException('Dog not found');
        }

        return $this->json(DogView::from($dog, $this->mediaUrlGenerator)->toArray());
    }

    #[Route('/{id<\d+>}', methods: ['PUT'])]
    public function updateItem(
        int $id,
        #[CurrentUser] User $owner,
        #[MapRequestPayload] UpdateDogPayload $payload,
        DogService $dogService,
    ): Response {
        $dog = $dogService->update(new UpdateDogData(
            id: $id,
            name: $payload->name,
            birthDate: $payload->birthDate,
            gender: $payload->gender,
            adoptDate: $payload->adoptDate,
            status: $payload->status,
            weight: $payload->weight,
            height: $payload->height,
        ), $owner);
        if (!$dog) {
            throw $this->createNotFoundException('Dog not found');
        }

        return $this->json(DogView::from($dog, $this->mediaUrlGenerator)->toArray());
    }

    #[Route('', methods: ['POST'])]
    public function createItem(
        #[CurrentUser] User $owner,
        #[MapRequestPayload] CreateDogPayload $payload,
        DogService $dogService,
    ): Response {
        $dog = $dogService->create(new CreateDogData(
            name: $payload->name,
            birthDate: $payload->birthDate,
            gender: $payload->gender,
            adoptDate: $payload->adoptDate,
            status: $payload->status,
            weight: $payload->weight,
            height: $payload->height,
        ), $owner);

        return $this->json(DogView::from($dog, $this->mediaUrlGenerator)->toArray(), 201);
    }

    #[Route('/{id<\d+>}', methods: ['DELETE'])]
    public function deleteItem(
        int $id,
        #[CurrentUser] User $owner,
        DogService $dogService,
    ): Response {
        if (!$dogService->delete($id, $owner)) {
            throw $this->createNotFoundException('Dog not found');
        }

        return new Response(null, 204);
    }
}
