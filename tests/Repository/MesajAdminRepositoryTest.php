<?php

namespace App\Tests\Repository;

use App\Entity\MesajAdmin;
use App\Repository\MesajAdminRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MesajAdminRepositoryTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $registry = $container->get(ManagerRegistry::class);
        $this->em = $registry->getManager();

        $this->repo = new MesajAdminRepository($registry, $this->em);

        $this->em->getConnection()->beginTransaction();

        $this->mesaj = new MesajAdmin();
        $this->mesaj->setMesaj('Mesaj info');
        $this->mesaj->setActiv(1);

        $this->em->persist($this->mesaj);
        $this->em->flush();
        $this->em->clear();
    }

    /**
     * @covers \App\Repository\MesajAdminRepository::__construct
     */
    public function testCanBuildMesajAdminRepository()
    {
        $this->assertInstanceOf(MesajAdminRepository::class, $this->repo);
    }

    /**
     * @covers \App\Repository\MesajAdminRepository::saveMesaj
     */
    public function testCanSaveMesajAdminWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(4001);
        $this->expectExceptionMessage('Failed operation');

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $mesajMock = $this->getMockBuilder(MesajAdminRepository::class)
            ->setConstructorArgs([$registry, $em])
            ->onlyMethods(['findOneBy'])
            ->getMock();

        $em->method('getRepository')->willReturnMap([
            [MesajAdmin::class, $mesajMock],
        ]);

        $mesajMock->expects($this->once())->method('findOneBy')->willReturn(null);

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush')
            ->willThrowException(new \Exception('Failed operation'));

        $mesajMock->saveMesaj(1, 1);
    }

    /**
     * @covers \App\Repository\MesajAdminRepository::saveMesaj
     */
    public function testItCanSaveMesajWithSuccess()
    {
        $id = $this->repo->saveMesaj('New info', 0);

        $new = $this->repo->findOneBy(['id' => $id]);

        $this->assertInstanceOf(MesajAdmin::class, $new);
        $this->assertEquals('New info', $new->getMesaj());
    }

    /**
     * @covers \App\Repository\MesajAdminRepository::getMesajAdmin
     */
    public function testCanGetMesajWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(4001);
        $this->expectExceptionMessage('Data collection error');

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $mesajMock = $this->getMockBuilder(MesajAdminRepository::class)
            ->setConstructorArgs([$registry, $em])
            ->onlyMethods(['findOneBy'])
            ->getMock();

        $em->method('getRepository')->willReturnMap([
            [MesajAdmin::class, $mesajMock],
        ]);

        $mesajMock->expects($this->once())->method('findOneBy')
            ->willThrowException(new \Exception('Data collection error'));

        $mesajMock->getMesajAdmin();
    }

    /**
     * @covers \App\Repository\MesajAdminRepository::getMesajAdmin
     */
    public function testCanGetMesajWithSuccess()
    {
        $mesaj = $this->repo->getMesajAdmin();

        $this->assertEquals('text admin', $mesaj);
    }

    protected function tearDown(): void
    {
        try {
            if (isset($this->em) && null !== $this->em) {
                $conn = $this->em->getConnection();

                if ($conn->isTransactionActive()) {
                    $conn->rollBack();
                }

                $this->em->clear();
                $this->em->close();
            }
        } finally {
            $this->em = null;
            $this->repo = null;

            self::ensureKernelShutdown();
            parent::tearDown();
        }
    }
}
