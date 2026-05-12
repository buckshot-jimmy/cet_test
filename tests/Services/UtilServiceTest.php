<?php

namespace App\Tests\Services;

use App\Services\UtilService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UtilServiceTest extends KernelTestCase
{
    /**
     * @covers \App\Services\UtilService::calculeazaDatePacient
     * @dataProvider dataProvider
     */
    public function testCanDetermineDatePacient($cnp, $sex, $dataNasterii, $an)
    {
        $service = new UtilService();

        $result = $service::calculeazaDatePacient($cnp);

        $expected = date('Y') - $an;
        if (date('m') < (new \DateTime($dataNasterii))->format('m') &&
            date('d') < (new \DateTime($dataNasterii))->format('d')) {
            $expected--;
        }

        $this->assertIsArray($result);
        $this->assertEquals($expected, $result['varsta']);
        $this->assertEquals($sex, $result['sex']);
        $this->assertEquals($dataNasterii, $result['dataNasterii']);
    }

    /**
     * @covers \App\Services\UtilService::getDateFirma
     */
    public function testItCanGetDateFirma()
    {
        $dateFirma = UtilService::getDateFirma();

        $this->assertIsArray($dateFirma);
        $this->assertSame('MIND RESET', $dateFirma['denumire']);
    }

    protected function dataProvider()
    {
        yield ['1790630060774', 'M', '30-06-1979', 1979];
        yield ['8860131418681', 'F', '31-01-1986', 1986];
        yield ['8020206418621', 'F', '06-02-2002', 2002];
    }
}
