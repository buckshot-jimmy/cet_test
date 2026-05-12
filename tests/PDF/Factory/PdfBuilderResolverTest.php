<?php

namespace App\Tests\PDF\Factory;

use App\PDF\Builder\BuletinInvestigatiePdfBuilder;
use App\PDF\Builder\FacturaPdfBuilder;
use App\PDF\Factory\PdfBuilderResolver;
use PHPUnit\Framework\TestCase;

class PdfBuilderResolverTest extends TestCase
{
    public function setUp(): void
    {
        $buletinBuilder = $this->createMock(BuletinInvestigatiePdfBuilder::class);
        $buletinBuilder->method('getSupportedTemplates')->willReturn('buletin_investigatie.html.twig');

        $facturaBuilder = $this->createMock(FacturaPdfBuilder::class);
        $facturaBuilder->method('getSupportedTemplates')->willReturn('factura.html.twig');

        $this->resolver = new PdfBuilderResolver([$buletinBuilder, $facturaBuilder]);
    }

    /**
     * @covers \App\PDF\Factory\PdfBuilderResolver::__construct
     */
    public function testItCanBuildResolver()
    {
        $this->assertInstanceOf(PdfBuilderResolver::class, $this->resolver);
    }

    /**
     * @covers \App\PDF\Factory\PdfBuilderResolver::resolve
     */
    public function testItCanResolveTemplateWithException()
    {
        $this->expectException(\Exception::class);

        $this->resolver->resolve('noTemplate');
    }

    /**
     * @covers \App\PDF\Factory\PdfBuilderResolver::resolve
     */
    public function testItCanResolveTemplate()
    {
        $map = $this->resolver->resolve('buletin_investigatie.html.twig');

        $this->assertInstanceOf(BuletinInvestigatiePdfBuilder::class, $map);
    }
}
