<?php

namespace App\Application\Media;

use App\Application\DogMedia\DogMediaService;
use App\Application\Media\Exception\MediaValidationException;
use App\Application\TreatmentMedia\TreatmentMediaService;
use App\Entity\DogMedia;
use App\Entity\MediaUpload;
use App\Entity\TreatmentMedia;
use App\Entity\User;
use App\Repository\DogRepository;
use App\Repository\MediaUploadRepository;
use App\Repository\TreatmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class MediaUploadService
{
    public function __construct(
        private MediaStorageInterface $storage,
        private DogRepository $dogs,
        private TreatmentRepository $treatments,
        private MediaUploadRepository $uploads,
        private DogMediaService $dogMedia,
        private TreatmentMediaService $treatmentMedia,
        private MediaUploadValidator $validator,
        private EntityManagerInterface $em,
        private LockFactory $lockFactory,
        private RateLimiterFactory $mediaUploadLimiter,
    ) {
    }

    public function initiate(int $dogId, ?int $treatmentId, User $owner, string $name, string $mimeType, int $sizeBytes): array
    {
        $dog = $this->dogs->findForOwner($dogId, $owner);
        $treatment = null === $treatmentId ? null : $this->treatments->findOneForDogAndOwner($dogId, $treatmentId, $owner);
        if (null === $dog || (null !== $treatmentId && null === $treatment)) {
            throw new NotFoundHttpException('Media owner not found.');
        }
        if (!$this->storage instanceof DirectUploadStorageInterface) {
            return ['mode' => 'multipart'];
        }
        $this->validator->validateDeclaration($name, $mimeType, $sizeBytes, null !== $treatmentId);
        if (!$this->mediaUploadLimiter->create((string) $owner->getId())->consume()->isAccepted()) {
            throw new MediaValidationException('Too many upload attempts. Please try again later.', 429, 'file');
        }
        $this->uploads->removeExpired();
        $upload = new MediaUpload($owner, $dog, $treatment, $name, $mimeType, $sizeBytes);
        $url = $this->storage->createUploadUrl($upload->getId(), $mimeType);
        $this->em->persist($upload);
        $this->em->flush();

        return ['mode' => 'direct', 'uploadId' => $upload->getId(), 'uploadUrl' => $url, 'contentType' => $mimeType];
    }

    public function complete(string $id, int $dogId, ?int $treatmentId, User $owner): DogMedia|TreatmentMedia
    {
        $this->assertOwner($dogId, $treatmentId, $owner);
        if (!$this->storage instanceof DirectUploadStorageInterface) {
            throw new NotFoundHttpException('Direct media upload is not configured.');
        }
        $lock = $this->lockFactory->createLock('media-upload:'.$id, 900);
        if (!$lock->acquire()) {
            throw new MediaValidationException('This upload is being finalized. Please try again.', 409, 'file');
        }
        try {
            $upload = $this->uploads->findForOwner($id, $dogId, $treatmentId, $owner);
            if (null === $upload) {
                throw new NotFoundHttpException('Upload not found.');
            }
            $this->em->refresh($upload);
            if ($upload->isExpired()) {
                throw new MediaValidationException('The upload has expired. Please upload the file again.', 410, 'file');
            }
            if ($upload->isCompleted()) {
                $result = $upload->getResult();
                if (null === $result) {
                    throw new MediaValidationException('The uploaded media has been removed.', 410, 'file');
                }

                return $result;
            }
            if (!$this->mediaUploadLimiter->create('finalize:'.$owner->getId())->consume()->isAccepted()) {
                throw new MediaValidationException('Too many upload attempts. Please try again later.', 429, 'file');
            }
            $file = $this->storage->downloadUpload($id, $upload->getOriginalName(), $upload->getMimeType(), $upload->getSizeBytes());
            try {
                $metadata = $this->validator->validate($file);
                if ($metadata->mimeType !== $upload->getMimeType() || $metadata->sizeBytes !== $upload->getSizeBytes()) {
                    throw new MediaValidationException('The uploaded content does not match the upload request.', 415, 'file');
                }
                $media = null === $treatmentId
                    ? $this->dogMedia->upload($dogId, $file, $owner, $upload)
                    : $this->treatmentMedia->upload($dogId, $treatmentId, $file, $owner, $upload);
                if (null === $media) {
                    throw new NotFoundHttpException('Media owner not found.');
                }

                return $media;
            } finally {
                if (is_file($file->getPathname())) {
                    unlink($file->getPathname());
                }
            }
        } finally {
            $lock->release();
        }
    }

    private function assertOwner(int $dogId, ?int $treatmentId, User $owner): void
    {
        if (null === $this->dogs->findForOwner($dogId, $owner)
            || (null !== $treatmentId && null === $this->treatments->findOneForDogAndOwner($dogId, $treatmentId, $owner))) {
            throw new NotFoundHttpException('Media owner not found.');
        }
    }
}
