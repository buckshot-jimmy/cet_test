<?php

namespace App\Tests\Repository;

use App\Repository\TitulaturaRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TitulaturaRepositoryTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $registry = $container->get(ManagerRegistry::class);

        $this->repo = new TitulaturaRepository($registry);
    }

    /**
     * @covers \App\Repository\TitulaturaRepository::__construct
     */
    public function testCanBuildTitulaturiRepository()
    {
        $this->assertInstanceOf(TitulaturaRepository::class, $this->repo);
    }
}
