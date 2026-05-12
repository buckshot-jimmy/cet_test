<?php

namespace App\Repository;

use App\Entity\MesajAdmin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MesajAdmin|null find($id, $lockMode = null, $lockVersion = null)
 * @method MesajAdmin|null findOneBy(array $criteria, array $orderBy = null)
 * @method MesajAdmin[]    findAll()
 * @method MesajAdmin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MesajAdminRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private EntityManagerInterface $em)
    {
        parent::__construct($registry, MesajAdmin::class);
    }

    public function saveMesaj($mesaj, $activ)
    {
        $mesajUnic = $this->findOneBy(['activ' => 1], ['id' => 'ASC']);

        if (!$mesajUnic) {
            $mesajUnic = new MesajAdmin();
        }

        $mesajUnic->setMesaj($mesaj);
        $mesajUnic->setActiv($activ);

        try {
            $this->em->persist($mesajUnic);
            $this->em->flush();
        } catch (\Exception $exception) {
            throw new \Exception("Failed operation", 4001, $exception);
        }

        return $mesajUnic->getId();
    }

    public function getMesajAdmin()
    {
        try {
            $mesaj = $this->findOneBy(['activ' => 1], ['id' => 'ASC']);
        } catch (\Exception $exception) {
            throw new \Exception("Data collection error", 4001, $exception);
        }

        return $mesaj ? $mesaj->getMesaj() : '';
    }
}
