<?php

namespace App\Controller\Api;

use App\Application\DogMedia\DogMediaService;
use App\Application\Media\Exception\MediaValidationException;
use App\Application\Media\MediaStorageInterface;
use App\Controller\Api\Dto\SelectDogMediaPayload;
use App\View\DogMediaView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dogs/{dogId<\d+>}/media')]
final class DogMediaApiController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function getCollection(
        int $dogId,
        DogMediaService $mediaService,
        MediaStorageInterface $storage,
    ): Response {
        $media = $mediaService->listForDog($dogId);
        if (null === $media) {
            throw $this->createNotFoundException('Dog not found');
        }

        return $this->json(array_map(
            static fn ($item) => DogMediaView::from($item, $storage)->toArray(),
            $media,
        ));
    }

    #[Route('', methods: ['POST'])]
    public function upload(
        int $dogId,
        Request $request,
        DogMediaService $mediaService,
        MediaStorageInterface $storage,
    ): Response {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            return $this->json(['message' => 'A multipart file field named "file" is required.'], 422);
        }

        try {
            $media = $mediaService->upload($dogId, $file);
        } catch (MediaValidationException $exception) {
            return $this->json(['message' => $exception->getMessage()], $exception->getStatusCode());
        }

        if (null === $media) {
            throw $this->createNotFoundException('Dog not found');
        }

        return $this->json(DogMediaView::from($media, $storage)->toArray(), 201);
    }

    #[Route('/{id<\d+>}', methods: ['DELETE'])]
    public function delete(
        int $dogId,
        int $id,
        DogMediaService $mediaService,
    ): Response {
        if (!$mediaService->delete($dogId, $id)) {
            throw $this->createNotFoundException('Dog media not found');
        }

        return new Response(null, 204);
    }

    #[Route('/thumbnail', methods: ['PUT'])]
    public function selectThumbnail(
        int $dogId,
        #[MapRequestPayload] SelectDogMediaPayload $payload,
        DogMediaService $mediaService,
        MediaStorageInterface $storage,
    ): Response {
        try {
            $media = $mediaService->selectThumbnail($dogId, $payload->mediaId);
        } catch (MediaValidationException $exception) {
            return $this->json(['message' => $exception->getMessage()], $exception->getStatusCode());
        }

        if (null === $media) {
            throw $this->createNotFoundException('Dog media not found');
        }

        return $this->json(DogMediaView::from($media, $storage)->toArray());
    }

    #[Route('/thumbnail', methods: ['DELETE'])]
    public function clearThumbnail(int $dogId, DogMediaService $mediaService): Response
    {
        if (!$mediaService->clearThumbnail($dogId)) {
            throw $this->createNotFoundException('Dog not found');
        }

        return new Response(null, 204);
    }

    #[Route('/profile', methods: ['PUT'])]
    public function selectProfile(
        int $dogId,
        #[MapRequestPayload] SelectDogMediaPayload $payload,
        DogMediaService $mediaService,
        MediaStorageInterface $storage,
    ): Response {
        $media = $mediaService->selectProfile($dogId, $payload->mediaId);
        if (null === $media) {
            throw $this->createNotFoundException('Dog media not found');
        }

        return $this->json(DogMediaView::from($media, $storage)->toArray());
    }

    #[Route('/profile', methods: ['DELETE'])]
    public function clearProfile(int $dogId, DogMediaService $mediaService): Response
    {
        if (!$mediaService->clearProfile($dogId)) {
            throw $this->createNotFoundException('Dog not found');
        }

        return new Response(null, 204);
    }
}
