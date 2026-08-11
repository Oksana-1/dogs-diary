<?php

namespace App\Repository;

use App\Entity\Treatment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function findOneForDog(int $dogId, int $treatmentId): ?Treatment
    {
        return $this->createQueryBuilder('treatment')
            ->andWhere('treatment.id = :treatmentId')
            ->andWhere('IDENTITY(treatment.dog) = :dogId')
            ->setParameter('treatmentId', $treatmentId)
            ->setParameter('dogId', $dogId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return Treatment[] Returns an array of Treatment objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Treatment
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
