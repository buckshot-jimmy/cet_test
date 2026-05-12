<?php

namespace App\Tests\Repository;

use App\DTO\ConsultatiiDTO;
use App\Entity\Consultatii;
use App\Entity\Owner;
use App\Entity\Pacienti;
use App\Entity\Preturi;
use App\Entity\Programari;
use App\Entity\RapoarteColaboratori;
use App\Entity\Role;
use App\Entity\Servicii;
use App\Entity\User;
use App\Repository\ConsultatiiRepository;
use App\Repository\PacientiRepository;
use App\Repository\PreturiRepository;
use App\Repository\ProgramariRepository;
use App\Services\NomenclatoareService;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ConsultatiiRepositoryTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $registry = $container->get(ManagerRegistry::class);
        $this->em = $registry->getManager();
        $this->service = $this->createMock(NomenclatoareService::class);

        $this->repo = new ConsultatiiRepository($registry, $this->em, $this->service);

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
        $this->owner->setAdresa('Addr');
        $this->owner->setSerieFactura('F');
        $this->owner->setSters(false);
        $this->em->persist($this->owner);

        $this->serviciu = (new Servicii())->setDenumire('Consult_Test');
        $this->serviciu->setTip(0);
        $this->serviciu->setSters(false);
        $this->em->persist($this->serviciu);

        $this->serviciu2 = (new Servicii())->setDenumire('Consult_Test2');
        $this->serviciu2->setTip(0);
        $this->serviciu2->setSters(false);
        $this->em->persist($this->serviciu2);

        $this->pret = new Preturi();
        $this->pret->setMedic($this->medic);
        $this->pret->setOwner($this->owner);
        $this->pret->setServiciu($this->serviciu);
        $this->pret->setPret(100);
        $this->pret->setProcentajMedic(50);
        $this->pret->setSters(false);
        $this->pret->setCotaTva(0);
        $this->em->persist($this->pret);

        $this->pret2 = new Preturi();
        $this->pret2->setMedic($this->medic);
        $this->pret2->setOwner($this->owner);
        $this->pret2->setServiciu($this->serviciu2);
        $this->pret2->setPret(150);
        $this->pret2->setProcentajMedic(50);
        $this->pret2->setSters(false);
        $this->pret2->setCotaTva(0);
        $this->em->persist($this->pret2);

        $this->pacient = new Pacienti();
        $this->pacient->setNume('Pacient_Test');
        $this->pacient->setPrenume('Prenume_Test');
        $this->pacient->setCnp('1790630060774');
        $this->pacient->setTelefon('0711111111');
        $this->pacient->setAdresa('Addr');
        $this->pacient->setTara('Romania');
        $this->pacient->setCi('XB');
        $this->pacient->setCiEliberat('City');
        $this->pacient->setSters(false);
        $this->pacient->setDataInreg(new \DateTime());
        $this->em->persist($this->pacient);

        $this->programare = new Programari();
        $this->programare->setPacient($this->pacient);
        $this->programare->setPret($this->pret);
        $data = \DateTime::createFromFormat('d-m-Y', '01-01-2026');
        $dataFormatata = $data->format('Y-m-d');
        $this->programare->setData(new \DateTime($dataFormatata));
        $this->programare->setOra(\DateTime::createFromFormat('H:i', '09:00'));
        $this->programare->setAnulata(false);
        $this->programare->setAdaugataDe($this->medic);
        $this->em->persist($this->programare);

        $this->consultatie = new Consultatii();
        $this->consultatie->setPret($this->pret);
        $this->consultatie->setPacient($this->pacient);
        $this->consultatie->setDiagnostic('diag');
        $this->consultatie->setConsultatie('cons');
        $this->consultatie->setTratament('trat');
        $this->consultatie->setNrInreg('1');
        $this->consultatie->setDataConsultatie(new \DateTime());
        $this->consultatie->setTarif(100);
        $this->consultatie->setLoc('C');
        $this->consultatie->setInchisa(false);
        $this->consultatie->setStearsa(false);
        $this->consultatie->setIncasata(false);
        $this->consultatie->setEvalPsiho('eval');
        $this->em->persist($this->consultatie);

        $this->consultatie2 = new Consultatii();
        $this->consultatie2->setPret($this->pret2);
        $this->consultatie2->setPacient($this->pacient);
        $this->consultatie2->setDiagnostic('diag');
        $this->consultatie2->setConsultatie('cons');
        $this->consultatie2->setTratament('trat');
        $this->consultatie2->setNrInreg('1');
        $this->consultatie2->setDataConsultatie(new \DateTime());
        $this->consultatie2->setTarif(100);
        $this->consultatie2->setLoc('C');
        $this->consultatie2->setInchisa(false);
        $this->consultatie2->setStearsa(false);
        $this->consultatie2->setIncasata(false);
        $this->consultatie2->setEvalPsiho('eval');
        $this->em->persist($this->consultatie2);

        $this->em->flush();
        $this->em->clear();

        $this->dto = new ConsultatiiDTO(1, '1', '1', 'C', '10' , '1', 'd',
            'c', 't', 'ahc', 'app', '01-01-2026', 1, '23',
            false, false, 'MT', 'inv', 'tr',
            'inv', 'trat', 'obs', 'ev', 'con', 'rez',
            'ob', 'rec', 'cc', 'cc', 'cc',
            'cc', 'pc', 'pc', 'pc', 'pc',
            'rc', 'rc', 'sc', 'sc', 1
        );

        $queryMock = $this->createMock(Query::class);
        $queryMock->method('getResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willThrowException(new \Exception('Data collection error'));

        $qbMock = $this->createMock(QueryBuilder::class);

        $qbMock->method('select')->willReturnSelf();
        $qbMock->method('leftJoin')->willReturnSelf();
        $qbMock->method('innerJoin')->willReturnSelf();
        $qbMock->method('where')->willReturnSelf();
        $qbMock->method('andWhere')->willReturnSelf();
        $qbMock->method('setParameter')->willReturnSelf();
        $qbMock->method('setParameters')->willReturnSelf();
        $qbMock->method('groupBy')->willReturnSelf();
        $qbMock->method('having')->willReturnSelf();
        $qbMock->method('getQuery')->willReturn($queryMock);

        $this->repoMock = $this->getMockBuilder(ConsultatiiRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $exprMock = $this->createMock(Expr::class);
        $exprMock->method('orX')->willReturn('OR_EXPRESSION');
        $qbMock->method('expr')->willReturn($exprMock);

        $this->repoMock->method('createQueryBuilder')->willReturn($qbMock);
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
            $this->service = null;

            self::ensureKernelShutdown();
            parent::tearDown();
        }
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::__construct
     */
    public function testCanBuildConsultatiiRepository()
    {
        $this->assertInstanceOf(ConsultatiiRepository::class, $this->repo);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::getAllConsultatiiByFilter
     * @covers \App\Repository\ConsultatiiRepository::applyFilters
     * @covers \App\Repository\ConsultatiiRepository::buildSort
     */
    public function testCanGetAllConsultatiiWithoutFilter()
    {
        $filter = ['value' => '', 'propertyFilters' => [], 'length' => 10, 'start' => 0,
            'sort' => ['column' => ConsultatiiRepository::COL_NR_INREG, 'dir' => 'DESC']];

        $result = $this->repo->getAllConsultatiiByFilter($filter);

        $this->assertArrayHasKey('consultatii', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertIsArray($result['consultatii']);
        $this->assertIsNumeric($result['total']);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::getAllConsultatiiByFilter
     * @covers \App\Repository\ConsultatiiRepository::getTotalConsultatiiByFilter
     * @covers \App\Repository\ConsultatiiRepository::applyFilters
     */
    public function testCanGetAllConsultatiiWithFilter()
    {
        $filter = ['value' => 'Prenume_Test',
            'propertyFilters' => [
                1 => ['medic' => ['sters' => false]],
                2 => ['owner' => ['sters' => false]]
            ]];

        $result = $this->repo->getAllConsultatiiByFilter($filter);

        $this->assertArrayHasKey('consultatii', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertIsArray($result['consultatii']);
        $this->assertIsNumeric($result['total']);
        $this->assertSame('Prenume_Test', $result['consultatii'][0]['prenumePacient']);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::getAllConsultatiiByFilter
     */
    public function testCanGetAllConsultatiiWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(4001);
        $this->expectExceptionMessage('Failed operation');

        $filter = [
            'value' => 'Prenume_Test',
            'propertyFilters' => [],
            'sort' => [
                'column' => ConsultatiiRepository::COL_NR_INREG,
                'dir' => 'INVALID_DIR',
            ],
        ];

        $this->repo->getAllConsultatiiByFilter($filter);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::saveConsultatie
     */
    public function testCanSaveConsultatieWithMissingIdException()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Missing ID');

        $this->repo->saveConsultatie($this->dto);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::saveConsultatie
     */
    public function testCanSaveConsultatieWithPersistException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed operation');

        $this->dto->id = $this->consultatie->getId();
        $this->dto->pret = -10;
        $this->repo->saveConsultatie($this->dto);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::saveConsultatie
     */
    public function testCanSaveConsultatie()
    {
        $this->dto->id = $this->consultatie->getId();
        $this->dto->pret = $this->pret->getId();
        $this->dto->pacient = $this->pacient->getId();
        $result = $this->repo->saveConsultatie($this->dto);

        $this->assertSame($this->consultatie->getId(), $result);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::saveInvestigatie
     */
    public function testCanSaveInvestigatieWithMissingIdException()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Missing ID');

        $this->repo->saveInvestigatie($this->dto);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::saveInvestigatie
     */
    public function testCanSaveInvestigatieWithPersistException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed operation');

        $this->dto->id = $this->consultatie->getId();
        $this->dto->pret = -10;
        $this->repo->saveInvestigatie($this->dto);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::saveInvestigatie
     */
    public function testCanSaveInvestigatie()
    {
        $this->dto->id = $this->consultatie->getId();
        $this->dto->pret = $this->pret->getId();
        $this->dto->pacient = $this->pacient->getId();
        $result = $this->repo->saveInvestigatie($this->dto);

        $this->assertSame($this->consultatie->getId(), $result);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::saveEvaluarePsihologica
     */
    public function testCanSaveEvaluarePsihologicaWithMissingIdException()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Missing ID');

        $this->repo->saveEvaluarePsihologica($this->dto);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::saveEvaluarePsihologica
     */
    public function testCanSaveEvaluarePsihologicaWithPersistException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed operation');

        $this->dto->id = $this->consultatie->getId();
        $this->dto->pret = -10;
        $this->repo->saveEvaluarePsihologica($this->dto);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::saveEvaluarePsihologica
     */
    public function testCanSaveEvaluarePsihologica()
    {
        $this->dto->id = $this->consultatie->getId();
        $this->dto->pret = $this->pret->getId();
        $this->dto->pacient = $this->pacient->getId();

        $result = $this->repo->saveEvaluarePsihologica($this->dto);

        $this->assertSame($this->consultatie->getId(), $result);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::getConsultatieInvestigatieEvaluare
     */
    public function testCanGetConsultatieInvestigatieEvaluareWithException()
    {
        $this->expectException(\Exception::class);

        $this->repoMock->getConsultatieInvestigatieEvaluare(1);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::getConsultatieInvestigatieEvaluare
     */
    public function testCanGetConsultatieInvestigatieEvaluare()
    {
        $result = $this->repo->getConsultatieInvestigatieEvaluare($this->consultatie->getId());

        $this->assertSame($result['id'], $this->consultatie->getId());
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::getIstoricPacient
     */
    public function testCanGetIstoricPacientWithExceptionAndTipToate()
    {
        $this->expectException(\Exception::class);

        $this->repoMock->getIstoricPacient($this->pacient->getId(), ConsultatiiRepository::TIP_TOATE);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::getIstoricPacient
     */
    public function testCanGetIstoricPacient()
    {
        $result = $this->repo->getIstoricPacient($this->pacient->getId(),
            ConsultatiiRepository::TIP_CONSULTATIE);

        $this->assertSame($result[0]['id'], $this->consultatie->getId());
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::getIstoricConsultatiiPentruFisa
     */
    public function testCanGetIstoricConsultatiiPentruFisaWithExceptionAndTipToate()
    {
        $this->expectException(\Exception::class);

        $this->repoMock->getIstoricConsultatiiPentruFisa(
            $this->pacient->getId(), $this->medic, ConsultatiiRepository::TIP_TOATE);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::getIstoricConsultatiiPentruFisa
     */
    public function testCanGetIstoricConsultatiiPentruFisa()
    {
        $result = $this->repo->getIstoricConsultatiiPentruFisa($this->pacient->getId(), $this->medic,
            ConsultatiiRepository::TIP_CONSULTATIE);

        $this->assertSame($result[0]['id'], $this->consultatie->getId());
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::deschideStergeConsultatii
     */
    public function testDeschideConsultatiiWithPersistException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(4001);
        $this->expectExceptionMessage('Failed operation');

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);
        $service = $this->createMock(NomenclatoareService::class);

        $consMock = $this->getMockBuilder(ConsultatiiRepository::class)
            ->setConstructorArgs([$registry, $em, $service])
            ->onlyMethods(['getServiciiPacient', 'findBy', 'find'])
            ->getMock();

        $pretMock = $this->createMock(PreturiRepository::class);
        $pacientMock = $this->createMock(PacientiRepository::class);
        $programareMock = $this->createMock(ProgramariRepository::class);

        $em->method('getRepository')->willReturnMap([
            [Preturi::class, $pretMock],
            [Consultatii::class, $consMock],
            [Pacienti::class, $pacientMock],
            [Programari::class, $programareMock]
        ]);

        $pretMock->method('find')->with($this->serviciu->getId())->willReturn($this->pret);
        $pacientMock->method('find')->with($this->pacient->getId())->willReturn($this->pacient);
        $programareMock->method('find')->with($this->programare->getId())->willReturn($this->programare);

        $consMock->expects($this->once())->method('findBy')->with(['pacient' => $this->pacient->getId()])
            ->willReturn([$this->consultatie]);

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush')->willThrowException(new \Exception());

        $consMock->deschideStergeConsultatii($this->pacient->getId(), $this->programare->getId(),
            [$this->serviciu->getId()], null);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::deschideStergeConsultatii
     */
    public function testDeschideConsultatiiWithSuccess()
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $pret = $this->em->getRepository(Preturi::class)->findAll()[0];
        $pret2 = $this->em->getRepository(Preturi::class)->findAll()[1];
        $pret3 = $this->em->getRepository(Preturi::class)->findAll()[2];
        $pacient = $this->em->getRepository(Pacienti::class)->find($this->pacient->getId());

        $date =  new \DateTime();

        $consultatie = new Consultatii();
        $consultatie->setPret($pret);
        $consultatie->setPacient($pacient);
        $consultatie->setDataConsultatie($date);
        $consultatie->setTarif('200');
        $consultatie->setInchisa(false);
        $consultatie->setStearsa(false);
        $consultatie->setIncasata(false);
        $consultatie->setPlatitaColaborator(false);
        $consultatie->setAhc('');
        $consultatie->setApp('');
        $em->persist($consultatie);
        $em->flush();

        $consultatie2 = new Consultatii();
        $consultatie2->setPret($pret2);
        $consultatie2->setPacient($pacient);
        $consultatie2->setDataConsultatie($date);
        $consultatie2->setTarif('300');
        $consultatie2->setInchisa(false);
        $consultatie2->setStearsa(false);
        $consultatie2->setIncasata(false);
        $consultatie2->setPlatitaColaborator(false);
        $em->persist($consultatie2);
        $em->flush();

        $result = $this->repo->deschideStergeConsultatii(
            $this->pacient->getId(), $this->programare->getId(),
            [$this->pret->getId(), $pret->getId(), $pret2->getId(), $pret3->getId()], null
        );

        $this->assertArrayHasKey('salvate', $result);
        $this->assertCount(1, $result['salvate']);
        $this->assertArrayHasKey('sterse', $result);
        $this->assertCount(1, $result['sterse']);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::getServiciiPacient
     */
    public function testCanGetServiciiPacientWithException()
    {
        $this->expectException(\Exception::class);

        $this->repoMock->getServiciiPacient([]);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::getServiciiPacient
     */
    public function testCanGetServiciiPacientWithSuccess()
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $pret = $this->em->getRepository(Preturi::class)->findAll()[0];
        $pacient = $this->em->getRepository(Pacienti::class)->find($this->pacient->getId());

        $consultatie = new Consultatii();
        $consultatie->setPret($pret);
        $consultatie->setPacient($pacient);
        $consultatie->setDataConsultatie(new \DateTime());
        $consultatie->setTarif('200');
        $consultatie->setInchisa(false);
        $consultatie->setStearsa(false);
        $consultatie->setIncasata(false);
        $consultatie->setPlatitaColaborator(false);
        $consultatie->setAhc('ahc test');
        $consultatie->setApp('');

        $em->persist($consultatie);
        $em->flush();
        $em->clear();

        $filter = [
            'inchisa' => false,
            'incasata' => false,
            'dataPrezentare' => (new \DateTime())->format('d-m-Y'),
            'pacientId' => $pacient->getId()
        ];

        $result = $this->repo->getServiciiPacient($filter);

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('numeMedic', $result[0]);
        $this->assertSame($consultatie->getId(), end($result)['consultatieId']);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::incaseazaConsultatii
     */
    public function testIncaseazaConsultatiiWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(4001);
        $this->expectExceptionMessage('Failed operation');

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);
        $service = $this->createMock(NomenclatoareService::class);

        $consMock = $this->getMockBuilder(ConsultatiiRepository::class)
            ->setConstructorArgs([$registry, $em, $service])
            ->onlyMethods(['find'])
            ->getMock();

        $em->method('getRepository')->willReturnMap([
            [Consultatii::class, $consMock],
        ]);

        $consMock->expects($this->once())->method('find')->with($this->consultatie->getId())
            ->willReturn($this->consultatie);

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush')
            ->willThrowException(new \Exception('Failed operation'));

        $repo = new ConsultatiiRepository($registry, $em, $service);

        $repo->incaseazaConsultatii([$this->consultatie->getId()]);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::incaseazaConsultatii
     */
    public function testIncaseazaConsultatiiWithSuccess()
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $pret = $this->em->getRepository(Preturi::class)->findAll()[0];
        $pacient = $this->em->getRepository(Pacienti::class)->find($this->pacient->getId());

        $consultatie = new Consultatii();
        $consultatie->setPret($pret);
        $consultatie->setPacient($pacient);
        $consultatie->setDataConsultatie(new \DateTime());
        $consultatie->setTarif('200');
        $consultatie->setInchisa(false);
        $consultatie->setStearsa(false);
        $consultatie->setIncasata(false);
        $consultatie->setPlatitaColaborator(false);
        $consultatie->setAhc('ahc test');
        $consultatie->setApp('');

        $em->persist($consultatie);
        $em->flush();
        $em->clear();

        $this->repo->incaseazaConsultatii([$consultatie->getId()]);

        $this->assertTrue($this->repo->findOneBy(['id' => $consultatie->getId()])->isIncasata());
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::calculeazaPlataColaborator
     */
    public function testCanGetCalculePlataColaboratorWithException()
    {
        $this->expectException(\Exception::class);

        $this->repo->calculeazaPlataColaborator(['medic' => new User()]);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::calculeazaPlataColaborator
     */
    public function testCanGetCalculePlataColaboratorWithSuccess()
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $pret = $this->em->getRepository(Preturi::class)->findAll()[0];
        $pacient = $this->em->getRepository(Pacienti::class)->find($this->pacient->getId());

        $date = new \DateTime();

        $consultatie = new Consultatii();
        $consultatie->setPret($pret);
        $consultatie->setPacient($pacient);
        $consultatie->setDataConsultatie($date);
        $consultatie->setTarif('200');
        $consultatie->setInchisa(true);
        $consultatie->setStearsa(false);
        $consultatie->setIncasata(true);
        $consultatie->setPlatitaColaborator(false);
        $consultatie->setAhc('ahc test');
        $consultatie->setApp('');

        $em->persist($consultatie);
        $em->flush();
        $em->clear();

        $result = $this->repo->calculeazaPlataColaborator([
            'medic' => $pret->getMedic()->getId(),
            'owner' => $pret->getOwner()->getId(),
            'luna' => $date->format('m'),
            'an' => $date->format('Y'),
        ]);

        $this->assertNotNull($result);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::getConsultatiiRaportColaborator
     */
    public function testItCanGetConsultatiiRaportColaboratorWithException()
    {
        $this->expectException(\Exception::class);

        $raport = $this->createMock(RapoarteColaboratori::class);
        $medic = $this->createMock(User::class);
        $owner = $this->createMock(Owner::class);
        $medic->method('getId')->willReturn(null);
        $owner->method('getId')->willReturn(null);
        $raport->method('getMedic')->willReturn($medic);
        $raport->method('getOwner')->willReturn($owner);
        $raport->method('getLuna')->willReturn(null);

        $service = $this->createMock(NomenclatoareService::class);
        $service->method('getLunileAnului')->willReturn([]);

        $queryMock = $this->createMock(Query::class);
        $queryMock->method('getResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willThrowException(new \Exception('Data collection error'));

        $qbMock = $this->createMock(QueryBuilder::class);

        $qbMock->method('select')->willReturnSelf();
        $qbMock->method('leftJoin')->willReturnSelf();
        $qbMock->method('where')->willReturnSelf();
        $qbMock->method('andWhere')->willReturnSelf();
        $qbMock->method('orderBy')->willReturnSelf();
        $qbMock->method('setParameters')->willReturnSelf();
        $qbMock->method('getQuery')->willReturn($queryMock);

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $repoMock = $this->getMockBuilder(ConsultatiiRepository::class)
            ->setConstructorArgs([$registry, $em, $service])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repoMock->method('createQueryBuilder')->willReturn($qbMock);

        $repoMock->getConsultatiiRaportColaborator($raport);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::getConsultatiiRaportColaborator
     */
    public function testItCanGetConsultatiiRaportColaborator()
    {
        $service = $this->createMock(NomenclatoareService::class);
        $service->method('getLunileAnului')->willReturn([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]);

        $em = self::getContainer()->get(EntityManagerInterface::class);

        $pret = $this->em->getRepository(Preturi::class)->findAll()[0];
        $pacient = $this->em->getRepository(Pacienti::class)->find($this->pacient->getId());

        $date = new \DateTime();

        $consultatie = new Consultatii();
        $consultatie->setPret($pret);
        $consultatie->setPacient($pacient);
        $consultatie->setDataConsultatie($date);
        $consultatie->setTarif('200');
        $consultatie->setInchisa(true);
        $consultatie->setStearsa(false);
        $consultatie->setIncasata(true);
        $consultatie->setPlatitaColaborator(false);
        $consultatie->setAhc('ahc test');
        $consultatie->setApp('');
        $em->persist($consultatie);

        $raport = new RapoarteColaboratori();
        $raport->setDataGenerarii($date);
        $raport->setSuma(5);
        $raport->setMedic($pret->getMedic());
        $raport->setOwner($pret->getOwner());
        $raport->setLuna($date->format('m'));
        $raport->setAn($date->format('Y'));
        $em->persist($raport);

        $em->flush();
        $em->clear();

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $queryMock = $this->createMock(Query::class);
        $queryMock->method('getResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willReturn([$consultatie]);

        $qbMock = $this->createMock(QueryBuilder::class);

        $qbMock->method('select')->willReturnSelf();
        $qbMock->method('leftJoin')->willReturnSelf();
        $qbMock->method('where')->willReturnSelf();
        $qbMock->method('andWhere')->willReturnSelf();
        $qbMock->method('orderBy')->willReturnSelf();
        $qbMock->method('setParameters')->willReturnSelf();
        $qbMock->method('getQuery')->willReturn($queryMock);

        $repoMock = $this->getMockBuilder(ConsultatiiRepository::class)
            ->setConstructorArgs([$registry, $em, $service])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repoMock->method('createQueryBuilder')->willReturn($qbMock);

        $result = $repoMock->getConsultatiiRaportColaborator($raport, true);

        $this->assertSame($result[0]->getId(), $consultatie->getId());
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::inchideDeschide
     */
    public function testInchideDeschideWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(4001);
        $this->expectExceptionMessage('Failed operation');

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);
        $service = $this->createMock(NomenclatoareService::class);

        $consMock = $this->getMockBuilder(ConsultatiiRepository::class)
            ->setConstructorArgs([$registry, $em, $service])
            ->onlyMethods(['find'])
            ->getMock();

        $em->method('getRepository')->willReturnMap([
            [Consultatii::class, $consMock],
        ]);

        $consMock->expects($this->once())->method('find')->with($this->consultatie->getId())
            ->willReturn($this->consultatie);

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush')
            ->willThrowException(new \Exception('Failed operation'));

        $repo = new ConsultatiiRepository($registry, $em, $service);

        $repo->inchideDeschide($this->consultatie->getId());
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::inchideDeschide
     */
    public function testInchideDeschideWithSuccess()
    {
        $result = $this->repo->inchideDeschide($this->consultatie->getId());

        $this->assertSame($result, $this->consultatie->getId());
        $this->assertSame($this->repo->findOneBy(['id' => $this->consultatie->getId()])->getInchisa(), true);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::valoareServicii
     */
    public function testItCanGetValoareServiciiWithException()
    {
        $this->expectException(\Exception::class);

        $this->repo->valoareServicii(['medic' => new User()]);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::valoareServicii
     */
    public function testItCanGetValoareServiciiWithSuccess()
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $pret = $this->em->getRepository(Preturi::class)->findAll()[0];
        $pacient = $this->em->getRepository(Pacienti::class)->find($this->pacient->getId());

        $date = new \DateTime();

        $consultatie = new Consultatii();
        $consultatie->setPret($pret);
        $consultatie->setPacient($pacient);
        $consultatie->setDataConsultatie($date);
        $consultatie->setTarif('200');
        $consultatie->setInchisa(true);
        $consultatie->setStearsa(false);
        $consultatie->setIncasata(true);
        $consultatie->setPlatitaColaborator(false);
        $consultatie->setAhc('ahc test');
        $consultatie->setApp('');

        $em->persist($consultatie);
        $em->flush();
        $em->clear();

        $result = $this->repo->valoareServicii([
            'medic' => $pret->getMedic()->getId(),
            'luna' => $date->format('m'),
            'an' => $date->format('Y'),
        ]);

        $this->assertArrayHasKey('valoare', $result[0]);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::numarConsultatiiPeLuni
     */
    public function testItCanGetNumarConsultatiiPeLuniWithException()
    {
        $this->expectException(\Exception::class);

        $this->repo->numarConsultatiiPeLuni(['medic' => new User()]);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::numarConsultatiiPeLuni
     */
    public function testItCanGetNumarConsultatiiPeLuniWithSuccess()
    {
        $date = new \DateTime();

        $result = $this->repo->numarConsultatiiPeLuni([
            'medic' => $this->pret->getMedic()->getId(),
            'luna' => $date->format('m'),
            'an' => $date->format('Y'),
        ]);

        $this->assertArrayHasKey('totalConsultatiiLuna', $result);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::getNrPacientiConsultatiDeMedic
     */
    public function testCanGetNrPacientiConsultatiDeMedicWithException()
    {
        $this->expectException(\Exception::class);

        $this->repo->getNrPacientiConsultatiDeMedic(new User());
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::getNrPacientiConsultatiDeMedic
     */
    public function testCanGetNrPacientiConsultatiDeMedicWithSuccess()
    {
        $result = $this->repo->getNrPacientiConsultatiDeMedic($this->medic->getId());

        $this->assertNotNull($result);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::getNrServiciiPrestateMedic
     */
    public function testCanGetNrServiciiPrestateMedicWithException()
    {
        $this->expectException(\Exception::class);

        $this->repo->getNrServiciiPrestateMedic(new User());
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::getNrServiciiPrestateMedic
     */
    public function testCanGetNrServiciiPrestateMedicWithSuccess()
    {
        $result = $this->repo->getNrServiciiPrestateMedic($this->medic->getId());

        $this->assertNotNull($result);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::ownerAreConsultatiiDeschise
     */
    public function testOwnerAreConsultatiiDeschiseWithException()
    {
        $this->expectException(\Exception::class);

        $this->repo->ownerAreConsultatiiDeschise(new User());
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::ownerAreConsultatiiDeschise
     */
    public function testOwnerAreConsultatiiDeschiseWithSuccess()
    {
        $result = $this->repo->ownerAreConsultatiiDeschise($this->owner->getId());

        $this->assertNotNull($result);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::inchideToateConsInvPacient
     */
    public function testInchideToateConsultatiilePacientWithException()
    {
        $this->expectException(\Exception::class);

        $this->repo->inchideToateConsInvPacient(new Pacienti());
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::inchideToateConsInvPacient
     */
    public function testInchideToateConsultatiilePacientWithSuccess()
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $pret = $this->em->getRepository(Preturi::class)->findAll()[0];
        $pacient = $this->em->getRepository(Pacienti::class)->find($this->pacient->getId());

        $date = new \DateTime();

        $consultatie = new Consultatii();
        $consultatie->setPret($pret);
        $consultatie->setPacient($pacient);
        $consultatie->setDataConsultatie($date);
        $consultatie->setTarif('200');
        $consultatie->setInchisa(false);
        $consultatie->setStearsa(false);
        $consultatie->setIncasata(true);
        $consultatie->setPlatitaColaborator(false);
        $consultatie->setAhc('ahc test');
        $consultatie->setApp('');

        $em->persist($consultatie);
        $em->flush();
        $em->clear();

        $result = $this->repo->inchideToateConsInvPacient($this->pacient->getId());

        $this->assertNotNull($result);
        $this->assertSame(true, $this->repo->findOneBy(['id' => $consultatie->getId()])->getInchisa());
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::deleteConsultatie
     */
    public function testItCanDeleteConsultatieWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(5001);
        $this->expectExceptionMessage('Failed operation');

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);
        $service = $this->createMock(NomenclatoareService::class);

        $consMock = $this->getMockBuilder(ConsultatiiRepository::class)
            ->setConstructorArgs([$registry, $em, $service])
            ->onlyMethods(['find'])
            ->getMock();

        $em->method('getRepository')->willReturnMap([
            [Consultatii::class, $consMock],
        ]);

        $consMock->expects($this->once())->method('find')->with($this->consultatie->getId())
            ->willReturn($this->consultatie);

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush')
            ->willThrowException(new \Exception('Failed operation'));

        $repo = new ConsultatiiRepository($registry, $em, $service);

        $repo->deleteConsultatie($this->consultatie->getId());
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::deleteConsultatie
     */
    public function testItCanDeleteConsultatieWithSuccess()
    {
        $this->repo->deleteConsultatie($this->consultatie->getId());

        $this->assertSame($this->repo->findOneBy(['id' => $this->consultatie->getId()])->getStearsa(), true);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::getConsultatiiNefacturatePacient
     */
    public function testCanGetConsultatiiNefacturatePacientWithException()
    {
        $this->expectException(\Exception::class);

        $this->repoMock->getConsultatiiNefacturatePacient(1);
    }

    /**
     * @covers \App\Repository\ConsultatiiRepository::getConsultatiiNefacturatePacient
     */
    public function testCanGetConsultatiiNefacturatePacientWithSuccess()
    {
        $service = $this->createMock(NomenclatoareService::class);
        $service->method('getLunileAnului')->willReturn([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]);

        $em = self::getContainer()->get(EntityManagerInterface::class);

        $pret = $this->em->getRepository(Preturi::class)->findAll()[0];
        $pacient = $this->em->getRepository(Pacienti::class)->find($this->pacient->getId());

        $date = new \DateTime();

        $consultatie = new Consultatii();
        $consultatie->setPret($pret);
        $consultatie->setPacient($pacient);
        $consultatie->setDataConsultatie($date);
        $consultatie->setTarif('200');
        $consultatie->setInchisa(true);
        $consultatie->setStearsa(false);
        $consultatie->setIncasata(true);
        $consultatie->setPlatitaColaborator(false);
        $consultatie->setAhc('ahc test');
        $consultatie->setApp('');
        $em->persist($consultatie);

        $em->flush();
        $em->clear();

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $queryMock = $this->createMock(Query::class);
        $queryMock->method('getResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willReturn([$consultatie]);

        $qbMock = $this->createMock(QueryBuilder::class);

        $qbMock->method('select')->willReturnSelf();
        $qbMock->method('leftJoin')->willReturnSelf();
        $qbMock->method('innerJoin')->willReturnSelf();
        $qbMock->method('where')->willReturnSelf();
        $qbMock->method('andWhere')->willReturnSelf();
        $qbMock->method('setParameters')->willReturnSelf();
        $qbMock->method('groupBy')->willReturnSelf();
        $qbMock->method('having')->willReturnSelf();
        $qbMock->method('getQuery')->willReturn($queryMock);

        $repoMock = $this->getMockBuilder(ConsultatiiRepository::class)
            ->setConstructorArgs([$registry, $em, $service])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repoMock->method('createQueryBuilder')->willReturn($qbMock);

        $result = $repoMock->getConsultatiiNefacturatePacient($pacient);

        $this->assertIsArray($result);
    }
}
