<?php

namespace App\PDF\Builder;

use App\Entity\Consultatie;
use App\Entity\Pacient;
use App\PDF\Contract\PdfDocumentBuilderInterface;
use App\PDF\DTO\PdfDocument;
use App\Services\NomenclatoareService;
use App\Services\UtilService;
use Doctrine\ORM\EntityManagerInterface;

class FisaConsultatiePdfBuilder implements PdfDocumentBuilderInterface
{
    const TIP_CONSULTATIE = 0;

    public function __construct(private EntityManagerInterface $em) {}

    public function getSupportedTemplates(): string
    {
        return 'fisa_consultatii.html.twig';
    }

    public function build($id): PdfDocument
    {
        $serviciu = $this->em->getRepository(Consultatie::class)->find($id);

        $pacient = $serviciu->getPacient();

        $consultatii = $this->em->getRepository(Consultatie::class)->getIstoricConsultatiiPentruFisa(
            $pacient, $serviciu->getPret()->getMedic(), self::TIP_CONSULTATIE);

        foreach ($consultatii as &$consultatie) {
            $consultatie['ahc'] = preg_split('/\n|\r\n?/', $consultatie['ahc']);
            $consultatie['app'] = preg_split('/\n|\r\n?/', $consultatie['app']);
        }

        $data = [
            'consultatii' => $consultatii,
            'pacient' => $this->em->getRepository(Pacient::class)->getPacient($pacient->getId()),
            'medic' => $serviciu->getPret()->getMedic(),
            'stareCivila' => (new NomenclatoareService())->getStariCivile()[$pacient->getStareCivila()],
            'formular' => 'Fisa_consultatii_'
        ];

        return new PdfDocument(
            $data,
            UtilService::getDateFirma(),
            $data['formular'] . $data['pacient']['nume'] . " " . $data['pacient']['prenume']
            . strtotime('now') . ".pdf"
        );
    }
}