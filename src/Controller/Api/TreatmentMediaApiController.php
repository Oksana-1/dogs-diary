<?php

namespace App\Controller\Api;

use App\Application\Media\Exception\MediaValidationException;
use App\Application\Media\MediaStorageInterface;
use App\Application\TreatmentMedia\TreatmentMediaService;
use App\Entity\User;
use App\View\TreatmentMediaView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/dogs/{dogId<\d+>}/treatments/{treatmentId<\d+>}/media')]
final class TreatmentMediaApiController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function getCollection(
        int $dogId,
        int $treatmentId,
        #[CurrentUser] User $owner,
        TreatmentMediaService $mediaService,
        MediaStorageInterface $storage,
    ): Response {
        $media = $mediaService->listForTreatment($dogId, $treatmentId, $owner);
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
        #[CurrentUser] User $owner,
        Request $request,
        TreatmentMediaService $mediaService,
        MediaStorageInterface $storage,
    ): Response {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            throw new MediaValidationException('A multipart file field named "file" is required.', field: 'file');
        }

        $media = $mediaService->upload($dogId, $treatmentId, $file, $owner);

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
        #[CurrentUser] User $owner,
        TreatmentMediaService $mediaService,
    ): Response {
        if (!$mediaService->delete($dogId, $treatmentId, $id, $owner)) {
            throw $this->createNotFoundException('Treatment media not found');
        }

        return new Response(null, 204);
    }
}
