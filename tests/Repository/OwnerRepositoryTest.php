<?php

namespace App\Tests\Repository;

use App\Entity\Owner;
use App\Repository\OwnerRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OwnerRepositoryTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $registry = $container->get(ManagerRegistry::class);
        $this->em = $registry->getManager();

        $this->repo = new OwnerRepository($registry, $this->em);

        $this->em->getConnection()->beginTransaction();

        $this->owner = new Owner();
        $this->owner->setSters(false);
        $this->owner->setCui(00000001);
        $this->owner->setDenumire('MyOwn');
        $this->owner->setAdresa('Addr');
        $this->owner->setSerieFactura('Fact');

        $this->em->persist($this->owner);
        $this->em->flush();
        $this->em->clear();
    }

    /**
     * @covers \App\Repository\OwnerRepository::__construct
     */
    public function testCanBuildOwnerRepository()
    {
        $this->assertInstanceOf(OwnerRepository::class, $this->repo);
    }

    /**
     * @covers \App\Repository\OwnerRepository::saveOwner
     */
    public function testCanSaveOwnerWithMissingIdException()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Missing ID');

        $this->repo->saveOwner(['owner_id' => -10]);
    }

    /**
     * @covers \App\Repository\OwnerRepository::saveOwner
     */
    public function testEditOwner()
    {
        $result = $this->repo->saveOwner(['owner_id' => $this->owner->getId(), 'denumire' => 'new', 'cui' => 1111111,
            'adresa' => 'test', 'serie_factura' => 'test', 'banca' => 'b', 'cont' => 12332323, 'sters' => false
        ]);

        $this->assertSame($result, $this->owner->getId());
    }

    /**
     * @covers \App\Repository\OwnerRepository::saveOwner
     */
    public function testCanSaveOwnerWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(5001);
        $this->expectExceptionMessage('Failed operation');

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $mesajMock = $this->getMockBuilder(OwnerRepository::class)
            ->setConstructorArgs([$registry, $em])
            ->onlyMethods(['findOneBy'])
            ->getMock();

        $em->method('getRepository')->willReturnMap([
            [Owner::class, $mesajMock],
        ]);

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush')
            ->willThrowException(new \Exception('Failed operation'));

        $mesajMock->saveOwner(['denumire' => 'test', 'cui' => 000000000, 'adresa' => 'test', 'serie_factura' => 'test',
            'sters' => false, 'banca' => 'banca', 'cont' => 12332323
        ]);
    }

    /**
     * @covers \App\Repository\OwnerRepository::saveOwner
     */
    public function testItCanSaveOwnerWithSuccess()
    {
        $this->repo->saveOwner(['denumire' => 'test', 'cui' => 000000000, 'adresa' => 'test', 'serie_factura' => 'test',
            'sters' => false, 'banca' => 'banca', 'cont' => 12332323
        ]);

        $last = $this->repo->findOneBy([], ['id' => 'DESC']);

        $this->assertEquals('test', $last->getDenumire());
    }

    /**
     * @covers \App\Repository\OwnerRepository::getAllOwners
     */
    public function testCanGetAllOwnersWithException()
    {
        $this->expectException(\Exception::class);

        $ownerMock = $this->getMockBuilder(OwnerRepository::class)
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

        $ownerMock->method('createQueryBuilder')->willReturn($qbMock);

        $ownerMock->getAllOwners();
    }

    /**
     * @covers \App\Repository\OwnerRepository::getAllOwners
     * @covers \App\Repository\OwnerRepository::getTotalOwnersByFilter
     * @covers \App\Repository\OwnerRepository::applyFilter
     * @covers \App\Repository\OwnerRepository::buildSort
     * @dataProvider dataProvider
     */
    public function testCanGetAllOwnersWithFilterSuccess($value, $col)
    {
        $filter = [
            'length' => 10,
            'start' => 0,
            'sort' => ['column' => $col, 'order' => 'asc'],
            'value' => $value,
        ];

        $result = $this->repo->getAllOwners($filter)['owners'];

        $this->assertSame('MyOwn', $result[array_key_last($result)]['denumire']);
    }

    /**
     * @covers \App\Repository\OwnerRepository::getOwner
     */
    public function testItCanGetOwnerWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Data collection error');

        $this->repo->getOwner(new Owner());
    }

    /**
     * @covers \App\Repository\OwnerRepository::getOwner
     */
    public function testItCanGetOwnerWithSuccess()
    {
        $result = $this->repo->getOwner($this->owner->getId());

        $this->assertSame($this->owner->getDenumire(), $result['denumire']);
    }

    /**
     * @covers \App\Repository\OwnerRepository::deleteOwner
     */
    public function testItCanDeleteOwnerWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(5001);
        $this->expectExceptionMessage('Failed operation');

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $ownerMock = $this->getMockBuilder(OwnerRepository::class)
            ->setConstructorArgs([$registry, $em])
            ->onlyMethods(['find'])
            ->getMock();

        $em->method('getRepository')->willReturnMap([
            [Owner::class, $ownerMock],
        ]);

        $ownerMock->expects($this->once())->method('find')->with($this->owner->getId())
            ->willReturn($this->owner);

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush')
            ->willThrowException(new \Exception('Failed operation'));

        $repo = new OwnerRepository($registry, $em);

        $repo->deleteOwner($this->owner->getId());
    }

    /**
     * @covers \App\Repository\OwnerRepository::deleteOwner
     */
    public function testItCanDeleteOwnerWithSuccess()
    {
        $owner = new Owner();
        $owner->setDenumire('owntest');
        $owner->setAdresa('adresatest');
        $owner->setSerieFactura('FF');
        $owner->setCui('12345');
        $owner->setSters(false);

        $this->em->persist($owner);
        $this->em->flush();

        $this->repo->deleteOwner($owner->getId());

        $this->assertSame(true, $owner->getSters());
    }

    private function dataProvider()
    {
        yield ['value' => 'MyOwn', 'col' => '1'];
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
