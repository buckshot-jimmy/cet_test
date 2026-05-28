<?php


namespace App\EventListener;


use App\Entity\Consultatie;
use App\Entity\RaportColaborator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class RaportColaboratorListener
{
    public function __construct(private EntityManagerInterface $em) {}

    public function postUpdate(RaportColaborator $raportColaborator)
    {
        $consultatiiDePlatit = $this->em->getRepository(Consultatie::class)
            ->getConsultatiiRaportColaborator($raportColaborator, false);

        foreach ($consultatiiDePlatit as $consInvId) {
            $consInvDePlatit = $this->em->getRepository(Consultatie::class)->find($consInvId['id']);
            $consInvDePlatit->setPlatitaColaborator(true);
            $this->em->persist($consInvDePlatit);
            $this->em->flush();
        }

        return true;
    }
}