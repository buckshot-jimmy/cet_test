<?php

namespace App\Tests\Repository;

use App\Repository\MedicTrimitatorRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MedicTrimitatorRepositoryTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $registry = $container->get(ManagerRegistry::class);

        $this->repo = new MedicTrimitatorRepository($registry);
    }

    /**
     * @covers \App\Repository\MedicTrimitatorRepository::__construct
     */
    public function testCanBuildMediciRepository()
    {
        $this->assertInstanceOf(MedicTrimitatorRepository::class, $this->repo);
    }
}
