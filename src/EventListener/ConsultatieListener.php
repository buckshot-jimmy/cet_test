<?php


namespace App\EventListener;

use App\Entity\Consultatie;
use App\Entity\MedicTrimitator;
use Doctrine\ORM\EntityManagerInterface;

class ConsultatieListener
{
    public function __construct(private EntityManagerInterface $em) {}

    public function postUpdate(Consultatie $consultatie)
    {
        $medicTrimitatorNume = $consultatie->getMedicTrimitator();

        if (!$medicTrimitatorNume) {
            return true;
        }

        $repo = $this->em->getRepository(MedicTrimitator::class);

        if ($repo->findOneBy(['nume' => $medicTrimitatorNume])) {
            return true;
        }

        $medicTrimitator = new MedicTrimitator();
        $medicTrimitator->setNume($medicTrimitatorNume);

        $this->em->persist($medicTrimitator);
        $this->em->flush();

        return true;
    }
}