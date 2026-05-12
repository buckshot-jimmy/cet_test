<?php

namespace App\Tests\Services;

use App\Services\NomenclatoareService;
use PHPUnit\Framework\TestCase;

class NomenclatoareServiceTest extends TestCase
{
    protected function setUp(): void
    {
        $this->service = new NomenclatoareService();
    }

    /**
     * @covers \App\Services\NomenclatoareService::getLunileAnului
     */
    public function testItCanGetLunileAnului()
    {
        $luni = $this->service->getLunileAnului();

        $this->assertIsArray($luni);
        $this->assertSame('Ianuarie', $luni[1]);
    }

    /**
     * @covers \App\Services\NomenclatoareService::getTari
     */
    public function testItCanGetTari()
    {
        $tari = $this->service->getTari();

        $this->assertIsArray($tari);
        $this->assertSame('Romania', $tari[0]);
    }

    /**
     * @covers \App\Services\NomenclatoareService::getJudete
     */
    public function testItCanGetJudete()
    {
        $judete = $this->service->getJudete();

        $this->assertIsArray($judete);
        $this->assertSame('Alba', $judete[0]);
    }

    /**
     * @covers \App\Services\NomenclatoareService::getStariCivile
     */
    public function testItCanGetStariCivile()
    {
        $stari = $this->service->getStariCivile();

        $this->assertIsArray($stari);
        $this->assertSame('Necasatorit', $stari[0]);
    }
}
