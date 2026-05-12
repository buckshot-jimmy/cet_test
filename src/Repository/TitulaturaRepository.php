<?php

namespace App\Repository;

use App\Entity\Titulatura;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Titulatura|null find($id, $lockMode = null, $lockVersion = null)
 * @method Titulatura|null findOneBy(array $criteria, array $orderBy = null)
 * @method Titulatura[]    findAll()
 * @method Titulatura[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TitulaturaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Titulatura::class);
    }
}
