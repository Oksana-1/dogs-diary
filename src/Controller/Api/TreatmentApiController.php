<?php

namespace App\Controller\Api;

use App\Application\Media\MediaUrlGenerator;
use App\Application\Treatment\Data\CreateTreatmentData;
use App\Application\Treatment\Data\UpdateTreatmentData;
use App\Application\Treatment\TreatmentService;
use App\Controller\Api\Dto\CreateTreatmentPayload;
use App\Controller\Api\Dto\UpdateTreatmentPayload;
use App\Entity\User;
use App\Enum\TreatmentTypeEnum;
use App\View\TreatmentView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/dogs/{dogId}/treatments')]
class TreatmentApiController extends AbstractController
{
    public function __construct(private readonly MediaUrlGenerator $mediaUrlGenerator)
    {
    }

    #[Route('', methods: ['GET'])]
    public function getCollection(
        int $dogId,
        #[CurrentUser] User $owner,
        TreatmentService $treatmentService,
    ): Response {
        $treatments = $treatmentService->listForDog($dogId, $owner);
        if (null === $treatments) {
            throw $this->createNotFoundException('Dog not found');
        }

        return $this->json(array_map(
            fn ($treatment) => TreatmentView::from($treatment, $this->mediaUrlGenerator)->toArray(),
            $treatments,
        ));
    }

    #[Route('/{id<\d+>}', methods: ['GET'])]
    public function getItem(
        int $dogId,
        int $id,
        #[CurrentUser] User $owner,
        TreatmentService $treatmentService,
    ): Response {
        $treatment = $treatmentService->get($dogId, $id, $owner);
        if (!$treatment) {
            throw $this->createNotFoundException('Treatment not found');
        }

        return $this->json(TreatmentView::from($treatment, $this->mediaUrlGenerator)->toArray());
    }

    #[Route('', methods: ['POST'])]
    public function createItem(
        int $dogId,
        #[CurrentUser] User $owner,
        #[MapRequestPayload] CreateTreatmentPayload $payload,
        TreatmentService $treatmentService,
    ): Response {
        $treatment = $treatmentService->create(new CreateTreatmentData(
            dogId: $dogId,
            types: array_map(
                static fn (string $type): TreatmentTypeEnum => TreatmentTypeEnum::from($type),
                $payload->types,
            ),
            productName: $payload->productName,
            treatmentDate: $payload->treatmentDate,
            dueDate: $payload->dueDate,
            note: $payload->note,
        ), $owner);
        if (null === $treatment) {
            throw $this->createNotFoundException('Dog not found');
        }

        return $this->json(TreatmentView::from($treatment, $this->mediaUrlGenerator)->toArray(), 201);
    }

    #[Route('/{id<\d+>}', methods: ['PUT'])]
    public function updateItem(
        int $dogId,
        int $id,
        #[CurrentUser] User $owner,
        #[MapRequestPayload] UpdateTreatmentPayload $payload,
        TreatmentService $treatmentService,
    ): Response {
        $treatment = $treatmentService->update($dogId, new UpdateTreatmentData(
            id: $id,
            types: array_map(
                static fn (string $type): TreatmentTypeEnum => TreatmentTypeEnum::from($type),
                $payload->types,
            ),
            productName: $payload->productName,
            treatmentDate: $payload->treatmentDate,
            dueDate: $payload->dueDate,
            note: $payload->note,
        ), $owner);
        if (null === $treatment) {
            throw $this->createNotFoundException('Treatment not found');
        }

        return $this->json(TreatmentView::from($treatment, $this->mediaUrlGenerator)->toArray());
    }

    #[Route('/{id<\d+>}', methods: ['DELETE'])]
    public function deleteItem(
        int $dogId,
        int $id,
        #[CurrentUser] User $owner,
        TreatmentService $treatmentService,
    ): Response {
        if (!$treatmentService->delete($dogId, $id, $owner)) {
            throw $this->createNotFoundException('Treatment not found');
        }

        return new Response(null, 204);
    }
}
