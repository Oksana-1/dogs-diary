<?php

namespace App\Application\DogMedia;

use App\Application\Media\Exception\MediaValidationException;
use App\Application\Media\MediaStorageInterface;
use App\Application\Media\MediaUploadValidator;
use App\Entity\DogMedia;
use App\Entity\User;
use App\Enum\MediaOwnerTypeEnum;
use App\Enum\MediaTypeEnum;
use App\Repository\DogMediaRepository;
use App\Repository\DogRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class DogMediaService
{
    public function __construct(
        private DogRepository $dogRepository,
        private DogMediaRepository $mediaRepository,
        private EntityManagerInterface $em,
        private MediaUploadValidator $uploadValidator,
        private MediaStorageInterface $storage,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return DogMedia[]|null
     */
    public function listForDog(int $dogId, User $owner): ?array
    {
        if (!$this->dogRepository->findForOwner($dogId, $owner)) {
            return null;
        }

        return $this->mediaRepository->findForDogAndOwner($dogId, $owner);
    }

    public function upload(int $dogId, UploadedFile $file, User $owner): ?DogMedia
    {
        $dog = $this->dogRepository->findForOwner($dogId, $owner);
        if (!$dog) {
            return null;
        }

        $metadata = $this->uploadValidator->validate($file);
        $storageKey = $this->storage->store(MediaOwnerTypeEnum::DOG, $dogId, $file, $metadata->extension);
        $media = new DogMedia(
            dog: $dog,
            type: $metadata->type,
            storageKey: $storageKey,
            originalName: $metadata->originalName,
            mimeType: $metadata->mimeType,
            sizeBytes: $metadata->sizeBytes,
            width: $metadata->width,
            height: $metadata->height,
        );

        try {
            $this->em->persist($media);
            $this->em->flush();
        } catch (\Throwable $exception) {
            try {
                $this->storage->delete($storageKey);
            } catch (\Throwable $cleanupException) {
                $this->logger->error('Failed to compensate a dog media upload.', [
                    'storageKey' => $storageKey,
                    'exception' => $cleanupException,
                ]);
            }

            throw $exception;
        }

        return $media;
    }

    public function delete(int $dogId, int $mediaId, User $owner): bool
    {
        $storageKey = $this->em->wrapInTransaction(function () use ($dogId, $mediaId, $owner): ?string {
            $dog = $this->dogRepository->findForOwner($dogId, $owner, LockMode::PESSIMISTIC_WRITE);
            if (!$dog) {
                return null;
            }

            $media = $this->mediaRepository->findOneForDogAndOwner($dogId, $mediaId, $owner);
            if (!$media) {
                return null;
            }

            $storageKey = $media->getStorageKey();
            $this->em->remove($media);

            return $storageKey;
        });

        if (null === $storageKey) {
            return false;
        }

        $this->deleteStoredFile($storageKey);

        return true;
    }

    public function selectThumbnail(int $dogId, int $mediaId, User $owner): ?DogMedia
    {
        return $this->selectRole($dogId, $mediaId, $owner, true);
    }

    public function selectProfile(int $dogId, int $mediaId, User $owner): ?DogMedia
    {
        return $this->selectRole($dogId, $mediaId, $owner, false);
    }

    public function clearThumbnail(int $dogId, User $owner): bool
    {
        return $this->clearRole($dogId, $owner, true);
    }

    public function clearProfile(int $dogId, User $owner): bool
    {
        return $this->clearRole($dogId, $owner, false);
    }

    private function selectRole(int $dogId, int $mediaId, User $owner, bool $thumbnail): ?DogMedia
    {
        return $this->em->wrapInTransaction(function () use ($dogId, $mediaId, $owner, $thumbnail): ?DogMedia {
            $dog = $this->dogRepository->findForOwner($dogId, $owner, LockMode::PESSIMISTIC_WRITE);
            if (!$dog) {
                return null;
            }

            $media = $this->mediaRepository->findOneForDogAndOwner($dogId, $mediaId, $owner);
            if (!$media) {
                return null;
            }

            if ($thumbnail && MediaTypeEnum::IMAGE !== $media->getType()) {
                throw new MediaValidationException('Only an image can be used as a thumbnail.', field: 'mediaId');
            }

            if ($thumbnail) {
                $dog->selectThumbnailMedia($media);
            } else {
                $dog->selectProfileMedia($media);
            }

            return $media;
        });
    }

    private function clearRole(int $dogId, User $owner, bool $thumbnail): bool
    {
        return $this->em->wrapInTransaction(function () use ($dogId, $owner, $thumbnail): bool {
            $dog = $this->dogRepository->findForOwner($dogId, $owner, LockMode::PESSIMISTIC_WRITE);
            if (!$dog) {
                return false;
            }

            if ($thumbnail) {
                $dog->clearThumbnailMedia();
            } else {
                $dog->clearProfileMedia();
            }

            return true;
        });
    }

    private function deleteStoredFile(string $storageKey): void
    {
        try {
            $this->storage->delete($storageKey);
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to delete a dog media file after removing its database row.', [
                'storageKey' => $storageKey,
                'exception' => $exception,
            ]);
        }
    }
}
