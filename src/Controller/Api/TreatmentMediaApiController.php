<?php

namespace App\Controller\Api;

use App\Application\Media\Exception\MediaValidationException;
use App\Application\Media\MediaStorageInterface;
use App\Application\TreatmentMedia\TreatmentMediaService;
use App\View\TreatmentMediaView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dogs/{dogId<\d+>}/treatments/{treatmentId<\d+>}/media')]
final class TreatmentMediaApiController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function getCollection(
        int $dogId,
        int $treatmentId,
        TreatmentMediaService $mediaService,
        MediaStorageInterface $storage,
    ): Response {
        $media = $mediaService->listForTreatment($dogId, $treatmentId);
        if (null === $media) {
            throw $this->createNotFoundException('Treatment not found');
        }

        return $this->json(array_map(
            static fn ($item) => TreatmentMediaView::from($item, $storage)->toArray(),
            $media,
        ));
    }

    #[Route('', methods: ['POST'])]
    public function upload(
        int $dogId,
        int $treatmentId,
        Request $request,
        TreatmentMediaService $mediaService,
        MediaStorageInterface $storage,
    ): Response {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            return $this->json(['message' => 'A multipart file field named "file" is required.'], 422);
        }

        try {
            $media = $mediaService->upload($dogId, $treatmentId, $file);
        } catch (MediaValidationException $exception) {
            return $this->json(['message' => $exception->getMessage()], $exception->getStatusCode());
        }

        if (null === $media) {
            throw $this->createNotFoundException('Treatment not found');
        }

        return $this->json(TreatmentMediaView::from($media, $storage)->toArray(), 201);
    }

    #[Route('/{id<\d+>}', methods: ['DELETE'])]
    public function delete(
        int $dogId,
        int $treatmentId,
        int $id,
        TreatmentMediaService $mediaService,
    ): Response {
        if (!$mediaService->delete($dogId, $treatmentId, $id)) {
            throw $this->createNotFoundException('Treatment media not found');
        }

        return new Response(null, 204);
    }
}
