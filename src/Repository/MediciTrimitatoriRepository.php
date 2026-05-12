<?php

namespace App\Repository;

use App\Entity\MediciTrimitatori;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MediciTrimitatori|null find($id, $lockMode = null, $lockVersion = null)
 * @method MediciTrimitatori|null findOneBy(array $criteria, array $orderBy = null)
 * @method MediciTrimitatori[]    findAll()
 * @method MediciTrimitatori[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MediciTrimitatoriRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediciTrimitatori::class);
    }
}
