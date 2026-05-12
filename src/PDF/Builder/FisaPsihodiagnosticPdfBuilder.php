<?php

namespace App\PDF\Builder;

use App\Entity\Consultatii;
use App\Entity\Pacienti;
use App\Entity\User;
use App\PDF\Contract\PdfDocumentBuilderInterface;
use App\PDF\DTO\PdfDocument;
use App\Services\UtilService;
use Doctrine\ORM\EntityManagerInterface;

class FisaPsihodiagnosticPdfBuilder implements PdfDocumentBuilderInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function getSupportedTemplates(): string
    {
        return 'fisa_psihodiagnostic.html.twig';
    }

    public function build($id): PdfDocument
    {
        $evaluare = $this->em->getRepository(Consultatii::class)->find($id);

        $data = [
            'obiectiv' => preg_split('/\n|\r\n?/', $evaluare->getConsultatie()),
            'recomandari' => preg_split('/\n|\r\n?/', $evaluare->getTratament()),
            'concluzii' => preg_split('/\n|\r\n?/', $evaluare->getDiagnostic()),
            'nrInreg' => $evaluare->getNrInreg(),
            'dataConsultatie' => $evaluare->getDataConsultatie()->format('d-m-Y'),
            'titulatura' => $evaluare->getPret()->getMedic()->getTitulatura(),
            'specialitate' => $evaluare->getPret()->getMedic()->getSpecialitate(),
            'parafa' => $evaluare->getPret()->getMedic()->getCodParafa(),
            'owner' => $evaluare->getPret()->getOwner(),
            'medic' => $this->em->getRepository(User::class)->getUser($evaluare->getPret()->getMedic()),
            'pacient' => $this->em->getRepository(Pacienti::class)->getPacient($evaluare->getPacient()),
            'formular' => 'Fisa_psihodiagnostic_',
            'evalPsiho' => unserialize($evaluare->getEvalPsiho())
        ];

        return new PdfDocument(
            $data,
            UtilService::getDateFirma(),
            $data['formular'] . $data['pacient']['nume'] . " " . $data['pacient']['prenume']
            . strtotime('now') . ".pdf"
        );
    }
}