<?php

namespace App\Controller\Api;

use App\Application\DogMedia\DogMediaService;
use App\Application\Media\Exception\MediaValidationException;
use App\Application\Media\MediaUrlGenerator;
use App\Application\Media\PrivateMediaResponseFactory;
use App\Controller\Api\Dto\SelectDogMediaPayload;
use App\Entity\User;
use App\View\DogMediaView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/dogs/{dogId<\d+>}/media')]
final class DogMediaApiController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function getCollection(
        int $dogId,
        #[CurrentUser] User $owner,
        DogMediaService $mediaService,
        MediaUrlGenerator $mediaUrlGenerator,
    ): Response {
        $media = $mediaService->listForDog($dogId, $owner);
        if (null === $media) {
            throw $this->createNotFoundException('Dog not found');
        }

        return $this->json(array_map(
            static fn ($item) => DogMediaView::from($item, $mediaUrlGenerator)->toArray(),
            $media,
        ));
    }

    #[Route('', methods: ['POST'])]
    public function upload(
        int $dogId,
        #[CurrentUser] User $owner,
        Request $request,
        DogMediaService $mediaService,
        MediaUrlGenerator $mediaUrlGenerator,
    ): Response {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            throw new MediaValidationException('A multipart file field named "file" is required.', field: 'file');
        }

        $media = $mediaService->upload($dogId, $file, $owner);

        if (null === $media) {
            throw $this->createNotFoundException('Dog not found');
        }

        return $this->json(DogMediaView::from($media, $mediaUrlGenerator)->toArray(), 201);
    }

    #[Route('/{id<\d+>}', name: 'app_api_dog_media_download', methods: ['GET'])]
    public function download(
        int $dogId,
        int $id,
        #[CurrentUser] User $owner,
        DogMediaService $mediaService,
        PrivateMediaResponseFactory $responseFactory,
    ): Response {
        $media = $mediaService->get($dogId, $id, $owner);
        if (null === $media) {
            throw $this->createNotFoundException('Dog media not found');
        }

        $response = $responseFactory->create(
            $media->getStorageKey(),
            $media->getOriginalName(),
            $media->getMimeType(),
        );
        if (null === $response) {
            throw $this->createNotFoundException('Dog media file not found');
        }

        return $response;
    }

    #[Route('/{id<\d+>}', methods: ['DELETE'])]
    public function delete(
        int $dogId,
        int $id,
        #[CurrentUser] User $owner,
        DogMediaService $mediaService,
    ): Response {
        if (!$mediaService->delete($dogId, $id, $owner)) {
            throw $this->createNotFoundException('Dog media not found');
        }

        return new Response(null, 204);
    }

    #[Route('/thumbnail', methods: ['PUT'])]
    public function selectThumbnail(
        int $dogId,
        #[CurrentUser] User $owner,
        #[MapRequestPayload] SelectDogMediaPayload $payload,
        DogMediaService $mediaService,
        MediaUrlGenerator $mediaUrlGenerator,
    ): Response {
        $media = $mediaService->selectThumbnail($dogId, $payload->mediaId, $owner);

        if (null === $media) {
            throw $this->createNotFoundException('Dog media not found');
        }

        return $this->json(DogMediaView::from($media, $mediaUrlGenerator)->toArray());
    }

    #[Route('/thumbnail', methods: ['DELETE'])]
    public function clearThumbnail(
        int $dogId,
        #[CurrentUser] User $owner,
        DogMediaService $mediaService,
    ): Response {
        if (!$mediaService->clearThumbnail($dogId, $owner)) {
            throw $this->createNotFoundException('Dog not found');
        }

        return new Response(null, 204);
    }

    #[Route('/profile', methods: ['PUT'])]
    public function selectProfile(
        int $dogId,
        #[CurrentUser] User $owner,
        #[MapRequestPayload] SelectDogMediaPayload $payload,
        DogMediaService $mediaService,
        MediaUrlGenerator $mediaUrlGenerator,
    ): Response {
        $media = $mediaService->selectProfile($dogId, $payload->mediaId, $owner);
        if (null === $media) {
            throw $this->createNotFoundException('Dog media not found');
        }

        return $this->json(DogMediaView::from($media, $mediaUrlGenerator)->toArray());
    }

    #[Route('/profile', methods: ['DELETE'])]
    public function clearProfile(
        int $dogId,
        #[CurrentUser] User $owner,
        DogMediaService $mediaService,
    ): Response {
        if (!$mediaService->clearProfile($dogId, $owner)) {
            throw $this->createNotFoundException('Dog not found');
        }

        return new Response(null, 204);
    }
}
