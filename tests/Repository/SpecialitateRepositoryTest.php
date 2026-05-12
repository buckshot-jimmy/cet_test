<?php

namespace App\Tests\Repository;

use App\Repository\SpecialitateRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SpecialitateRepositoryTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $registry = $container->get(ManagerRegistry::class);

        $this->repo = new SpecialitateRepository($registry);
    }

    /**
     * @covers \App\Repository\SpecialitateRepository::__construct
     */
    public function testCanBuildSpecialitateRepository()
    {
        $this->assertInstanceOf(SpecialitateRepository::class, $this->repo);
    }
}
