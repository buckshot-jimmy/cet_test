<?php

namespace App\PDF\Builder;

use App\Entity\Consultatii;
use App\Entity\Pacienti;
use App\PDF\Contract\PdfDocumentBuilderInterface;
use App\PDF\DTO\PdfDocument;
use App\Services\UtilService;
use Doctrine\ORM\EntityManagerInterface;

class ScrisoareMedicalaPdfBuilder implements PdfDocumentBuilderInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function getSupportedTemplates(): string
    {
        return 'scrisoare_medicala.html.twig';
    }

    public function build($id): PdfDocument
    {
        $serviciu = $this->em->getRepository(Consultatii::class)->find($id);

        $data = [
            'dataConsultatie' => $serviciu->getDataConsultatie()->format('d-m-Y'),
            'consultatie' => preg_split('/\n|\r\n?/', $serviciu->getConsultatie()),
            'tratament' => preg_split('/\n|\r\n?/', $serviciu->getTratament()),
            'diagnostic' => preg_split('/\n|\r\n?/', $serviciu->getDiagnostic()),
            'nrInreg' => $serviciu->getNrInreg(),
            'ahc' => preg_split('/\n|\r\n?/', $serviciu->getAhc()),
            'app' => preg_split('/\n|\r\n?/', $serviciu->getApp()),
            'medic' => $serviciu->getPret()->getMedic()->getNume()." ".$serviciu->getPret()->getMedic()->getPrenume(),
            'titulatura' => $serviciu->getPret()->getMedic()->getTitulatura()->getDenumire(),
            'specialitate' => $serviciu->getPret()->getMedic()->getSpecialitate()->getDenumire(),
            'parafa' => $serviciu->getPret()->getMedic()->getCodParafa(),
            'owner' => $serviciu->getPret()->getOwner()->getDenumire(),
            'ownerCui' => $serviciu->getPret()->getOwner()->getCui(),
            'tratamenteUrmate' => preg_split('/\n|\r\n?/', $serviciu->getTratamenteUrmate()),
            'investigatiiUrmate' => preg_split('/\n|\r\n?/', $serviciu->getInvestigatiiUrmate()),
            'pacient' => $this->em->getRepository(Pacienti::class)->getPacient($serviciu->getPacient()),
            'formular' => 'Scrisoare_medicala_'
        ];

        return new PdfDocument(
            $data,
            UtilService::getDateFirma(),
            $data['formular'] . $data['pacient']['nume'] . " " . $data['pacient']['prenume']
            . strtotime('now') . ".pdf"
        );
    }
}