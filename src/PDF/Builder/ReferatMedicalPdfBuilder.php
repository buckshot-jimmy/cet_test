<?php

namespace App\PDF\Builder;

use App\Entity\Consultatii;
use App\Entity\Pacienti;
use App\Entity\User;
use App\PDF\Contract\PdfDocumentBuilderInterface;
use App\PDF\DTO\PdfDocument;
use App\Services\UtilService;
use Doctrine\ORM\EntityManagerInterface;

class ReferatMedicalPdfBuilder implements PdfDocumentBuilderInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function getSupportedTemplates(): string
    {
        return 'referat_medical.html.twig';
    }

    public function build($id): PdfDocument
    {
        $consultatie = $this->em->getRepository(Consultatii::class)->find($id);

        $data = [
            'dataConsultatie' => $consultatie->getDataConsultatie()->format('d-m-Y'),
            'nrInreg' => $consultatie->getNrInreg(),
            'diagnostic' => preg_split('/\n|\r\n?/', $consultatie->getDiagnostic()),
            'ahc' => preg_split('/\n|\r\n?/', $consultatie->getAhc()),
            'app' => preg_split('/\n|\r\n?/', $consultatie->getApp()),
            'observatii' => preg_split('/\n|\r\n?/', $consultatie->getObservatii()),
            'consultatie' => preg_split('/\n|\r\n?/', $consultatie->getConsultatie()),
            'formular' => 'Referat_medical_',
            'pacient' => $this->em->getRepository(Pacienti::class)->getPacient($consultatie->getPacient()),
            'medic' => $this->em->getRepository(User::class)->getUser($consultatie->getPret()->getMedic()->getId()),
            'owner' => $consultatie->getPret()->getOwner(),
            'investigatiiUrmate' => preg_split('/\n|\r\n?/', $consultatie->getInvestigatiiUrmate()),
            'tratamenteUrmate' => preg_split('/\n|\r\n?/', $consultatie->getTratamenteUrmate())
        ];

        return new PdfDocument(
            $data,
            UtilService::getDateFirma(),
            $data['formular'] . $data['pacient']['nume'] . " " . $data['pacient']['prenume']
            . strtotime('now') . ".pdf"
        );
    }
}