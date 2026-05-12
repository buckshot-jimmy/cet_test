<?php

namespace App\Tests\PDF\Service;

use App\PDF\Builder\FacturaPdfBuilder;
use App\PDF\DTO\PdfDocument;
use App\PDF\Factory\PdfBuilderResolver;
use App\PDF\Service\PdfService;
use Mpdf\Mpdf;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class PdfServiceTest extends TestCase
{
    public function setUp(): void
    {
        $this->resolver = $this->createMock(PdfBuilderResolver::class);
        $this->twig = $this->createMock(Environment::class);
        $this->mpdf = $this->createMock(Mpdf::class);

        $this->pdfService = new PdfService($this->resolver, $this->twig, $this->mpdf);
    }

    /**
     * @covers \App\PDF\Service\PdfService::__construct
     */
    public function testItCanBuildService()
    {
        $this->assertInstanceOf(PdfService::class, $this->pdfService);
    }

    /**
     * @covers \App\PDF\Service\PdfService::printToPdf
     * @dataProvider typeProvider
     */
    public function testItCanPrintToPdf($orientation, $footer)
    {
        $builder = $this->createMock(FacturaPdfBuilder::class);
        $document = $this->createMock(PDFDocument::class);
        $document->data = ['data' => 'factura'];
        $document->outputName = 'file.pdf';
        $document->dateFirma = ['nume' => 'firma'];

        $this->resolver->method('resolve')->with('factura.html.twig')->willReturn($builder);
        $builder->method('build')->with(1)->willReturn($document);

        $params = [
            'orientation' => $orientation,
            'footer' => $footer
        ];

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                '@templates/formulare/factura.html.twig',
                [
                    'data' => ['data' => 'factura'],
                    'dateFirma' => ['nume' => 'firma'],
                    'output' => 'file.pdf',
                ]
            )
            ->willReturn('<html lang="en">content</html>');

        $this->mpdf->expects($this->once())
            ->method('WriteHTML')
            ->with('<html lang="en">content</html>');

        $this->mpdf->expects($this->once())
            ->method('Output')
            ->with('file.pdf', 'D')
            ->willReturn('PDF_CONTENT');

        $result = $this->pdfService->printToPdf(1, 'factura.html.twig', $params);

        $this->assertEquals('PDF_CONTENT', $result);
    }

    private function typeProvider()
    {
        yield ['orientation' => 'P', 'footer' => 'footer text'];
        yield ['orientation' => null, 'footer' => null];
    }
}
