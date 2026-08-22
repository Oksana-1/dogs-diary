<?php

namespace App\Repository;

use App\Entity\Treatment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Treatment>
 */
class TreatmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Treatment::class);
    }

    /**
     * @return Treatment[]
     */
    public function findForDogAndOwner(int $dogId, User $owner): array
    {
        return $this->createQueryBuilder('treatment')
            ->innerJoin('treatment.dog', 'dog')
            ->innerJoin('dog.owners', 'owner')
            ->leftJoin('treatment.media', 'media')
            ->addSelect('media')
            ->andWhere('dog.id = :dogId')
            ->andWhere('owner = :owner')
            ->setParameter('dogId', $dogId)
            ->setParameter('owner', $owner)
            ->orderBy('treatment.treatmentDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForDogAndOwner(
        int $dogId,
        int $treatmentId,
        User $owner,
        ?LockMode $lockMode = null,
    ): ?Treatment {
        $query = $this->createQueryBuilder('treatment')
            ->innerJoin('treatment.dog', 'dog')
            ->innerJoin('dog.owners', 'owner')
            ->andWhere('treatment.id = :treatmentId')
            ->andWhere('dog.id = :dogId')
            ->andWhere('owner = :owner')
            ->setParameter('treatmentId', $treatmentId)
            ->setParameter('dogId', $dogId)
            ->setParameter('owner', $owner)
            ->getQuery();

        if (null !== $lockMode) {
            $query->setLockMode($lockMode);
        }

        return $query->getOneOrNullResult();
    }
}
