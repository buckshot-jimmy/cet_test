<?php

namespace App\Tests\Repository;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use App\Repository\ResetPasswordRequestRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ResetPasswordRequestRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->em = $container->get('doctrine.orm.entity_manager');

        $registry = $container->get(ManagerRegistry::class);

        $this->repo = new ResetPasswordRequestRepository($registry);

        $this->testUser = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('ciprianmarta.cm@gmail.com');
    }

    /**
     * @covers \App\Repository\ResetPasswordRequestRepository::__construct
     */
    public function testItCanBuildRepository()
    {
        $this->assertInstanceOf(ResetPasswordRequestRepository::class, $this->repo);
    }

    public function testItCanCreateResetPasswordRequest()
    {
        $reset = $this->repo->createResetPasswordRequest(
            $this->testUser,
            new \DateTimeImmutable(),
            'selector',
            'hashedToken'
        );

        $this->assertInstanceOf(ResetPasswordRequest::class, $reset);

        $this->repo->removeResetPasswordRequest($reset);
    }
}
