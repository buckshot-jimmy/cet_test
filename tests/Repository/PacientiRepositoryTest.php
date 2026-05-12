<?php

namespace App\Tests\Repository;

use App\DTO\PacientiDTO;
use App\Entity\Consultatii;
use App\Entity\Pacienti;
use App\Entity\Preturi;
use App\Entity\User;
use App\Repository\PacientiRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PacientiRepositoryTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $registry = $container->get(ManagerRegistry::class);
        $this->em = $registry->getManager();

        $this->repo = new PacientiRepository($registry, $this->em);

        $this->em->getConnection()->beginTransaction();

        $this->pacient = new Pacienti();
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

        $this->consultatie = new Consultatii();
        $this->consultatie->setPret($this->em->getRepository(Preturi::class)->findAll()[0]);
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

        $this->dto = new PacientiDTO(1, 'n', 'p', '1790630060774', '0745545689' ,
            '', 'ciprianmarta.cm@gmail.com', 'a', 'Alba', 'Baciu', 'Romania',
            'XB', 'City', 'l', 'o', '2026-01-01', false,
            'o', 1, $this->testMedic->getId(), [], []);

        $this->em->flush();
        $this->em->clear();

        $this->emMock = $this->createMock(EntityManagerInterface::class);
        $this->registryMock = $this->createMock(ManagerRegistry::class);
        $this->repoMock = $this->getMockBuilder(PacientiRepository::class)
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
     * @covers \App\Repository\PacientiRepository::__construct
     */
    public function testCanBuildPacientiRepository()
    {
        $this->assertInstanceOf(PacientiRepository::class, $this->repo);
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
     * @covers \App\Repository\PacientiRepository::getPacientiInCabinet
     */
    public function testItCanGetPacientiInCabinetWithException()
    {
        $this->expectException(\Exception::class);

        $this->repoMock->getPacientiInCabinet([]);
    }

    /**
     * @covers \App\Repository\PacientiRepository::getPacientiInCabinet
     * @covers \App\Repository\PacientiRepository::getTotalPacientiInCabinet
     * @covers \App\Repository\PacientiRepository::pacientAreConsultatiiDeschise
     * @covers \App\Repository\PacientiRepository::pacientAreConsultatiiNeplatite
     * @covers \App\Repository\PacientiRepository::applyFilterPacientiInCabinet
     * @covers \App\Repository\PacientiRepository::buildSortPacientiInCabinet
     * @covers \App\Repository\PacientiRepository::getTotalPacienti
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
     * @covers \App\Repository\PacientiRepository::getAllPacienti
     */
    public function testCanGetAllPacientiWithException()
    {
        $this->expectException(\Exception::class);

        $this->repoMock->getAllPacienti([]);
    }

    /**
     * @covers \App\Repository\PacientiRepository::getAllPacienti
     * @covers \App\Repository\PacientiRepository::getTotalPacienti
     * @covers \App\Repository\PacientiRepository::applyFilter
     * @covers \App\Repository\PacientiRepository::buildSort
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
     * @covers \App\Repository\PacientiRepository::getAllPacientiCuConsultatii
     */
    public function testCanGetAllPacientiWithConsultatiiWithException()
    {
        $this->expectException(\Exception::class);

        $this->repoMock->getAllPacientiCuConsultatii([]);
    }

    /**
     * @covers \App\Repository\PacientiRepository::getAllPacienti
     * @covers \App\Repository\PacientiRepository::getAllPacientiCuConsultatii
     * @covers \App\Repository\PacientiRepository::getTotalPacientiCuConsultatii
     * @covers \App\Repository\PacientiRepository::applyFilter
     * @covers \App\Repository\PacientiRepository::buildSort
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
     * @covers \App\Repository\PacientiRepository::getPacientiConsultatiiNefacturate
     */
    public function testCanGetAllPacientiWithConsultatiiNefacturateWithException()
    {
        $this->expectException(\Exception::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $repoMock = $this->getMockBuilder(PacientiRepository::class)
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
        $qbMock->method('andWhere')->willReturnSelf();
        $qbMock->method('groupBy')->willReturnSelf();
        $qbMock->method('having')->willReturnSelf();
        $qbMock->method('setParameters')->willReturnSelf();
        $qbMock->method('getQuery')->willReturn($queryMock);
        $qbMock->method('setMaxResults')->willReturnSelf();
        $qbMock->method('setFirstResult')->willReturnSelf();

        $exprMock = $this->createMock(Expr::class);
        $exprMock->method('orX')->willReturn('OR_EXPRESSION');
        $qbMock->method('expr')->willReturn($exprMock);

        $conn = $this->createMock(Connection::class);
        $result = $this->createMock(Result::class);
        $conn->method('executeQuery')->willReturn($result);
        $result->method('fetchOne')->willReturn(1);
        $em->expects($this->once())->method('getConnection')->willReturn($conn);

        $repoMock->method('createQueryBuilder')->willReturn($qbMock);

        $repoMock->getPacientiConsultatiiNefacturate([]);
    }

    /**
     * @covers \App\Repository\PacientiRepository::getPacientiConsultatiiNefacturate
     * @covers \App\Repository\PacientiRepository::getTotalPacientiConsultatiiNefacturate
     */
    public function testCanGetAllPacientiWithConsultatiiNefacturateWithSuccess()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $repoMock = $this->getMockBuilder(PacientiRepository::class)
            ->setConstructorArgs([$registry, $em])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $queryMock = $this->createMock(Query::class);
        $queryMock->method('getResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willReturn([1]);

        $qbMock = $this->createMock(QueryBuilder::class);

        $qbMock->method('select')->willReturnSelf();
        $qbMock->method('innerJoin')->willReturnSelf();
        $qbMock->method('leftJoin')->willReturnSelf();
        $qbMock->method('where')->willReturnSelf();
        $qbMock->method('andWhere')->willReturnSelf();
        $qbMock->method('groupBy')->willReturnSelf();
        $qbMock->method('having')->willReturnSelf();
        $qbMock->method('setParameters')->willReturnSelf();
        $qbMock->method('getQuery')->willReturn($queryMock);
        $qbMock->method('setMaxResults')->willReturnSelf();
        $qbMock->method('setFirstResult')->willReturnSelf();

        $exprMock = $this->createMock(Expr::class);
        $exprMock->method('orX')->willReturn('OR_EXPRESSION');
        $qbMock->method('expr')->willReturn($exprMock);

        $conn = $this->createMock(Connection::class);
        $result = $this->createMock(Result::class);
        $conn->method('executeQuery')->willReturn($result);
        $result->method('fetchOne')->willReturn(1);
        $em->expects($this->once())->method('getConnection')->willReturn($conn);

        $repoMock->method('createQueryBuilder')->willReturn($qbMock);

        $repoMock->getPacientiConsultatiiNefacturate(['value' => 'a', 'sort' => ['column' => '6', 'order' => 'asc'],
            'length' => 10, 'start' => 0]);
    }

    /**
     * @covers \App\Repository\PacientiRepository::savePacient
     */
    public function testSanSavePacientWithMissingIdException()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Missing ID');

        $this->dto->id = -10;
        $this->repo->savePacient($this->dto, $this->testMedic);
    }

    /**
     * @covers \App\Repository\PacientiRepository::savePacient
     */
    public function testSanSavePacientWithPersistException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed operation');
        $this->dto->id = $this->pacient->getId();

        $this->repo->savePacient($this->dto, $this->testMedic);
    }

    /**
     * @covers \App\Repository\PacientiRepository::savePacient
     */
    public function testSanSavePacientWithSuccess()
    {
        $adaugatDe = $this->em->getRepository(User::class)->findAll()[0];
        $this->dto->id = $this->pacient->getId();

        $result = $this->repo->savePacient($this->dto, $adaugatDe);

        $this->assertSame($this->dto->id, $result);
    }

    /**
     * @covers \App\Repository\PacientiRepository::getPacient
     */
    public function testItCanGetPacientWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Data collection error');

        $this->repo->getPacient(new Pacienti());
    }

    /**
     * @covers \App\Repository\PacientiRepository::getPacient
     */
    public function testItCanGetPacientWithSuccess()
    {
        $result = $this->repo->getPacient($this->pacient->getId());

        $this->assertSame($this->pacient->getNume(), $result['nume']);
    }

    /**
     * @covers \App\Repository\PacientiRepository::deletePacient
     */
    public function testItCanDeletePacientWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(5001);
        $this->expectExceptionMessage('Failed operation');

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $pacientMock = $this->getMockBuilder(PacientiRepository::class)
            ->setConstructorArgs([$registry, $em])
            ->onlyMethods(['find'])
            ->getMock();

        $em->method('getRepository')->willReturnMap([
            [Pacienti::class, $pacientMock],
        ]);

        $pacientMock->expects($this->once())->method('find')->with($this->pacient->getId())
            ->willReturn($this->pacient);

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush')
            ->willThrowException(new \Exception('Failed operation'));

        $repo = new PacientiRepository($registry, $em);

        $repo->deletePacient($this->pacient->getId());
    }

    /**
     * @covers \App\Repository\PacientiRepository::deletePacient
     */
    public function testItCanDeletePacientWithSuccess()
    {
        $pacient = new Pacienti();
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
     * @covers \App\Repository\PacientiRepository::getPacientiByCnp
     */
    public function testItCanGetPacientByCnpWithException()
    {
        $this->expectException(\Exception::class);

        $this->repoMock->getPacientiByCnp(10);
    }

    /**
     * @covers \App\Repository\PacientiRepository::getPacientiByCnp
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
}
