<?php

namespace App\Tests\Repository;

use App\Entity\Owner;
use App\Entity\Preturi;
use App\Entity\Role;
use App\Entity\Servicii;
use App\Entity\User;
use App\Repository\OwnerRepository;
use App\Repository\PreturiRepository;
use App\Repository\ServiciiRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PreturiRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $registry = $container->get(ManagerRegistry::class);
        $this->em = $registry->getManager();

        $this->repo = new PreturiRepository($registry, $this->em);

        $this->em->getConnection()->beginTransaction();

        $this->role = (new Role())->setDenumire('ROLE_TestMedic');
        $this->em->persist($this->role);

        $this->medic = (new User())
            ->setUsername('test_medic')
            ->setPassword('test_password_hash')
            ->setNume('Nume_Test')
            ->setPrenume('Prenume_Test')
            ->setTelefon('0700000000')
            ->setRole($this->role);
        $this->medic->setSters(false);
        $this->medic->setParolaSchimbata(false);
        $this->em->persist($this->medic);

        $this->owner = (new Owner())->setDenumire('Owner')->setCui('12345678');
        $this->owner->setSters(false);
        $this->owner->setAdresa('Addr');
        $this->owner->setSerieFactura('F');
        $this->em->persist($this->owner);

        $this->serviciu = (new Servicii())->setDenumire('Consult_TestTest');
        $this->serviciu->setTip(0);
        $this->serviciu->setSters(false);
        $this->em->persist($this->serviciu);

        $this->pret = new Preturi();
        $this->pret->setMedic($this->medic);
        $this->pret->setOwner($this->owner);
        $this->pret->setServiciu($this->serviciu);
        $this->pret->setPret(100);
        $this->pret->setProcentajMedic(50);
        $this->pret->setSters(false);
        $this->pret->setCotaTva(0);
        $this->em->persist($this->pret);

        $this->em->flush();
        $this->em->clear();
    }

    /**
     * @covers \App\Repository\PreturiRepository::__construct
     */
    public function testCanBuildPreturiRepository()
    {
        $this->assertInstanceOf(PreturiRepository::class, $this->repo);
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
     * @covers \App\Repository\PreturiRepository::getAllPreturi
     */
    public function testItCanGetAllPreturiWithException()
    {
        $this->expectException(\Exception::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $repoMock = $this->getMockBuilder(PreturiRepository::class)
            ->setConstructorArgs([$registry, $em])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $queryMock = $this->createMock(Query::class);
        $queryMock->method('getResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willThrowException(new \Exception('Data collection error'));

        $qbMock = $this->createMock(QueryBuilder::class);

        $qbMock->method('select')->willReturnSelf();
        $qbMock->method('innerJoin')->willReturnSelf();
        $qbMock->method('leftJoin')->willReturnSelf();
        $qbMock->method('where')->willReturnSelf();
        $qbMock->method('setParameters')->willReturnSelf();
        $qbMock->method('getQuery')->willReturn($queryMock);
        $qbMock->method('setMaxResults')->willReturnSelf();
        $qbMock->method('setFirstResult')->willReturnSelf();

        $exprMock = $this->createMock(Expr::class);
        $exprMock->method('orX')->willReturn('OR_EXPRESSION');
        $qbMock->method('expr')->willReturn($exprMock);

        $repoMock->method('createQueryBuilder')->willReturn($qbMock);

        $repoMock->getAllPreturi([]);
    }

    /**
     * @covers \App\Repository\PreturiRepository::getAllPreturi
     * @covers \App\Repository\PreturiRepository::getTotalPreturi
     * @covers \App\Repository\PreturiRepository::applyFilters
     * @covers \App\Repository\PreturiRepository::buildSort
     * @dataProvider dataProviderSortCol
     */
    public function testItCanGetAllPreturiWithFilterWithSuccess($col)
    {
        $result = $this->repo->getAllPreturi(
            [
                'value' => 'Consult',
                'start' => 0,
                'length' => 10,
                'sort' => ['column' => $col, 'dir' => 'DESC'],
                'propertyFilters' => [
                    0 => ['preturi' => ['sters' => false]],
                    1 => ['medic' => ['sters' => false]],
                    2 => ['owner' => ['sters' => false]]
                ]
            ]
        );

        $this->assertArrayHasKey('servicii_preturi', $result);
        $this->assertArrayHasKey('total', $result);
    }

    private function dataProviderSortCol()
    {
        yield ['1'];
        yield ['2'];
        yield ['3'];
        yield [''];
    }

    /**
     * @covers \App\Repository\PreturiRepository::getPreturiMedic
     */
    public function testItCanGetPreturiMedicWithException()
    {
        $this->expectException(\Exception::class);

        $this->repo->getPreturiMedic(new Preturi());
    }

    /**
     * @covers \App\Repository\PreturiRepository::getPreturiMedic
     */
    public function testItCanGetPreturiMedicWithSuccess()
    {
        $result = $this->repo->getPreturiMedic($this->medic->getId());

        $this->assertSame($this->pret->getId(), $result[0]['id']);
    }

    /**
     * @covers \App\Repository\PreturiRepository::savePret
     */
    public function testItCanSavePretWithMissingIdException()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Missing ID');

        $this->repo->savePret(['pret_id' => -10]);
    }

    /**
     * @covers \App\Repository\PreturiRepository::savePret
     */
    public function testItCanSavePretWithPersistException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed operation');

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $medic = $this->createMock(UserRepository::class);
        $owner = $this->createMock(OwnerRepository::class);
        $serviciu = $this->createMock(ServiciiRepository::class);

        $pretMock = $this->getMockBuilder(PreturiRepository::class)
            ->setConstructorArgs([$registry, $em])
            ->onlyMethods(['find'])
            ->getMock();

        $em->method('getRepository')->willReturnMap([
            [Preturi::class, $pretMock],
            [Owner::class, $owner],
            [User::class, $medic],
            [Servicii::class, $serviciu],
        ]);

        $pretMock->method('find')->with($this->pret->getId())
            ->willReturn($this->pret);

        $medic->method('find')->willReturn(new User());
        $owner->method('find')->willReturn(new Owner());
        $serviciu->method('find')->willReturn(new Servicii());

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush')->willThrowException(new \Exception());

        $pretMock->savePret([
            'pret_medic' => $this->em->getRepository(User::class)->findAll()[0]->getId(),
            'pret_owner' => $this->em->getRepository(Owner::class)->findAll()[0]->getId(),
            'pret_serviciu' => $this->em->getRepository(Servicii::class)->findAll()[0]->getId(),
            'pret_pret' => -10,
            'pret_procentaj_medic' => 0
        ]);
    }

    /**
     * @covers \App\Repository\PreturiRepository::savePret
     */
    public function testItCanSavePretWithSuccess()
    {
        $result = $this->repo->savePret([
            'pret_id' => $this->pret->getId(),
            'pret_medic' => $this->em->getRepository(User::class)->findAll()[0]->getId(),
            'pret_owner' => $this->em->getRepository(Owner::class)->findAll()[0]->getId(),
            'pret_serviciu' => $this->serviciu,
            'pret_pret' => 10,
            'pret_procentaj_medic' => 10
        ]);

        $this->assertSame($result, $this->pret->getId());
    }

    /**
     * @covers \App\Repository\PreturiRepository::getPret
     */
    public function testItCanGetPretWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Data collection error');

        $this->repo->getPret(new Preturi());
    }

    /**
     * @covers \App\Repository\PreturiRepository::getPret
     */
    public function testItCanGetPretWithSuccess()
    {
        $result = $this->repo->getPret($this->pret->getId());

        $this->assertSame($this->pret->getPret(), $result['pret']);
    }

    /**
     * @covers \App\Repository\PreturiRepository::getMediciForOwner
     */
    public function testItCanGetMediciForOwnerWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Data collection error');

        $this->repo->getMediciForOwner(new Owner());
    }

    /**
     * @covers \App\Repository\PreturiRepository::getMediciForOwner
     */
    public function testItCanGetMediciForOwnerWithSuccess()
    {
        $result = $this->repo->getMediciForOwner($this->owner->getId());

        $this->assertSame($this->medic->getId(), $result[0]['id']);
    }

    /**
     * @covers \App\Repository\PreturiRepository::deletePret
     */
    public function testItCanDeletePretWithException()
    {
        $this->expectException(\Exception::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $pretMock = $this->getMockBuilder(PreturiRepository::class)
            ->setConstructorArgs([$registry, $em])
            ->onlyMethods(['find'])
            ->getMock();

        $em->method('getRepository')->willReturnMap([
            [Preturi::class, $pretMock]
        ]);

        $pretMock->method('find')->with($this->pret->getId())
            ->willReturn($this->pret);

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush')->willThrowException(new \Exception());

        $pretMock->deletePret($this->pret->getId());
    }

    /**
     * @covers \App\Repository\PreturiRepository::deletePret
     */
    public function testItCanDeletePretWithSuccess()
    {
        $role = (new Role())->setDenumire('ROLE_TestMedic');
        $this->em->persist($role);

        $medic = (new User())
            ->setUsername('test_medic2')
            ->setPassword('test_password_hash2')
            ->setNume('Nume_Test2')
            ->setPrenume('Prenume_Test2')
            ->setTelefon('0700000000')
            ->setRole($role);
        $medic->setSters(false);
        $medic->setParolaSchimbata(false);
        $this->em->persist($medic);

        $owner = (new Owner())->setDenumire('Owner3')->setCui('12345679');
        $owner->setAdresa('Addr2');
        $owner->setSerieFactura('F3');
        $owner->setSters(false);
        $this->em->persist($owner);

        $serviciu = (new Servicii())->setDenumire('Consult_TestTest2');
        $serviciu->setTip(0);
        $serviciu->setSters(false);
        $this->em->persist($serviciu);

        $pret = new Preturi();
        $pret->setMedic($medic);
        $pret->setOwner($owner);
        $pret->setServiciu($serviciu);
        $pret->setPret(100);
        $pret->setProcentajMedic(50);
        $pret->setSters(false);
        $pret->setCotaTva(0);
        $this->em->persist($pret);

        $this->em->flush();

        $this->repo->deletePret($pret->getId());

        $this->assertSame(true, $pret->getSters());
    }
}
