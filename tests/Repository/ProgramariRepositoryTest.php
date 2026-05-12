<?php

namespace App\Tests\Repository;

use App\Entity\Owner;
use App\Entity\Pacienti;
use App\Entity\Preturi;
use App\Entity\Programari;
use App\Entity\Role;
use App\Entity\Servicii;
use App\Entity\User;
use App\Repository\PacientiRepository;
use App\Repository\PreturiRepository;
use App\Repository\ProgramariRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ProgramariRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $registry = $container->get(ManagerRegistry::class);
        $this->em = $registry->getManager();

        $this->repo = new ProgramariRepository($registry, $this->em);

        $this->em->getConnection()->beginTransaction();

        $this->pacient = new Pacienti();
        $this->pacient->setNume('Nume_Test');
        $this->pacient->setPrenume('Prenume_Test');
        $this->pacient->setEmail('test@test.com');
        $this->pacient->setTelefon('0711111111');
        $this->pacient->setAdresa('Addr');
        $this->pacient->setTara('Romania');
        $this->pacient->setCnp('1790630060998');
        $this->pacient->setCi('XB');
        $this->pacient->setCiEliberat('City');
        $this->em->persist($this->pacient);

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

        $this->em->flush();
        $this->em->clear();

        $this->medic = $this->em->getRepository(User::class)->find($this->medic->getId());
    }

    /**
     * @covers \App\Repository\ProgramariRepository::__construct
     */
    public function testCanBuildPreturiRepository()
    {
        $this->assertInstanceOf(ProgramariRepository::class, $this->repo);
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
     * @covers \App\Repository\ProgramariRepository::getAllProgramari
     */
    public function testItCanGetAllProgramariWithException()
    {
        $this->expectException(\Exception::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $repoMock = $this->getMockBuilder(ProgramariRepository::class)
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

        $repoMock->getAllProgramari([]);
    }

    /**
     * @covers \App\Repository\ProgramariRepository::getAllProgramari
     * @covers \App\Repository\ProgramariRepository::getTotalProgramari
     * @covers \App\Repository\ProgramariRepository::applyFilters
     * @covers \App\Repository\ProgramariRepository::buildSort
     * @dataProvider dataProviderSortCol
     */
    public function testItCanGetAllProgramariWithFilterWithSuccess($col)
    {
        $result = $this->repo->getAllProgramari(
            [
                'value' => 'Consult',
                'start' => 0,
                'length' => 10,
                'sort' => ['column' => $col, 'dir' => 'DESC'],
                'propertyFilters' => [
                    0 => ['preturi' => ['sters' => false]],
                    1 => ['medic' => ['sters' => false]],
                ]
            ]
        );

        $this->assertArrayHasKey('programari', $result);
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
     * @covers \App\Repository\ProgramariRepository::saveProgramare
     */
    public function testItCanSavePretWithMissingIdException()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Missing ID');

        $this->repo->saveProgramare([
            'programare_id' => -10, 'programare_pacient' => 1, 'programare_pret_serviciu' => 1], 1
        );
    }

    /**
     * @covers \App\Repository\ProgramariRepository::saveProgramare
     */
    public function testItCanSavePretWithPersistException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed operation');

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $pacienti = $this->createMock(PacientiRepository::class);
        $preturi = $this->createMock(PreturiRepository::class);

        $programareMock = $this->getMockBuilder(ProgramariRepository::class)
            ->setConstructorArgs([$registry, $em])
            ->onlyMethods(['find'])
            ->getMock();

        $em->method('getRepository')->willReturnMap([
            [Pacienti::class, $pacienti],
            [Preturi::class, $preturi],
            [Programari::class, $programareMock],
        ]);

        $programareMock->method('find')->with($this->programare->getId())->willReturn($this->programare);

        $pacienti->method('find')->willReturn(new Pacienti());
        $preturi->method('find')->willReturn(new Preturi());

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush')->willThrowException(new \Exception());

        $programareMock->saveProgramare([
            'programare_id' => $this->programare->getId(), 'programare_pacient' => -10,
            'programare_pret_serviciu' => $this->serviciu->getId(), 'programare_data' => '01-01-2026',
            'programare_ora' => '09:00',
        ], $this->medic);
    }

    /**
     * @covers \App\Repository\ProgramariRepository::saveProgramare
     */
    public function testItCanSaveProgramareWithSuccess()
    {
        $result = $this->repo->saveProgramare([
            'programare_id' => $this->programare->getId(), 'programare_pacient' => $this->pacient->getId(),
            'programare_pret_serviciu' => $this->pret->getId(), 'programare_data' => '01-01-2026',
            'programare_ora' => '09:00',
        ], $this->medic);

        $this->assertSame($result, $this->programare->getId());
    }

    /**
     * @covers \App\Repository\ProgramariRepository::getProgramare
     */
    public function testItCanGetProgramareWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Data collection error');

        $this->repo->getProgramare(new Programari());
    }

    /**
     * @covers \App\Repository\ProgramariRepository::getProgramare
     */
    public function testItCanGetProgramareWithSuccess()
    {
        $result = $this->repo->getProgramare($this->programare->getId());

        $this->assertSame($this->programare->getId(), $result['id']);
    }

    /**
     * @covers \App\Repository\ProgramariRepository::cancelProgramare
     */
    public function testItCanCancelProgramareWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(5001);
        $this->expectExceptionMessage('Failed operation');

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $programareMock = $this->getMockBuilder(ProgramariRepository::class)
            ->setConstructorArgs([$registry, $em])
            ->onlyMethods(['find'])
            ->getMock();

        $em->method('getRepository')->willReturnMap([
            [Programari::class, $programareMock],
        ]);

        $programareMock->expects($this->once())->method('find')->with($this->programare->getId())
            ->willReturn($this->programare);

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush')
            ->willThrowException(new \Exception('Failed operation'));

        $repo = new ProgramariRepository($registry, $em);

        $repo->cancelProgramare($this->programare->getId());
    }

    /**
     * @covers \App\Repository\ProgramariRepository::cancelProgramare
     */
    public function testItCanCancelProgramareWithSuccess()
    {
        $this->repo->cancelProgramare($this->programare->getId());

        $this->assertSame($this->repo->findOneBy(['id' => $this->programare->getId()])->getAnulata(), 1);
    }

    /**
     * @covers \App\Repository\ProgramariRepository::checkAvailability
     */
    public function testItCanCheckAvailabilityWithException()
    {
        $this->expectException(\Exception::class);

        $queryMock = $this->createMock(Query::class);
        $queryMock->method('getSingleScalarResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willThrowException(new \Exception('Data collection error'));

        $qbMock = $this->createMock(QueryBuilder::class);

        $qbMock->method('select')->willReturnSelf();
        $qbMock->method('leftJoin')->willReturnSelf();
        $qbMock->method('where')->willReturnSelf();
        $qbMock->method('andWhere')->willReturnSelf();
        $qbMock->method('setParameters')->willReturnSelf();
        $qbMock->method('getQuery')->willReturn($queryMock);

        $repoMock = $this->getMockBuilder(ProgramariRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repoMock->method('createQueryBuilder')->willReturn($qbMock);

        $repoMock->checkAvailability(['programare_medic' => 1, 'programare_data' => '10-10-2026',
            'programare_ora' => '09:00', ]
        );
    }

    /**
     * @covers \App\Repository\ProgramariRepository::checkAvailability
     */
    public function testItCanCheckAvailabilityWithSuccess()
    {
        $result = $this->repo->checkAvailability(['programare_medic' => 1, 'programare_data' => '10-10-2026',
                'programare_ora' => '09:00', ]
        );

        $this->assertTrue($result);
    }
}
