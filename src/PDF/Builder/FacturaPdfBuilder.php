<?php

namespace App\PDF\Builder;

use App\Entity\Facturi;
use App\PDF\Contract\PdfDocumentBuilderInterface;
use App\PDF\DTO\PdfDocument;
use App\Services\UtilService;
use Doctrine\ORM\EntityManagerInterface;

class FacturaPdfBuilder implements PdfDocumentBuilderInterface
{
    const STORNO = 1;

    public function __construct(private EntityManagerInterface $em) {}

    public function getSupportedTemplates(): string
    {
        return 'factura.html.twig';
    }

    public function build($id): PdfDocument
    {
        $factura = $this->em->getRepository(Facturi::class)->find($id);

        $total = 0;
        $consultatii = [];

        foreach ($factura->getFacturaConsultatii() as $fc) {
            $valoare = $fc->getValoare();
            $total += $valoare;

            $consultatie = $fc->getConsultatie();
            $consultatie->setTarif($valoare);

            $consultatii[] = $consultatie;
        }

        $stornata = null;
        if ($factura->getTip() === self::STORNO) {
            $stornata = $this->em->getRepository(Facturi::class)->findOneBy(['stornare' => $factura->getId()]);
        }

        $data = [
            'nrFactura' => $factura->getSerie() . $factura->getNumar(),
            'dataFactura' => $factura->getData()->format('d-m-Y'),
            'furnizor' => $factura->getOwner(),
            'cumparator' => $factura->getPacient() ?? $factura->getClientPj(),
            'consultatii' => $consultatii,
            'total' => $total,
            'stornata' => $stornata ? $stornata?->getSerie() . $stornata?->getNumar() . '/'
                . $stornata?->getData()->format('d-m-Y') : '',
            'scadenta' => $factura->getScadenta(),
        ];

        return new PdfDocument(
            $data,
            UtilService::getDateFirma(),
            'Factura.pdf'
        );
    }
}