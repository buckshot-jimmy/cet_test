<?php

namespace App\Tests\Repository;

use App\Repository\MediciTrimitatoriRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MediciTrimitatoriRepositoryTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $registry = $container->get(ManagerRegistry::class);

        $this->repo = new MediciTrimitatoriRepository($registry);
    }

    /**
     * @covers \App\Repository\MediciTrimitatoriRepository::__construct
     */
    public function testCanBuildMediciRepository()
    {
        $this->assertInstanceOf(MediciTrimitatoriRepository::class, $this->repo);
    }
}
