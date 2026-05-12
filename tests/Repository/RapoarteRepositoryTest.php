<?php

namespace App\Tests\Repository;

use App\Entity\Consultatii;
use App\Entity\Owner;
use App\Entity\Pacienti;
use App\Entity\Preturi;
use App\Entity\RapoarteColaboratori;
use App\Entity\Role;
use App\Entity\Servicii;
use App\Entity\User;
use App\Repository\ConsultatiiRepository;
use App\Repository\OwnerRepository;
use App\Repository\RapoarteRepository;
use App\Repository\UserRepository;
use App\Services\NomenclatoareService;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RapoarteRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $registry = $container->get(ManagerRegistry::class);
        $this->em = $registry->getManager();
        $this->service = new NomenclatoareService();

        $this->repo = new RapoarteRepository($registry, $this->em, $this->service);

        $this->em->getConnection()->beginTransaction();

        $date = new \DateTime();

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

        $this->owner2 = (new Owner())->setDenumire('Owner2')->setCui('12345679');
        $this->owner2->setSters(false);
        $this->owner2->setAdresa('Addr2');
        $this->owner2->setSerieFactura('F2');
        $this->em->persist($this->owner2);

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
        $this->pacient->setCnp('1234567890122');
        $this->pacient->setTelefon('0711111111');
        $this->pacient->setAdresa('Addr');
        $this->pacient->setTara('Romania');
        $this->pacient->setCi('XB');
        $this->pacient->setCiEliberat('City');
        $this->pacient->setSters(false);
        $this->pacient->setDataInreg($date);
        $this->em->persist($this->pacient);

        $this->consultatie = new Consultatii();
        $this->consultatie->setPret($this->pret);
        $this->consultatie->setPacient($this->pacient);
        $this->consultatie->setDiagnostic('diag');
        $this->consultatie->setConsultatie('cons');
        $this->consultatie->setTratament('trat');
        $this->consultatie->setNrInreg('1');
        $this->consultatie->setDataConsultatie($date);
        $this->consultatie->setTarif(100);
        $this->consultatie->setLoc('C');
        $this->consultatie->setInchisa(true);
        $this->consultatie->setStearsa(false);
        $this->consultatie->setIncasata(true);
        $this->consultatie->setEvalPsiho('eval');
        $this->em->persist($this->consultatie);

        $this->consultatie2 = new Consultatii();
        $this->consultatie2->setPret($this->pret2);
        $this->consultatie2->setPacient($this->pacient);
        $this->consultatie2->setDiagnostic('diag');
        $this->consultatie2->setConsultatie('cons');
        $this->consultatie2->setTratament('trat');
        $this->consultatie2->setNrInreg('1');
        $this->consultatie2->setDataConsultatie($date);
        $this->consultatie2->setTarif(100);
        $this->consultatie2->setLoc('C');
        $this->consultatie2->setInchisa(true);
        $this->consultatie2->setStearsa(false);
        $this->consultatie2->setIncasata(true);
        $this->consultatie2->setEvalPsiho('eval');
        $this->em->persist($this->consultatie2);

        $this->raport = new RapoarteColaboratori();
        $this->raport->setDataGenerarii($date);
        $this->raport->setSuma(200);
        $this->raport->setMedic($this->medic);
        $this->raport->setOwner($this->owner);
        $this->raport->setLuna($this->service->getLunileAnului()[intval($date->format('m'))]);
        $this->raport->setAn($date->format('Y'));
        $this->raport->setStare(RapoarteRepository::STARE_NEPLATITA);
        $this->em->persist($this->raport);

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
            $this->service = null;

            self::ensureKernelShutdown();
            parent::tearDown();
        }
    }

    /**
     * @covers \App\Repository\RapoarteRepository::__construct
     */
    public function testCanBuildRapoarteRepository()
    {
        $this->assertInstanceOf(RapoarteRepository::class, $this->repo);
    }

    /**
     * @covers \App\Repository\RapoarteRepository::getAllRapoarteColaboratori
     */
    public function testCanGetAllRapoarteWithException()
    {
        $this->expectException(\Exception::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);
        $service = $this->createMock(NomenclatoareService::class);

        $repoMock = $this->getMockBuilder(RapoarteRepository::class)
            ->setConstructorArgs([$registry, $em, $service])
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

        $repoMock->getAllRapoarteColaboratori([]);
    }

    /**
     * @covers \App\Repository\RapoarteRepository::getAllRapoarteColaboratori
     * @covers \App\Repository\RapoarteRepository::getTotalRapoarte
     * @covers \App\Repository\RapoarteRepository::applyFilters
     * @covers \App\Repository\RapoarteRepository::buildSort
     * @dataProvider dataProviderSortCol
     */
    public function testCanGetAllRapoarteWithFilterSuccess($col)
    {
        $result = $this->repo->getAllRapoarteColaboratori(
            [
                'value' => 'Test',
                'start' => 0,
                'length' => 10,
                'sort' => ['column' => $col, 'dir' => 'DESC'],
                'propertyFilters' => [
                    1 => ['medic' => ['sters' => false]],
                    2 => ['owner' => ['sters' => false]]
                ]
            ]
        );

        $this->assertArrayHasKey('nume_medic', $result['rapoarteColaboratori'][0]);
        $this->assertArrayHasKey('rapoarteColaboratori', $result);
        $this->assertArrayHasKey('total', $result);
    }

    private function dataProviderSortCol()
    {
        yield ['1'];
        yield ['2'];
        yield ['3'];
        yield ['4'];
        yield ['5'];
        yield [''];
    }

    /**
     * @covers \App\Repository\RapoarteRepository::saveRaportColaboratori
     */
    public function testItCanSaveRaportWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(4001);
        $this->expectExceptionMessage('Failed operation');

        $formData = [
            'owner' => 1,
            'medic' => 2
        ];

        $registry = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $ownerRepo = $this->createMock(OwnerRepository::class);
        $ownerRepo->method('find')->willReturn(new Owner());

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('find')->willReturn(new User());

        $consultatiiRepo = $this->createMock(ConsultatiiRepository::class);
        $consultatiiRepo->method('calculeazaPlataColaborator')
            ->willReturn(1000);

        $em->method('getRepository')
            ->willReturnMap([
                [Owner::class, $ownerRepo],
                [User::class, $userRepo],
                [Consultatii::class, $consultatiiRepo],
            ]);

        $service = $this->createMock(NomenclatoareService::class);
        $service->method('getLunileAnului')
            ->willReturn([
                1 => 'Ianuarie',
                2 => 'Februarie',
                3 => 'Martie',
                4 => 'Aprilie',
                5 => 'Mai',
                6 => 'Iunie',
                7 => 'Iulie',
                8 => 'August',
                9 => 'Septembrie',
                10 => 'Octombrie',
                11 => 'Noiembrie',
                12 => 'Decembrie',
            ]);

        $repo = new RapoarteRepository($registry, $em, $service);

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush')
            ->willThrowException(new \Exception('Failed operation'));

        $repo->saveRaportColaboratori([$formData]);
    }

    /**
     * @covers \App\Repository\RapoarteRepository::saveRaportColaboratori
     */
    public function testItCanSaveRaportWithSuccess()
    {
        $result = $this->repo->saveRaportColaboratori([
            'owner' => $this->owner->getId(),
            'medic' => $this->medic->getId(),
            'luna' => $this->consultatie->getDataConsultatie()->format('m'),
            'an' => $this->consultatie->getDataConsultatie()->format('Y'),
        ]);

        $this->assertNotNull($result);
    }

    /**
     * @covers \App\Repository\RapoarteRepository::getRaportByFilters
     */
    public function testItCanGetRaportByFilterWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(4001);
        $this->expectExceptionMessage('Data collection error');

        $this->repo->getRaportByFilters(['medic' => new User()]);
    }

    /**
     * @covers \App\Repository\RapoarteRepository::getRaportByFilters
     */
    public function testItCanGetRaportByFilterWithSuccess()
    {
        $result = $this->repo->getRaportByFilters([
            'owner' => $this->owner->getId(),
            'medic' => $this->medic->getId(),
            'luna' => $this->consultatie->getDataConsultatie()->format('m'),
            'an' => $this->consultatie->getDataConsultatie()->format('Y'),
        ]);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('dataGenerarii', $result);
    }

    /**
     * @covers \App\Repository\RapoarteRepository::platesteColaborator
     */
    public function testPlatesteColaboratorWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed operation');
        $this->expectExceptionCode(4001);

        $formData = [
            'raport_colaboratori_id' => 10
        ];

        $registry = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $raport = $this->createMock(RapoarteColaboratori::class);

        $repo = $this->getMockBuilder(RapoarteRepository::class)
            ->setConstructorArgs([$registry, $em, $this->service])
            ->onlyMethods(['find'])
            ->getMock();

        $repo->method('find')->willReturn($raport);

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush')
            ->willThrowException(new \Exception('Failed operation'));

        $repo->platesteColaborator($formData);
    }

    /**
     * @covers \App\Repository\RapoarteRepository::platesteColaborator
     */
    public function testPlatesteColaboratorWithSuccess()
    {
        $date = new \DateTime();

        $role = (new Role())->setDenumire('ROLE_TestMedic');
        $this->em->persist($role);

        $medic = (new User())
            ->setUsername('test_medic1')
            ->setPassword('test_password_hash')
            ->setNume('Nume_Test1')
            ->setPrenume('Prenume_Test1')
            ->setTelefon('0700000000')
            ->setRole($role);
        $medic->setSters(false);
        $medic->setParolaSchimbata(false);
        $this->em->persist($medic);

        $owner = (new Owner())->setDenumire('Owner99')->setCui('1234560');
        $owner->setAdresa('Addr98');
        $owner->setSerieFactura('F98');
        $owner->setSters(false);
        $this->em->persist($owner);

        $raport = new RapoarteColaboratori();
        $raport->setDataGenerarii($date);
        $raport->setSuma(200);
        $raport->setMedic($medic);
        $raport->setOwner($owner);
        $raport->setLuna($this->service->getLunileAnului()[intval($date->format('m'))]);
        $raport->setAn($date->format('Y'));
        $raport->setStare(RapoarteRepository::STARE_NEPLATITA);
        $this->em->persist($raport);

        $this->em->flush();
        $this->em->clear();

        $result = $this->repo->platesteColaborator(['raport_colaboratori_id' => $this->raport->getId()]);

        $this->assertNotNull($result);
    }
}
