<?php


namespace App\EventListener;


use App\Entity\Consultatii;
use App\Entity\RapoarteColaboratori;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class PlatesteColaboratorListener
{
    public function __construct(private EntityManagerInterface $em) {}

    public function postUpdate(RapoarteColaboratori $raportColaborator)
    {
        $consultatiiDePlatit = $this->em->getRepository(Consultatii::class)
            ->getConsultatiiRaportColaborator($raportColaborator, false);

        foreach ($consultatiiDePlatit as $consInvId) {
            $consInvDePlatit = $this->em->getRepository(Consultatii::class)->find($consInvId['id']);
            $consInvDePlatit->setPlatitaColaborator(true);
            $this->em->persist($consInvDePlatit);
            $this->em->flush();
        }

        return true;
    }
}