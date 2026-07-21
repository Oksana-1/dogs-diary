<?php

namespace App\Controller\Api;

use App\Application\Treatment\Data\CreateTreatmentData;
use App\Application\Treatment\Data\UpdateTreatmentData;
use App\Application\Treatment\TreatmentService;
use App\Controller\Api\Dto\CreateTreatmentPayload;
use App\Controller\Api\Dto\UpdateTreatmentPayload;
use App\Enum\TreatmentTypeEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dogs/{dogId}/treatments')]
class TreatmentApiController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function getCollection(int $dogId, TreatmentService $treatmentService): Response
    {
        $treatments = $treatmentService->listForDog($dogId);
        if ($treatments === null) {
            throw $this->createNotFoundException('Dog not found');
        }

        return $this->json($treatments);
    }

    #[Route('/{id<\d+>}', methods: ['GET'])]
    public function getItem(int $dogId, int $id, TreatmentService $treatmentService): Response
    {
        $treatment = $treatmentService->get($id);
        if (!$treatment || $treatment['dogId'] !== $dogId) {
            throw $this->createNotFoundException('Treatment not found');
        }

        return $this->json($treatment);
    }

    #[Route('', methods: ['POST'])]
    public function createItem(
        int $dogId,
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
        ));
        if ($treatment === null) {
            throw $this->createNotFoundException('Dog not found');
        }

        return $this->json($treatment, 201);
    }

    #[Route('/{id<\d+>}', methods: ['PUT'])]
    public function updateItem(
        int $dogId,
        int $id,
        #[MapRequestPayload] UpdateTreatmentPayload $payload,
        TreatmentService $treatmentService,
    ): Response {
        $existingTreatment = $treatmentService->get($id);
        if (!$existingTreatment || $existingTreatment['dogId'] !== $dogId) {
            throw $this->createNotFoundException('Treatment not found');
        }

        $treatment = $treatmentService->update(new UpdateTreatmentData(
            id: $id,
            types: array_map(
                static fn (string $type): TreatmentTypeEnum => TreatmentTypeEnum::from($type),
                $payload->types,
            ),
            productName: $payload->productName,
            treatmentDate: $payload->treatmentDate,
            dueDate: $payload->dueDate,
            note: $payload->note,
        ));
        if ($treatment === null) {
            throw $this->createNotFoundException('Treatment not found');
        }

        return $this->json($treatment);
    }

    #[Route('/{id<\d+>}', methods: ['DELETE'])]
    public function deleteItem(
        int $dogId,
        int $id,
        TreatmentService $treatmentService,
    ): Response {
        $existingTreatment = $treatmentService->get($id);
        if (!$existingTreatment || $existingTreatment['dogId'] !== $dogId) {
            throw $this->createNotFoundException('Treatment not found');
        }

        $treatmentService->delete($id);

        return new Response(null, 204);
    }

}
