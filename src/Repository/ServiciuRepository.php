<?php

namespace App\Repository;

use App\Entity\Serviciu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Serviciu|null find($id, $lockMode = null, $lockVersion = null)
 * @method Serviciu|null findOneBy(array $criteria, array $orderBy = null)
 * @method Serviciu[]    findAll()
 * @method Serviciu[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ServiciuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private EntityManagerInterface $em)
    {
        parent::__construct($registry, Serviciu::class);
    }

    public function saveServiciu($formData)
    {
        $serviciu = new Serviciu();

        $serviciu->setDenumire($formData['add_denumire_serviciu']);
        $serviciu->setTip($formData['add_tip_serviciu']);
        $serviciu->setSters(false);

        try {
            $this->em->persist($serviciu);
            $this->em->flush();
        } catch (\Exception $exception) {
            throw new \Exception("Failed operation", 5001, $exception);
        }

        return $serviciu->getId();
    }

    public function getAllServicii()
    {
        try {
            $servicii = $this->createQueryBuilder('servicii')
                ->where('servicii.sters = :sters')
                ->setParameter(':sters', false)
                ->getQuery()
                ->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $exception) {
            throw new \Exception("Data collection error", 4001, $exception);
        }

        return $servicii;
    }
}
