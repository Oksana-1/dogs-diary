<?php

namespace App\Repository;

use App\Entity\DogMedia;
use App\Entity\User;
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
    public function findForDogAndOwner(int $dogId, User $owner): array
    {
        return $this->createQueryBuilder('media')
            ->innerJoin('media.dog', 'dog')
            ->innerJoin('dog.owners', 'owner')
            ->andWhere('dog.id = :dogId')
            ->andWhere('owner = :owner')
            ->setParameter('dogId', $dogId)
            ->setParameter('owner', $owner)
            ->orderBy('media.createdAt', 'DESC')
            ->addOrderBy('media.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForDogAndOwner(int $dogId, int $mediaId, User $owner): ?DogMedia
    {
        return $this->createQueryBuilder('media')
            ->innerJoin('media.dog', 'dog')
            ->innerJoin('dog.owners', 'owner')
            ->andWhere('media.id = :mediaId')
            ->andWhere('dog.id = :dogId')
            ->andWhere('owner = :owner')
            ->setParameter('mediaId', $mediaId)
            ->setParameter('dogId', $dogId)
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
