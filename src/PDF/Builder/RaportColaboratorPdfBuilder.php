<?php

namespace App\PDF\Builder;

use App\Entity\Consultatii;
use App\Entity\RapoarteColaboratori;
use App\PDF\Contract\PdfDocumentBuilderInterface;
use App\PDF\DTO\PdfDocument;
use App\Services\UtilService;
use Doctrine\ORM\EntityManagerInterface;

class RaportColaboratorPdfBuilder implements PdfDocumentBuilderInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function getSupportedTemplates(): string
    {
        return 'plata_colaborator.html.twig';
    }

    public function build($id): PdfDocument
    {
        $raport = $this->em->getRepository(RapoarteColaboratori::class)->find($id);

        $totalDePlata = 0;

        $pdfData = [
            'consultatii' => [],
            'medic' => $raport->getMedic()->getNume() . " " . $raport->getMedic()->getPrenume(),
            'owner' => $raport->getOwner()->getDenumire(),
            'an' => $raport->getAn(),
            'luna' => $raport->getLuna(),
            'stare' => $raport->getStare(),
        ];

        $consultatiiRaport = $this->em->getRepository(Consultatii::class)->getConsultatiiRaportColaborator($raport);

        foreach ($consultatiiRaport as $id) {
            $consultatie = $this->em->getRepository(Consultatii::class)->find($id);

            $tmp = [
                'dataConsultatie' => $consultatie->getDataConsultatie()->format('d-m-Y'),
                'numePacient' => $consultatie->getPacient()->getNume(),
                'prenumePacient' => $consultatie->getPacient()->getPrenume(),
                'cnpPacient' => $consultatie->getPacient()->getCnp(),
                'serviciu' => $consultatie->getPret()->getServiciu()->getDenumire(),
                'sumaIncasata' => $consultatie->getTarif(),
                'procentajColaborator' => $consultatie->getPret()->getProcentajMedic(),
                'sumaDePlata' => $consultatie->getTarif() * $consultatie->getPret()->getProcentajMedic() / 100
            ];

            $totalDePlata += $tmp['sumaDePlata'];

            $pdfData['consultatii'][] = $tmp;
        }

        $pdfData['totalDePlata'] = $totalDePlata;

        return new PdfDocument(
            $pdfData,
            UtilService::getDateFirma(),
            $pdfData['medic'] . '_' . $pdfData['owner'] . strtotime('now') . ".pdf"
        );
    }
}