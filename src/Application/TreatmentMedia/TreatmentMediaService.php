<?php

namespace App\Application\TreatmentMedia;

use App\Application\Media\Exception\MediaValidationException;
use App\Application\Media\MediaStorageInterface;
use App\Application\Media\MediaUploadValidator;
use App\Entity\TreatmentMedia;
use App\Entity\User;
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
    public function listForTreatment(int $dogId, int $treatmentId, User $owner): ?array
    {
        if (!$this->treatmentRepository->findOneForDogAndOwner($dogId, $treatmentId, $owner)) {
            return null;
        }

        return $this->mediaRepository->findForTreatmentAndOwner($dogId, $treatmentId, $owner);
    }

    public function upload(int $dogId, int $treatmentId, UploadedFile $file, User $owner): ?TreatmentMedia
    {
        if (!$this->treatmentRepository->findOneForDogAndOwner($dogId, $treatmentId, $owner)) {
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
                $owner,
                &$storageKey,
                &$replacedStorageKey,
            ): ?TreatmentMedia {
                $treatment = $this->treatmentRepository->findOneForDogAndOwner(
                    $dogId,
                    $treatmentId,
                    $owner,
                    LockMode::PESSIMISTIC_WRITE,
                );
                if (!$treatment) {
                    return null;
                }

                $storageKey = $this->storage->store(
                    MediaOwnerTypeEnum::TREATMENT,
                    $treatmentId,
                    $file,
                    $metadata->extension,
                );

                $existingMedia = $this->mediaRepository
                    ->findForTreatmentAndOwner($dogId, $treatmentId, $owner)[0] ?? null;
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

    public function get(int $dogId, int $treatmentId, int $mediaId, User $owner): ?TreatmentMedia
    {
        return $this->mediaRepository->findOneForTreatmentAndOwner($dogId, $treatmentId, $mediaId, $owner);
    }

    public function delete(int $dogId, int $treatmentId, int $mediaId, User $owner): bool
    {
        $storageKey = $this->em->wrapInTransaction(function () use ($dogId, $treatmentId, $mediaId, $owner): ?string {
            $treatment = $this->treatmentRepository->findOneForDogAndOwner(
                $dogId,
                $treatmentId,
                $owner,
                LockMode::PESSIMISTIC_WRITE,
            );
            if (!$treatment) {
                return null;
            }

            $media = $this->mediaRepository->findOneForTreatmentAndOwner(
                $dogId,
                $treatmentId,
                $mediaId,
                $owner,
            );
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
