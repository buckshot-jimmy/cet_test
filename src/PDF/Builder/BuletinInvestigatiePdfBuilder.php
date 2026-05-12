<?php

namespace App\PDF\Builder;

use App\Entity\Consultatii;
use App\Entity\Pacienti;
use App\Entity\User;
use App\PDF\Contract\PdfDocumentBuilderInterface;
use App\PDF\DTO\PdfDocument;
use App\Services\UtilService;
use Doctrine\ORM\EntityManagerInterface;

class BuletinInvestigatiePdfBuilder implements PdfDocumentBuilderInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function getSupportedTemplates(): string
    {
        return 'buletin_investigatie.html.twig';
    }

    public function build($id): PdfDocument
    {
        $serviciu = $this->em->getRepository(Consultatii::class)->find($id);

        $pacient = $this->em->getRepository(Pacienti::class)->getPacient($serviciu->getPacient());
        $varsta = UtilService::calculeazaDatePacient($pacient['cnp'])['varsta'];
        $pacient['varsta'] = $varsta['ani'] . ' ani ';

        $data = [
            'nrInreg' => $serviciu->getNrInreg(),
            'serviciu' => $serviciu->getPret()->getServiciu(),
            'consultatie' => preg_split('/\n|\r\n?/', $serviciu->getConsultatie()),
            'tratament' => preg_split('/\n|\r\n?/', $serviciu->getTratament()),
            'medicTrimitator' => $serviciu->getMedicTrimitator(),
            'dataConsultatie' => $serviciu->getDataConsultatie()->format('d-m-Y'),
            'tipInvestigatie' => $serviciu->getPret()->getServiciu()->getDenumire(),
            'medic' => $this->em->getRepository(User::class)->getUser($serviciu->getPret()->getMedic()),
            'owner' => $serviciu->getPret()->getOwner(),
            'pacient' => $pacient,
            'formular' => 'Raport_' . $serviciu->getPret()->getServiciu()->getDenumire() . '_'
        ];

        return new PdfDocument(
            $data,
            UtilService::getDateFirma(),
            $data['formular'] . $data['pacient']['nume'] . " " . $data['pacient']['prenume']
            . strtotime('now') . ".pdf"
        );
    }
}