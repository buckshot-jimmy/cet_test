<?php

namespace App\Repository;

use App\Entity\MedicTrimitator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MedicTrimitator|null find($id, $lockMode = null, $lockVersion = null)
 * @method MedicTrimitator|null findOneBy(array $criteria, array $orderBy = null)
 * @method MedicTrimitator[]    findAll()
 * @method MedicTrimitator[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MedicTrimitatorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MedicTrimitator::class);
    }
}
