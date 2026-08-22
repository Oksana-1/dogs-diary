<?php

namespace App\Repository;

use App\Entity\TreatmentMedia;
use App\Entity\User;
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
    public function findForTreatmentAndOwner(int $dogId, int $treatmentId, User $owner): array
    {
        return $this->createQueryBuilder('media')
            ->innerJoin('media.treatment', 'treatment')
            ->innerJoin('treatment.dog', 'dog')
            ->innerJoin('dog.owners', 'owner')
            ->andWhere('treatment.id = :treatmentId')
            ->andWhere('dog.id = :dogId')
            ->andWhere('owner = :owner')
            ->setParameter('dogId', $dogId)
            ->setParameter('treatmentId', $treatmentId)
            ->setParameter('owner', $owner)
            ->orderBy('media.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForTreatmentAndOwner(
        int $dogId,
        int $treatmentId,
        int $mediaId,
        User $owner,
    ): ?TreatmentMedia {
        return $this->createQueryBuilder('media')
            ->innerJoin('media.treatment', 'treatment')
            ->innerJoin('treatment.dog', 'dog')
            ->innerJoin('dog.owners', 'owner')
            ->andWhere('media.id = :mediaId')
            ->andWhere('treatment.id = :treatmentId')
            ->andWhere('dog.id = :dogId')
            ->andWhere('owner = :owner')
            ->setParameter('mediaId', $mediaId)
            ->setParameter('dogId', $dogId)
            ->setParameter('treatmentId', $treatmentId)
            ->setParameter('owner', $owner)
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
