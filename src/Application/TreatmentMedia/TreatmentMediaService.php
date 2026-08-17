<?php

namespace App\Application\TreatmentMedia;

use App\Application\Media\Exception\MediaValidationException;
use App\Application\Media\MediaStorageInterface;
use App\Application\Media\MediaUploadValidator;
use App\Entity\Treatment;
use App\Entity\TreatmentMedia;
use App\Enum\MediaOwnerTypeEnum;
use App\Enum\MediaTypeEnum;
use App\Repository\TreatmentMediaRepository;
use App\Repository\TreatmentRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class TreatmentMediaService
{
    public function __construct(
        private TreatmentRepository $treatmentRepository,
        private TreatmentMediaRepository $mediaRepository,
        private EntityManagerInterface $em,
        private MediaUploadValidator $uploadValidator,
        private MediaStorageInterface $storage,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return TreatmentMedia[]|null
     */
    public function listForTreatment(int $dogId, int $treatmentId): ?array
    {
        if (!$this->treatmentRepository->findOneForDog($dogId, $treatmentId)) {
            return null;
        }

        return $this->mediaRepository->findForTreatment($treatmentId);
    }

    public function upload(int $dogId, int $treatmentId, UploadedFile $file): ?TreatmentMedia
    {
        if (!$this->treatmentRepository->findOneForDog($dogId, $treatmentId)) {
            return null;
        }

        $metadata = $this->uploadValidator->validate($file);
        if (MediaTypeEnum::IMAGE !== $metadata->type) {
            throw new MediaValidationException('Treatments support image uploads only.', 415, 'file');
        }

        $storageKey = null;
        $replacedStorageKey = null;
        try {
            $media = $this->em->wrapInTransaction(function () use (
                $dogId,
                $treatmentId,
                $file,
                $metadata,
                &$storageKey,
                &$replacedStorageKey,
            ): ?TreatmentMedia {
                $treatment = $this->em->find(Treatment::class, $treatmentId, LockMode::PESSIMISTIC_WRITE);
                if (!$treatment || $treatment->getDog()?->getId() !== $dogId) {
                    return null;
                }

                $storageKey = $this->storage->store(
                    MediaOwnerTypeEnum::TREATMENT,
                    $treatmentId,
                    $file,
                    $metadata->extension,
                );

                $existingMedia = $this->mediaRepository->findForTreatment($treatmentId)[0] ?? null;
                if ($existingMedia) {
                    $replacedStorageKey = $existingMedia->getStorageKey();
                    $existingMedia->replaceFile(
                        storageKey: $storageKey,
                        originalName: $metadata->originalName,
                        mimeType: $metadata->mimeType,
                        sizeBytes: $metadata->sizeBytes,
                        width: $metadata->width,
                        height: $metadata->height,
                    );

                    return $existingMedia;
                }

                $media = new TreatmentMedia(
                    treatment: $treatment,
                    storageKey: $storageKey,
                    originalName: $metadata->originalName,
                    mimeType: $metadata->mimeType,
                    sizeBytes: $metadata->sizeBytes,
                    width: $metadata->width,
                    height: $metadata->height,
                    position: 1,
                );
                $this->em->persist($media);

                return $media;
            });
        } catch (\Throwable $exception) {
            if (null !== $storageKey) {
                try {
                    $this->storage->delete($storageKey);
                } catch (\Throwable $cleanupException) {
                    $this->logger->error('Failed to compensate a treatment media upload.', [
                        'storageKey' => $storageKey,
                        'exception' => $cleanupException,
                    ]);
                }
            }

            throw $exception;
        }

        if (null !== $replacedStorageKey) {
            $this->deleteStoredFile($replacedStorageKey, 'replacing');
        }

        return $media;
    }

    public function delete(int $dogId, int $treatmentId, int $mediaId): bool
    {
        $storageKey = $this->em->wrapInTransaction(function () use ($dogId, $treatmentId, $mediaId): ?string {
            $treatment = $this->em->find(Treatment::class, $treatmentId, LockMode::PESSIMISTIC_WRITE);
            if (!$treatment || $treatment->getDog()?->getId() !== $dogId) {
                return null;
            }

            $media = $this->mediaRepository->findOneForTreatment($treatmentId, $mediaId);
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

        $this->deleteStoredFile($storageKey, 'deleting');

        return true;
    }

    private function deleteStoredFile(string $storageKey, string $operation): void
    {
        try {
            $this->storage->delete($storageKey);
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to delete a treatment media file after '.$operation.' its database record.', [
                'storageKey' => $storageKey,
                'exception' => $exception,
            ]);
        }
    }
}
