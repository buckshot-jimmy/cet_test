<?php

namespace App\Services;

use App\Entity\Consultatie;
use App\Entity\FacturaConsultatie;
use App\Entity\Factura;
use App\Entity\Owner;
use App\Entity\Pacient;
use App\Entity\PersoanaJuridica;
use Doctrine\ORM\EntityManagerInterface;

class FacturaService
{
    const FACTURA = 0;

    public function __construct(private EntityManagerInterface $em) {}

    public function prepareInvoice($data)
    {
        $factura = new Factura();
        $date = new \DateTime();

        $furnizor = $this->em->getRepository(Owner::class)->find($data['owner_factura']);
        $lastInvoice = $this->em->getRepository(Factura::class)
            ->findOneBy(['owner' => $furnizor->getId()], ['id' => 'DESC']);

        $factura->setData(new \DateTime());
        $factura->setOwner($furnizor);
        $factura->setTip(self::FACTURA);
        $factura->setSerie($furnizor->getSerieFactura());
        $factura->setNumar($lastInvoice ? $lastInvoice->getNumar() + 1 : 1);
        $factura->setScadenta($date->add(new \DateInterval('P7D')));

        foreach (explode(',', $data['consultatii_factura']) as $cf) {
            $facturaConsultatie = new FacturaConsultatie();
            $facturaConsultatie->setFactura($factura);
            $consultatie = $this->em->getRepository(Consultatie::class)->find($cf);
            $consultatie->setFacturata(true);
            $facturaConsultatie->setConsultatie($consultatie);
            $facturaConsultatie->setValoare($consultatie->getTarif());

            $factura->addFacturaConsultatii($facturaConsultatie);
        }

        if (isset($data['factura_pacient'])) {
            $pacient = $this->em->getRepository(Pacient::class)->find($data['factura_pacient']);
            $factura->setPacient($pacient);
            $factura->setClientPj(null);
        }
        if (isset($data['factura_pj'])) {
            $pj = $this->em->getRepository(PersoanaJuridica::class)->find($data['factura_pj']);
            $factura->setPacient(null);
            $factura->setClientPj($pj);
        }

        return $factura;
    }
}