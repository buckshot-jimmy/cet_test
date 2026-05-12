<?php

namespace App\PDF\Service;

use App\PDF\Contract\PdfInterface;
use App\PDF\Factory\PdfBuilderResolver;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Twig\Environment;

class PdfService implements PdfInterface
{
    public function __construct(
        private PdfBuilderResolver $resolver,
        private Environment $twig,
        private Mpdf $pdf
    ) {}

    public function printToPdf(int $id, string $template, array $params = [])
    {
        $builder = $this->resolver->resolve($template);
        $document = $builder->build($id);

        if (isset($params['orientation'])) {
            $this->pdf->AddPage($params['orientation']);
        }

        if (isset($params['footer'])) {
            $this->pdf->SetFooter($params['footer']);
        }

        $html = $this->twig->render(
            '@templates/formulare/' . $template,
            [
                'data' => $document->data,
                'dateFirma' => $document->dateFirma,
                'output' => $document->outputName,
            ]
        );

        $this->pdf->WriteHTML($html);

        return $this->pdf->Output(
            $document->outputName,
            $params['destination'] ?? Destination::DOWNLOAD
        );
    }
}