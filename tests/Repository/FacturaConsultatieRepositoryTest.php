<?php

namespace App\Tests\Repository;

use App\Repository\FacturaConsultatieRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class FacturaConsultatieRepositoryTest extends TestCase
{
    public function setUp(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);

        $this->repo = new FacturaConsultatieRepository($registry);
    }

    /**
     * @covers \App\Repository\FacturaConsultatieRepository::__construct
     */
    public function testCanBuildConsultatiiRepository()
    {
        $this->assertInstanceOf(FacturaConsultatieRepository::class, $this->repo);
    }
}
