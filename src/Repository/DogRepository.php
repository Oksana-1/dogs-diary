<?php

namespace App\Repository;

use App\Entity\Dog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dog>
 */
class DogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dog::class);
    }

    /**
     * @return Dog[]
     */
    public function findAllWithMediaForOwner(User $owner): array
    {
        return $this->createQueryBuilder('dog')
            ->innerJoin('dog.owners', 'owner')
            ->leftJoin('dog.media', 'media')
            ->addSelect('media')
            ->andWhere('owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('dog.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findWithMediaForOwner(int $id, User $owner): ?Dog
    {
        return $this->createQueryBuilder('dog')
            ->innerJoin('dog.owners', 'owner')
            ->leftJoin('dog.media', 'media')
            ->addSelect('media')
            ->andWhere('dog.id = :id')
            ->andWhere('owner = :owner')
            ->setParameter('id', $id)
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findForOwner(int $id, User $owner, ?LockMode $lockMode = null): ?Dog
    {
        $query = $this->createQueryBuilder('dog')
            ->innerJoin('dog.owners', 'owner')
            ->andWhere('dog.id = :id')
            ->andWhere('owner = :owner')
            ->setParameter('id', $id)
            ->setParameter('owner', $owner)
            ->getQuery();

        if (null !== $lockMode) {
            $query->setLockMode($lockMode);
        }

        return $query->getOneOrNullResult();
    }
}
