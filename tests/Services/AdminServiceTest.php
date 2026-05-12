<?php

namespace App\Tests\Services;

use App\Entity\Consultatii;
use App\Entity\MesajAdmin;
use App\Entity\Owner;
use App\Entity\Pacienti;
use App\Entity\Preturi;
use App\Entity\Role;
use App\Entity\Servicii;
use App\Entity\Specialitate;
use App\Entity\Titulatura;
use App\Entity\User;
use App\Repository\ConsultatiiRepository;
use App\Repository\MesajAdminRepository;
use App\Repository\PacientiRepository;
use App\Repository\RoleRepository;
use App\Repository\SpecialitateRepository;
use App\Repository\TitulaturaRepository;
use App\Services\AdminService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdminServiceTest extends KernelTestCase
{
    public function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->em = $container->get('doctrine.orm.entity_manager');
        $this->translator = $container->get('translator');

        $this->service = new AdminService($this->em, $this->translator);

        $this->emMock = $this->createMock(EntityManagerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->serviceMock = new AdminService($this->emMock, $this->translator);

        $this->rolesRepo = $this->createMock(RoleRepository::class);
        $this->specialitateRepo = $this->createMock(SpecialitateRepository::class);
        $this->titulaturaRepo = $this->createMock(TitulaturaRepository::class);
        $this->consultatiiRepo = $this->createMock(ConsultatiiRepository::class);
        $this->mesajRepo = $this->createMock(MesajAdminRepository::class);
        $this->pacientiRepo = $this->createMock(PacientiRepository::class);

        $this->emMock->method('getRepository')->willReturnMap([
            [Role::class, $this->rolesRepo],
            [Specialitate::class, $this->specialitateRepo],
            [Titulatura::class, $this->titulaturaRepo],
            [Consultatii::class, $this->consultatiiRepo],
            [MesajAdmin::class, $this->mesajRepo],
            [Pacienti::class, $this->pacientiRepo]
        ]);

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

        $this->owner = (new Owner())->setDenumire('Owner')->setCui('92345678');
        $this->owner->setSters(false);
        $this->owner->setAdresa('Addr');
        $this->owner->setSerieFactura('Ftest');
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
        $this->pacient->setNume('Pacient_Test');
        $this->pacient->setPrenume('Prenume_Test');
        $this->pacient->setCnp('1234567890109');
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

        $this->mesaj = new MesajAdmin();
        $this->mesaj->setMesaj('Mesaj');
        $this->mesaj->setActiv(true);
        $this->em->persist($this->mesaj);

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
            $this->serviceMock = null;

            self::ensureKernelShutdown();
            parent::tearDown();
        }
    }

    /**
     * @covers \App\Services\AdminService::__construct
     */
    public function testItCanBuildService()
    {
        $this->assertInstanceOf(AdminService::class, $this->service);
    }

    /**
     * @covers \App\Services\AdminService::getNomenclatoareMedicale
     */
    public function testItCanGetNomenclatoareWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(4001);
        $this->expectExceptionMessage('Data collection error');

        $this->rolesRepo->expects($this->once())->method('getAllRoles')
            ->willThrowException(new \Exception('Data collection error'));

        $this->serviceMock->getNomenclatoareMedicale();
    }

    /**
     * @covers \App\Services\AdminService::getNomenclatoareMedicale
     */
    public function testItCanGetNomenclatoareWithSuccess()
    {
        $result = $this->service->getNomenclatoareMedicale();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('roluri', $result);
        $this->assertNotEmpty($result['roluri']);
    }

    /**
     * @covers \App\Services\AdminService::getLoggedUserData
     */
    public function testItCanGetLoggedUserData()
    {
        $this->assertEquals($this->medic->getNume(), $this->service->getLoggedUserData($this->medic)['nume']);
    }

    /**
     * @covers \App\Services\AdminService::getValoriGrafice
     */
    public function testItCanGetVAloriGraficeWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(4001);
        $this->expectExceptionMessage('Data collection error');

        $this->consultatiiRepo->expects($this->once())->method('valoareServicii')->with([])
            ->willThrowException(new \Exception('Data collection error'));

        $this->serviceMock->getValoriGrafice([]);
    }

    /**
     * @covers \App\Services\AdminService::getValoriGrafice
     */
    public function testItCanGetVAloriGraficeWithSuccess()
    {
        $result = $this->service->getValoriGrafice(['rol' => 'ROLE_Medic', 'id' => $this->medic->getId()]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('luna', $result);
        $this->assertIsArray($result['total']);
        $this->assertIsArray($result['luna']);
    }

    /**
     * @covers \App\Services\AdminService::getTotaluriPacienti
     */
    public function testGetTotaluriPacientiWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(4001);
        $this->expectExceptionMessage('Data collection error');

        $this->consultatiiRepo->expects($this->once())->method('getNrPacientiConsultatiDeMedic')
            ->willThrowException(new \Exception('Data collection error'));

        $this->serviceMock->getTotaluriPacienti(['id' => -10]);
    }

    /**
     * @covers \App\Services\AdminService::getTotaluriPacienti
     */
    public function testGetTotaluriPacientiWithSuccess()
    {
       $result = $this->service->getTotaluriPacienti(['id' => $this->medic->getId()]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('consultatiiPacientiMedic', $result);
        $this->assertArrayHasKey('valoriCabinet', $result);
        $this->assertSame(1, $result['consultatiiPacientiMedic']['nrPacientiMedic']);
    }

    /**
     * @covers \App\Services\AdminService::setSessionInfo
     */
    public function testItCanSetSessionInfo()
    {
        $result = $this->service->setSessionInfo(
            new Session(),
            [
                'loggedUserData' => ['id' => $this->medic->getId(), 'nume' => $this->medic->getNume()],
                'specialitati' => [],
                'titulaturi' => [],
                'roluri' => [],
                'informare' => $this->mesaj
            ]
        );

        $this->assertInstanceOf(Session::class, $result);
        $this->assertEquals($this->medic->getNume(), $result->get('loggedUserData')['nume']);
    }

    /**
     * @covers \App\Services\AdminService::buildValidationErrors
     */
    public function testItCanBuildValidationErrors()
    {
        $violation1 = new ConstraintViolation(
            'Data collection error', null, [], '', '', null);
        $violation2 = new ConstraintViolation(
            'Successful operation', null, [], '', '', null);

        $violationsList = new ConstraintViolationList([$violation1, $violation2]);

        $result = $this->service->buildValidationErrors($violationsList);

        $this->assertSame(' ' . 'Eroare la preluarea datelor. Operatiune reusita.', $result);
    }
}
