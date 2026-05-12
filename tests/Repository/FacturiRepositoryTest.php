<?php

namespace App\Tests\Repository;

use App\Entity\Consultatii;
use App\Entity\FacturaConsultatie;
use App\Entity\Facturi;
use App\Entity\Owner;
use App\Entity\Pacienti;
use App\Entity\Preturi;
use App\Entity\Role;
use App\Entity\Servicii;
use App\Entity\User;
use App\Repository\FacturiRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FacturiRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $registry = $container->get(ManagerRegistry::class);
        $this->em = $registry->getManager();

        $this->repo = new FacturiRepository($registry, $this->em);

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

        $this->pret = new Preturi();
        $this->pret->setMedic($this->medic);
        $this->pret->setOwner($this->owner);
        $this->pret->setServiciu($this->serviciu);
        $this->pret->setPret(100);
        $this->pret->setProcentajMedic(50);
        $this->pret->setSters(false);
        $this->pret->setCotaTva(0);
        $this->em->persist($this->pret);

        $this->pacient = new Pacienti();
        $this->pacient->setNume('pacient_test');
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

        $this->factura = new Facturi();
        $this->factura->setPacient($this->pacient);
        $this->factura->setSerie('S');
        $this->factura->setNumar(1);
        $this->factura->setData(new \DateTime());
        $this->factura->setScadenta(new \DateTime());
        $this->factura->setTip(0);
        $this->factura->setStornare(null);
        $this->factura->setOwner($this->owner);

        $this->fc = new FacturaConsultatie();
        $this->fc->setFactura($this->factura);
        $this->fc->setConsultatie($this->consultatie);
        $this->fc->setValoare(100);
        $this->em->persist($this->fc);

        $this->factura->addFacturaConsultatii($this->fc);
        $this->em->persist($this->factura);

        $this->em->flush();

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
        $qbMock->method('distinct')->willReturnSelf();
        $qbMock->method('getQuery')->willReturn($queryMock);

        $this->repoMock = $this->getMockBuilder(FacturiRepository::class)
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
            $this->repoMock = null;

            self::ensureKernelShutdown();
            parent::tearDown();
        }
    }

    /**
     * @covers \App\Repository\FacturiRepository::__construct
     */
    public function testItCanBuildRepository()
    {
        $this->assertInstanceOf(FacturiRepository::class, $this->repo);
    }

    /**
     * @covers \App\Repository\FacturiRepository::getAllFacturi
     */
    public function testGetAllFacturiWithException()
    {
        $this->expectException(\Exception::class);

        $this->repoMock->getAllFacturi();
    }

    /**
     * @covers \App\Repository\FacturiRepository::getAllFacturi
     * @covers \App\Repository\FacturiRepository::getTotalFacturiByFilter
     * @covers \App\Repository\FacturiRepository::applyFilter
     * @covers \App\Repository\FacturiRepository::buildSort
     * @dataProvider dataProviderAllFilter
     */
    public function testGetAllFacturi($value, $col)
    {
        $result = $this->repo->getAllFacturi([
            'value' => $value,
            'sort' => ['id' => 'DESC', 'column' => $col],
            'start' => 0,
            'length' => 10,
        ]);

        $this->assertIsArray($result['facturi']);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @covers \App\Repository\FacturiRepository::saveInvoice
     */
    public function testSaveInvoiceWithException()
    {
        $this->expectException(\Exception::class);

        $this->repo->saveInvoice(new Facturi());
    }

    /**
     * @covers \App\Repository\FacturiRepository::saveInvoice
     */
    public function testSaveInvoice()
    {
        $result = $this->repo->saveInvoice($this->factura);

        $this->assertInstanceOf(Facturi::class, $result);
    }

    /**
     * @covers \App\Repository\FacturiRepository::storneaza
     */
    public function testReverseInvoice()
    {
        $this->repo->storneaza($this->factura);

        $this->assertInstanceOf(Facturi::class, $this->factura->getStornare());
    }

    private function dataProviderAllFilter()
    {
        yield ['value' => 'pacient_test', 'column' => '5'];
        yield ['value' => '', null];
    }
}
