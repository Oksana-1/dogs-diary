<?php

namespace App\Repository;

use App\Entity\MediaUpload;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MediaUploadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediaUpload::class);
    }

    public function findForOwner(string $id, int $dogId, ?int $treatmentId, User $owner): ?MediaUpload
    {
        $builder = $this->createQueryBuilder('upload')
            ->andWhere('upload.id = :id AND upload.owner = :owner AND IDENTITY(upload.dog) = :dog')
            ->setParameter('id', $id)->setParameter('owner', $owner)->setParameter('dog', $dogId);
        if (null === $treatmentId) {
            $builder->andWhere('upload.treatment IS NULL');
        } else {
            $builder->andWhere('IDENTITY(upload.treatment) = :treatment')->setParameter('treatment', $treatmentId);
        }

        return $builder->getQuery()->getOneOrNullResult();
    }

    public function removeExpired(): int
    {
        return $this->createQueryBuilder('upload')->delete()
            ->andWhere('upload.expiresAt < :cutoff')
            ->setParameter('cutoff', new \DateTimeImmutable('-1 day'))
            ->getQuery()->execute();
    }
}
