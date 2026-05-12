<?php

namespace App\Tests\Repository;

use App\Entity\Servicii;
use App\Repository\ServiciiRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ServiciiRepositoryTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $registry = $container->get(ManagerRegistry::class);
        $this->em = $registry->getManager();

        $this->repo = new ServiciiRepository($registry, $this->em);

        $this->em->getConnection()->beginTransaction();

        $this->serviciu = (new Servicii())->setDenumire('Consult_Test');
        $this->serviciu->setTip(0);
        $this->serviciu->setSters(false);
        $this->em->persist($this->serviciu);

        $this->em->flush();
        $this->em->clear();
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

    /**
     * @covers \App\Repository\ServiciiRepository::__construct
     */
    public function testCanBuildServiciiRepository()
    {
        $this->assertInstanceOf(ServiciiRepository::class, $this->repo);
    }

    /**
     * @covers \App\Repository\ServiciiRepository::getAllServicii
     */
    public function testItCanGetAllServiciiWithException()
    {
        $this->expectException(\Exception::class);

        $registry = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $repoMock = $this->getMockBuilder(ServiciiRepository::class)
            ->setConstructorArgs([$registry, $em])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $queryMock = $this->createMock(Query::class);
        $queryMock->method('getResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willThrowException(new \Exception('Data collection error'));

        $qbMock = $this->createMock(QueryBuilder::class);
        $qbMock->method('select')->willReturnSelf();
        $qbMock->method('where')->willReturnSelf();
        $qbMock->method('setParameter')->willReturnSelf();
        $qbMock->method('getQuery')->willReturn($queryMock);
        $repoMock->method('createQueryBuilder')->willReturn($qbMock);

        $repoMock->getAllServicii();
    }

    /**
     * @covers \App\Repository\ServiciiRepository::getAllServicii
     */
    public function testItCanGetAllServiciiWithSuccess()
    {
        $result = $this->repo->getAllServicii();

        $this->assertSame($this->serviciu->getDenumire(), end($result)['denumire']);
    }

    /**
     * @covers \App\Repository\ServiciiRepository::saveServiciu
     */
    public function testItCanSaveServiciuWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(5001);
        $this->expectExceptionMessage('Failed operation');

        $formData = [
            'add_denumire_serviciu' => 'test',
            'add_tip_serviciu' => 0
        ];

        $registry = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $repo = new ServiciiRepository($registry, $em);

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush')
            ->willThrowException(new \Exception('Failed operation'));

        $repo->saveServiciu($formData);
    }

    /**
     * @covers \App\Repository\ServiciiRepository::saveServiciu
     */
    public function testItCanSaveServiciuWithSuccess()
    {
        $formData = [
            'add_denumire_serviciu' => 'test',
            'add_tip_serviciu' => 0
        ];

        $result = $this->repo->saveServiciu($formData);

        $this->assertNotNull($result);
    }
}
