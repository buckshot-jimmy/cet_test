<?php


namespace App\EventListener;

use App\Entity\Consultatii;
use App\Entity\MediciTrimitatori;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class SaveConsultatieListener
{
    public function __construct(private EntityManagerInterface $em) {}

    public function postUpdate(Consultatii $consultatie)
    {
        $medicTrimitatorNume = $consultatie->getMedicTrimitator();

        if (!$medicTrimitatorNume) {
            return true;
        }

        $repo = $this->em->getRepository(MediciTrimitatori::class);

        if ($repo->findOneBy(['nume' => $medicTrimitatorNume])) {
            return true;
        }

        $medicTrimitator = new MediciTrimitatori();
        $medicTrimitator->setNume($medicTrimitatorNume);

        $this->em->persist($medicTrimitator);
        $this->em->flush();

        return true;
    }
}