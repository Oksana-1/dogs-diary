<?php

namespace App\Repository;

use App\Entity\TreatmentMedia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TreatmentMedia>
 */
final class TreatmentMediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TreatmentMedia::class);
    }

    /**
     * @return TreatmentMedia[]
     */
    public function findForTreatment(int $treatmentId): array
    {
        return $this->createQueryBuilder('media')
            ->andWhere('IDENTITY(media.treatment) = :treatmentId')
            ->setParameter('treatmentId', $treatmentId)
            ->orderBy('media.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForTreatment(int $treatmentId, int $mediaId): ?TreatmentMedia
    {
        return $this->createQueryBuilder('media')
            ->andWhere('media.id = :mediaId')
            ->andWhere('IDENTITY(media.treatment) = :treatmentId')
            ->setParameter('mediaId', $mediaId)
            ->setParameter('treatmentId', $treatmentId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return string[]
     */
    public function findAllStorageKeys(): array
    {
        $rows = $this->createQueryBuilder('media')
            ->select('media.storageKey')
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'storageKey');
    }
}
