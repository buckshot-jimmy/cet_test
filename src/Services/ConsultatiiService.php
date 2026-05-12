<?php

namespace App\Services;

use App\Entity\Consultatii;
use Doctrine\ORM\EntityManagerInterface;

class ConsultatiiService
{
    public function __construct(private EntityManagerInterface $em, private NomenclatoareService $service) {}

    public function calculeazaConsultatiiPeLuni($user)
    {
        $consultatii = ['luni' => [], 'consultatii' => []];
        $consultatiiMedic = ['luni' => [], 'consultatii' => []];
        $filter = ['an' => date('Y')];
        $luni = $this->service->getLunileAnului();
        $repo = $this->em->getRepository(Consultatii::class);

        for ($luna = 1; $luna <= date('m'); $luna++) {
            $filter['luna'] = $luna;
            $consultatiiData = $repo->numarConsultatiiPeLuni($filter);
            $consultatii['luni'][] = $luni[$luna];
            $consultatii['consultatii'][] = $consultatiiData['totalConsultatiiLuna'] ?? 0;

            $filter['medic'] = $user->getId();
            $consultatiiMedic['luni'][] = $luni[$luna];
            $consultatiiMedicData = $repo->numarConsultatiiPeLuni($filter);
            $consultatiiMedic['consultatii'][] = $consultatiiMedicData['totalConsultatiiLuna'] ?? 0;
            unset($filter['medic']);
        }

        return ['consultatii' => $consultatii, 'consultatiiMedic' => $consultatiiMedic];
    }

    public function calculeazaIncasariMedicPeLuni($userId)
    {
        $repo = $this->em->getRepository(Consultatii::class);
        $luni = $this->service->getLunileAnului();

        $incasariMedic = [];
        $filter = ['an' => date('Y'), 'medic' => $userId];

        for ($luna = 1; $luna <= date('m'); $luna++) {
            $filter['luna'] = $luna;
            $incasariMedic['luni'][] = $luni[$luna];

            $incasariMedicData = $repo->valoareServicii($filter)[0] ?? ['valoare' => '0', 'comision' => '0'];

            $incasariMedic['incasari'][] = $incasariMedicData['valoare'] ?? 0;
            $incasariMedic['comision'][] = $incasariMedicData ['comision'] ?? 0;
        }

        return $incasariMedic;
    }
}