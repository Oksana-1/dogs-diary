<?php

namespace App\Repository;

use App\Entity\DogMedia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DogMedia>
 */
final class DogMediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DogMedia::class);
    }

    /**
     * @return DogMedia[]
     */
    public function findForDog(int $dogId): array
    {
        return $this->createQueryBuilder('media')
            ->andWhere('IDENTITY(media.dog) = :dogId')
            ->setParameter('dogId', $dogId)
            ->orderBy('media.createdAt', 'DESC')
            ->addOrderBy('media.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForDog(int $dogId, int $mediaId): ?DogMedia
    {
        return $this->createQueryBuilder('media')
            ->andWhere('media.id = :mediaId')
            ->andWhere('IDENTITY(media.dog) = :dogId')
            ->setParameter('mediaId', $mediaId)
            ->setParameter('dogId', $dogId)
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
