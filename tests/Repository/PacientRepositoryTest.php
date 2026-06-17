<?php

namespace App\Tests\Repository;

use App\Entity\Consultatie;
use App\Entity\Pacient;
use App\Entity\Pret;
use App\Entity\User;
use App\Repository\PacientRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PacientRepositoryTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $registry = $container->get(ManagerRegistry::class);
        $this->em = $registry->getManager();

        $this->repo = new PacientRepository($registry, $this->em);

        $this->em->getConnection()->beginTransaction();

        $this->pacient = new Pacient();
        $this->pacient->setNume('Pacient_Test');
        $this->pacient->setPrenume('Prenume_Test');
        $this->pacient->setCnp('1891022414121');
        $this->pacient->setTelefon('0711111111');
        $this->pacient->setAdresa('Addr');
        $this->pacient->setTara('Romania');
        $this->pacient->setCi('XB');
        $this->pacient->setCiEliberat('City');
        $this->pacient->setSters(false);
        $this->pacient->setDataInreg(new \DateTime());
        $this->em->persist($this->pacient);

        $this->consultatie = new Consultatie();
        $this->consultatie->setPret($this->em->getRepository(Pret::class)->findAll()[0]);
        $this->consultatie->setPacient($this->pacient);
        $this->consultatie->setDiagnostic('diag');
        $this->consultatie->setConsultatie('cons');
        $this->consultatie->setTratament('trat');
        $this->consultatie->setNrInreg('123');
        $this->consultatie->setDataConsultatie(new \DateTime());
        $this->consultatie->setTarif(100);
        $this->consultatie->setLoc('C');
        $this->consultatie->setInchisa(false);
        $this->consultatie->setStearsa(false);
        $this->consultatie->setIncasata(false);
        $this->em->persist($this->consultatie);

        $this->pacient->addConsultatii($this->consultatie);

        $this->testMedic = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('damian.popescu@mindreset.ro');

        $this->em->flush();
        $this->em->clear();

        $this->emMock = $this->createMock(EntityManagerInterface::class);
        $this->registryMock = $this->createMock(ManagerRegistry::class);
        $this->repoMock = $this->getMockBuilder(PacientRepository::class)
            ->setConstructorArgs([$this->registryMock, $this->emMock])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $this->queryMock = $this->createMock(Query::class);
        $this->queryMock->method('getResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willThrowException(new \Exception('Data collection error'));

        $this->qbMock = $this->createMock(QueryBuilder::class);

        $this->qbMock->method('select')->willReturnSelf();
        $this->qbMock->method('innerJoin')->willReturnSelf();
        $this->qbMock->method('leftJoin')->willReturnSelf();
        $this->qbMock->method('where')->willReturnSelf();
        $this->qbMock->method('andWhere')->willReturnSelf();
        $this->qbMock->method('setParameter')->willReturnSelf();
        $this->qbMock->method('groupBy')->willReturnSelf();
        $this->qbMock->method('getQuery')->willReturn($this->queryMock);
        $this->qbMock->method('setMaxResults')->willReturnSelf();
        $this->qbMock->method('setFirstResult')->willReturnSelf();
        $this->qbMock->method('distinct')->willReturnSelf();

        $exprMock = $this->createMock(Expr::class);
        $exprMock->method('orX')->willReturn('OR_EXPRESSION');
        $this->qbMock->method('expr')->willReturn($exprMock);

        $this->repoMock->method('createQueryBuilder')->willReturn($this->qbMock);
    }

    /**
     * @covers \App\Repository\PacientRepository::__construct
     */
    public function testCanBuildPacientiRepository()
    {
        $this->assertInstanceOf(PacientRepository::class, $this->repo);
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
     * @covers \App\Repository\PacientRepository::getPacientiInCabinet
     */
    public function testItCanGetPacientiInCabinetWithException()
    {
        $this->expectException(\Exception::class);

        $this->repoMock->getPacientiInCabinet([]);
    }

    /**
     * @covers \App\Repository\PacientRepository::getPacientiInCabinet
     * @covers \App\Repository\PacientRepository::getTotalPacientiInCabinet
     * @covers \App\Repository\PacientRepository::pacientAreConsultatiiDeschise
     * @covers \App\Repository\PacientRepository::pacientAreConsultatiiNeplatite
     * @covers \App\Repository\PacientRepository::applyFilterPacientiInCabinet
     * @covers \App\Repository\PacientRepository::buildSortPacientiInCabinet
     * @covers \App\Repository\PacientRepository::getTotalPacienti
     * @dataProvider dataProvider
     */
    public function testItCanGetPacientiInCabinetWithSuccess($value, $col)
    {
        $result = $this->repo->getPacientiInCabinet(
            [
                'length' => 10,
                'start' => 0,
                'sort' => ['column' => $col, 'order' => 'asc'],
                'value' => $value,
            ]);

        $this->assertArrayHasKey('pacienti', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @covers \App\Repository\PacientRepository::getAllPacienti
     */
    public function testCanGetAllPacientiWithException()
    {
        $this->expectException(\Exception::class);

        $this->repoMock->getAllPacienti([]);
    }

    /**
     * @covers \App\Repository\PacientRepository::getAllPacienti
     * @covers \App\Repository\PacientRepository::getTotalPacienti
     * @covers \App\Repository\PacientRepository::applyFilter
     * @covers \App\Repository\PacientRepository::buildSort
     * @dataProvider dataProvider
     */
    public function testCanGetAllPacientiWithSuccess($value, $col)
    {
        $result = $this->repo->getAllPacienti(
            [
                'length' => 10,
                'start' => 0,
                'sort' => ['column' => $col, 'order' => 'asc'],
                'value' => $value,
            ]);

        $this->assertArrayHasKey('pacienti', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @covers \App\Repository\PacientRepository::getAllPacientiCuConsultatii
     */
    public function testCanGetAllPacientiWithConsultatiiWithException()
    {
        $this->expectException(\Exception::class);

        $this->repoMock->getAllPacientiCuConsultatii([]);
    }

    /**
     * @covers \App\Repository\PacientRepository::getAllPacienti
     * @covers \App\Repository\PacientRepository::getAllPacientiCuConsultatii
     * @covers \App\Repository\PacientRepository::getTotalPacientiCuConsultatii
     * @covers \App\Repository\PacientRepository::applyFilter
     * @covers \App\Repository\PacientRepository::buildSort
     */
    public function testCanGetAllPacientiCuConsultatiiWithSuccess()
    {
        $result = $this->repo->getAllPacientiCuConsultatii(
            [
                'length' => 10,
                'start' => 0,
                'sort' => ['column' => '6', 'order' => 'asc'],
                'value' => '',
            ]);

        $this->assertArrayHasKey('pacienti', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @covers \App\Repository\PacientRepository::getPacientiConsultatiiNefacturate
     */
    public function testCanGetAllPacientiWithConsultatiiNefacturateWithException()
    {
        $this->expectException(\Exception::class);

        $this->repoMock->getPacientiConsultatiiNefacturate([]);
    }

    /**
     * @covers \App\Repository\PacientRepository::getPacientiConsultatiiNefacturate
     * @covers \App\Repository\PacientRepository::getTotalPacientiConsultatiiNefacturate
     * @covers \App\Repository\PacientRepository::applyFilterNefacturate
     */
    public function testCanGetAllPacientiWithConsultatiiNefacturateWithSuccess()
    {
        $result = $this->repo->getPacientiConsultatiiNefacturate(
            ['value' => 'a', 'sort' => ['column' => '6', 'order' => 'asc'],'length' => 10, 'start' => 0]);

        $this->assertArrayHasKey('pacienti', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @covers \App\Repository\PacientRepository::savePacient
     */
    public function testCanSaveNewPacient()
    {
        $adaugatDe = $this->em->getRepository(User::class)->findAll()[0];
        $pacient = $this->getPacientEntity('1990101223347');

        $result = $this->repo->savePacient($pacient, $adaugatDe);

        $this->assertNotNull($result);
    }

    /**
     * @covers \App\Repository\PacientRepository::savePacient
     */
    public function testSanSavePacientWithPersistException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed operation');

        $this->emMock->method('persist')
            ->willThrowException(new \Exception('Failed operation'));

        $this->repoMock->savePacient($this->getPacientEntity('1990101223347'), $this->testMedic);
    }

    /**
     * @covers \App\Repository\PacientRepository::savePacient
     */
    public function testSanSavePacientWithSuccess()
    {
        $adaugatDe = $this->em->getRepository(User::class)->findAll()[0];
        $pacient = $this->em->getRepository(Pacient::class)->find($this->pacient->getId());
        $pacient->setNume('Pacient_Editat');

        $result = $this->repo->savePacient($pacient, $adaugatDe);

        $this->assertSame($this->pacient->getId(), $result);
    }

    /**
     * @covers \App\Repository\PacientRepository::getPacient
     */
    public function testItCanGetPacientWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Data collection error');

        $this->repo->getPacient(new Pacient());
    }

    /**
     * @covers \App\Repository\PacientRepository::getPacient
     */
    public function testItCanGetPacientWithSuccess()
    {
        $result = $this->repo->getPacient($this->pacient->getId());

        $this->assertSame($this->pacient->getNume(), $result['nume']);
    }

    /**
     * @covers \App\Repository\PacientRepository::deletePacient
     */
    public function testItCanDeletePacientWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(5001);
        $this->expectExceptionMessage('Failed operation');

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $pacientMock = $this->getMockBuilder(PacientRepository::class)
            ->setConstructorArgs([$registry, $em])
            ->onlyMethods(['find'])
            ->getMock();

        $em->method('getRepository')->willReturnMap([
            [Pacient::class, $pacientMock],
        ]);

        $pacientMock->expects($this->once())->method('find')->with($this->pacient->getId())
            ->willReturn($this->pacient);

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush')
            ->willThrowException(new \Exception('Failed operation'));

        $repo = new PacientRepository($registry, $em);

        $repo->deletePacient($this->pacient->getId());
    }

    /**
     * @covers \App\Repository\PacientRepository::deletePacient
     */
    public function testItCanDeletePacientWithSuccess()
    {
        $pacient = new Pacient();
        $pacient->setNume('Pacient_Test');
        $pacient->setPrenume('Prenume_Test');
        $pacient->setCnp('1990813419809');
        $pacient->setTelefon('0711111111');
        $pacient->setAdresa('Addr');
        $pacient->setTara('Romania');
        $pacient->setCi('XB');
        $pacient->setCiEliberat('City');
        $pacient->setSters(false);
        $pacient->setDataInreg(new \DateTime());

        $this->em->persist($pacient);
        $this->em->flush();

        $this->repo->deletePacient($pacient->getId());

        $this->assertSame(true, $pacient->getSters());
    }

    /**
     * @covers \App\Repository\PacientRepository::getPacientiByCnp
     */
    public function testItCanGetPacientByCnpWithException()
    {
        $this->expectException(\Exception::class);

        $this->repoMock->getPacientiByCnp(10);
    }

    /**
     * @covers \App\Repository\PacientRepository::getPacientiByCnp
     */
    public function testItCanGetPacientByCnp()
    {
        $result = $this->repo->getPacientiByCnp($this->pacient->getCnp());

        $this->assertSame($this->pacient->getId(), $result[0]['id']);
    }

    public function dataProvider()
    {
        yield ['value' => '', 'col' => '2'];
        yield ['value' => '', 'col' => '3'];
        yield ['value' => '', 'col' => '4'];
        yield ['value' => '', 'col' => '5'];
        yield ['value' => '', 'col' => '6'];
        yield ['value' => '', 'col' => '7'];
        yield ['value' => '', 'col' => '8'];
        yield ['value' => '', 'col' => '9'];
        yield ['value' => '', 'col' => '10'];
        yield ['value' => '', 'col' => '12'];
        yield ['value' => 'Pacient_Test', 'col' => '6'];
    }

    private function getPacientEntity(string $cnp): Pacient
    {
        $pacient = new Pacient();
        $pacient->setNume('Pacient_Test');
        $pacient->setPrenume('Prenume_Test');
        $pacient->setCnp($cnp);
        $pacient->setTelefon('0711111111');
        $pacient->setAdresa('Addr');
        $pacient->setTara('Romania');
        $pacient->setCi('XB');
        $pacient->setCiEliberat('City');
        $pacient->setJudet('Alba');
        $pacient->setLocalitate('Localitate');
        $pacient->setStareCivila(0);

        return $pacient;
    }
}
