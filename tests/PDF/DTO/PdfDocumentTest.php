<?php

namespace App\Tests\PDF\DTO;

use App\PDF\DTO\PdfDocument;
use PHPUnit\Framework\TestCase;

class PdfDocumentTest extends TestCase
{
    /**
     * @covers \App\PDF\DTO\PdfDocument::__construct
     */
    public function testItCanBuildDto()
    {
        $dto = new PdfDocument(['data'], ['dateFirma'], 'testPath');

        $this->assertInstanceOf(PdfDocument::class, $dto);
    }
}
