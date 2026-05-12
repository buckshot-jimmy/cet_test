<?php

namespace App\Tests\Repository;

use App\Entity\PersoaneJuridice;
use App\Repository\PersoaneJuridiceRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PersoaneJuridiceRepositoryTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $registry = $container->get(ManagerRegistry::class);
        $this->em = $registry->getManager();

        $this->repo = new PersoaneJuridiceRepository($registry, $this->em);

        $this->em->getConnection()->beginTransaction();

        $this->pj = new PersoaneJuridice();
        $this->pj->setSters(false);
        $this->pj->setCui(00000001);
        $this->pj->setDenumire('ClientPj');
        $this->pj->setAdresa('Addr');

        $this->em->persist($this->pj);
        $this->em->flush();
        $this->em->clear();
    }

    /**
     * @covers \App\Repository\PersoaneJuridiceRepository::__construct
     */
    public function testCanBuildPersoaneJuridiceRepository()
    {
        $this->assertInstanceOf(PersoaneJuridiceRepository::class, $this->repo);
    }

    /**
     * @covers \App\Repository\PersoaneJuridiceRepository::saveClientPj
     */
    public function testCanSavePjWithMissingIdException()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Missing ID');

        $this->repo->saveClientPj(['pj_id' => -10]);
    }

    /**
     * @covers \App\Repository\PersoaneJuridiceRepository::saveClientPj
     */
    public function testEditPj()
    {
        $result = $this->repo->saveClientPj(['pj_id' => $this->pj->getId(), 'denumire' => 'new', 'cui' => 1111111,
            'adresa' => 'test', 'sters' => false
        ]);

        $this->assertSame($result, $this->pj->getId());
    }

    /**
     * @covers \App\Repository\PersoaneJuridiceRepository::saveClientPj
     */
    public function testCanSavePjWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(5001);
        $this->expectExceptionMessage('Failed operation');

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $mesajMock = $this->getMockBuilder(PersoaneJuridiceRepository::class)
            ->setConstructorArgs([$registry, $em])
            ->onlyMethods(['findOneBy'])
            ->getMock();

        $em->method('getRepository')->willReturnMap([
            [PersoaneJuridice::class, $mesajMock],
        ]);

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush')
            ->willThrowException(new \Exception('Failed operation'));

        $mesajMock->saveClientPj(['denumire' => 'test', 'cui' => 000000000, 'adresa' => 'test','sters' => false]);
    }

    /**
     * @covers \App\Repository\PersoaneJuridiceRepository::saveClientPj
     */
    public function testItCanSavePjWithSuccess()
    {
        $this->repo->saveClientPj(['denumire' => 'test', 'cui' => 000000000, 'adresa' => 'test', 'sters' => false]);

        $last = $this->repo->findOneBy([], ['id' => 'DESC']);

        $this->assertEquals('test', $last->getDenumire());
    }

    /**
     * @covers \App\Repository\PersoaneJuridiceRepository::getAllClientiPj
     */
    public function testCanGetAllPjWithException()
    {
        $this->expectException(\Exception::class);

        $pjMock = $this->getMockBuilder(PersoaneJuridiceRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $queryMock = $this->createMock(Query::class);
        $queryMock->method('getResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willThrowException(new \Exception('Data collection error'));

        $qbMock = $this->createMock(QueryBuilder::class);

        $exprMock = $this->createMock(Expr::class);
        $exprMock->method('orX')->willReturn('OR_EXPRESSION');
        $qbMock->method('expr')->willReturn($exprMock);
        $qbMock->method('select')->willReturnSelf();
        $qbMock->method('getQuery')->willReturn($queryMock);
        $qbMock->method('where')->willReturnSelf();
        $qbMock->method('setParameter')->willReturnSelf();
        $qbMock->method('setMaxResults')->willReturnSelf();
        $qbMock->method('setFirstResult')->willReturnSelf();

        $pjMock->method('createQueryBuilder')->willReturn($qbMock);

        $pjMock->getAllClientiPj();
    }

    /**
     * @covers \App\Repository\PersoaneJuridiceRepository::getAllClientiPj
     * @covers \App\Repository\PersoaneJuridiceRepository::getTotalClientiPjByFilter
     * @covers \App\Repository\PersoaneJuridiceRepository::applyFilter
     * @covers \App\Repository\PersoaneJuridiceRepository::buildSort
     * @dataProvider dataProvider
     */
    public function testCanGetAllPjWithFilterSuccess($value, $col)
    {
        $filter = [
            'length' => 10,
            'start' => 0,
            'sort' => ['column' => $col, 'order' => 'asc'],
            'value' => $value,
        ];

        $result = $this->repo->getAllClientiPj($filter)['clienti_pj'];

        $this->assertSame('ClientPj', $result[array_key_last($result)]['denumire']);
    }

    /**
     * @covers \App\Repository\PersoaneJuridiceRepository::getClientPj
     */
    public function testItCanGetPjWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Data collection error');

        $this->repo->getClientPj(new PersoaneJuridice());
    }

    /**
     * @covers \App\Repository\PersoaneJuridiceRepository::getClientPj
     */
    public function testItCanGetPjWithSuccess()
    {
        $result = $this->repo->getClientPj($this->pj->getId());

        $this->assertSame($this->pj->getDenumire(), $result['denumire']);
    }

    /**
     * @covers \App\Repository\PersoaneJuridiceRepository::deleteClientPj
     */
    public function testItCanDeletePjWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(5001);
        $this->expectExceptionMessage('Failed operation');

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $pjMock = $this->getMockBuilder(PersoaneJuridiceRepository::class)
            ->setConstructorArgs([$registry, $em])
            ->onlyMethods(['find'])
            ->getMock();

        $em->method('getRepository')->willReturnMap([
            [PersoaneJuridice::class, $pjMock],
        ]);

        $pjMock->expects($this->once())->method('find')->with($this->pj->getId())
            ->willReturn($this->pj);

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush')
            ->willThrowException(new \Exception('Failed operation'));

        $repo = new PersoaneJuridiceRepository($registry, $em);

        $repo->deleteClientPj($this->pj->getId());
    }

    /**
     * @covers \App\Repository\PersoaneJuridiceRepository::deleteClientPj
     */
    public function testItCanDeletePjrWithSuccess()
    {
        $pj = new PersoaneJuridice();
        $pj->setDenumire('pjtest');
        $pj->setAdresa('adresatest');
        $pj->setCui('12121');
        $pj->setSters(false);

        $this->em->persist($pj);
        $this->em->flush();

        $this->repo->deleteClientPj($pj->getId());

        $this->assertSame(true, $pj->isSters());
    }

    /**
     * @covers \App\Repository\PersoaneJuridiceRepository::getClientiByCui
     */
    public function testItCanGetClientByCuiWithException()
    {
        $this->expectException(\Exception::class);

        $pjMock = $this->getMockBuilder(PersoaneJuridiceRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $queryMock = $this->createMock(Query::class);
        $queryMock->method('getResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willThrowException(new \Exception('Data collection error'));

        $qbMock = $this->createMock(QueryBuilder::class);

        $exprMock = $this->createMock(Expr::class);
        $exprMock->method('like')->willReturn('OR_EXPRESSION');
        $qbMock->method('expr')->willReturn($exprMock);
        $qbMock->method('select')->willReturnSelf();
        $qbMock->method('getQuery')->willReturn($queryMock);
        $qbMock->method('where')->willReturnSelf();
        $qbMock->method('setParameter')->willReturnSelf();
        $qbMock->method('setMaxResults')->willReturnSelf();
        $qbMock->method('setFirstResult')->willReturnSelf();

        $pjMock->method('createQueryBuilder')->willReturn($qbMock);

        $pjMock->getClientiByCui(1234567890);
    }

    /**
     * @covers \App\Repository\PersoaneJuridiceRepository::getClientiByCui
     */
    public function testItCanGetClientByCuiWithSuccess()
    {
        $result = $this->repo->getClientiByCui(1234567890);

        $this->assertIsArray($result);
    }
    public function testItCanGetClientiByCui()
    {

    }

    private function dataProvider()
    {
        yield ['value' => 'ClientPj', 'col' => '1'];
        yield ['value' => '', 'col' => ''];
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
