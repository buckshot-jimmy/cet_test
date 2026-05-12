<?php

namespace App\Tests\Controller;

use App\Controller\ConsultatiiController;
use App\DTO\ConsultatiiDTO;
use App\Entity\Consultatii;
use App\Entity\MediciTrimitatori;
use App\Entity\Owner;
use App\Entity\Pacienti;
use App\Entity\Preturi;
use App\Entity\Programari;
use App\Entity\Role;
use App\Entity\Servicii;
use App\Entity\User;
use App\PDF\Service\PdfService;
use App\Repository\ConsultatiiRepository;
use App\Repository\MediciTrimitatoriRepository;
use App\Repository\OwnerRepository;
use App\Repository\ServiciiRepository;
use App\Repository\UserRepository;
use App\Services\ConsultatiiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConsultatiiControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->consultatiiService = $this->createMock(ConsultatiiService::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->controller = new ConsultatiiController(
            $this->em,
            $this->translator,
            $this->consultatiiService,
            $this->authorizationChecker
        );

        $this->testUser = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('test@test.ro');

        $this->consRepo = $this->createMock(ConsultatiiRepository::class);

        $this->testMedic = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('damian.popescu@mindreset.ro');

        $this->dbEm = $this->getContainer()->get(EntityManagerInterface::class);
        $this->dbEm->getConnection()->beginTransaction();

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
        $this->dbEm->persist($this->pacient);

        $pret = $this->dbEm->getRepository(Preturi::class)->findAll()[0];

        $programare = new Programari();
        $programare->setPacient($this->pacient);
        $programare->setPret($pret);
        $data = \DateTime::createFromFormat('d-m-Y', '01-01-2026');
        $dataFormatata = $data->format('Y-m-d');
        $programare->setData(new \DateTime($dataFormatata));
        $programare->setOra(\DateTime::createFromFormat('H:i', '09:00'));
        $programare->setAnulata(false);
        $programare->setAdaugataDe($this->testMedic);
        $this->dbEm->persist($programare);

        $this->consultatie = new Consultatii();
        $this->consultatie->setPret($pret);
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
        $this->consultatie->setProgramare($programare);
        $this->dbEm->persist($this->consultatie);

        $this->dbEm->flush();
        $this->dbEm->clear();

        $this->dto = new ConsultatiiDTO(1, '1', '1', 'C', '10' , '1', 'd',
            'c', 't', 'ahc', 'app', '01-01-2026', 1, '23',
            false, false, 'MT', 'inv', 'tr',
            'inv', 'trat', 'obs', 'ev', 'con', 'rez',
            'ob', 'rec', 'cc', 'cc', 'cc',
            'cc', 'pc', 'pc', 'pc', 'pc',
            'rc', 'rc', 'sc', 'sc', 1
        );
    }

    protected function tearDown(): void
    {
        try {
            if (isset($this->dbEm) && null !== $this->dbEm) {
                $conn = $this->dbEm->getConnection();

                if ($conn->isTransactionActive()) {
                    $conn->rollBack();
                }

                $this->dbEm->clear();
                $this->dbEm->close();
            }
        } finally {
            $this->dbEm = null;
            $this->em = null;

            self::ensureKernelShutdown();
            parent::tearDown();
        }
    }

    /**
     * @covers \App\Controller\ConsultatiiController::__construct
     */
    public function testCanBuildController()
    {
        $controller = new ConsultatiiController(
            $this->em, $this->translator, $this->consultatiiService, $this->authorizationChecker
        );

        $this->assertInstanceOf(ConsultatiiController::class, $controller);
    }

    /**
     * @covers \App\Controller\ConsultatiiController::consultatii
     */
    public function testConsultatiiAccessDeniedForView(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('VIEW', $this->isInstanceOf(Consultatii::class))
            ->willReturn(false);

        $this->client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->client->request('GET', '/consultatii');

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\ConsultatiiController::consultatii
     */
    public function testRendersConsultatiiTemplateWithCorrectData(): void
    {
        $this->authorizationChecker->method('isGranted')->willReturn(true);

        $serviciiRepo = $this->createMock(ServiciiRepository::class);
        $serviciiRepo->method('getAllServicii')->willReturn(['servicii']);

        $mediciRepo = $this->createMock(UserRepository::class);
        $mediciRepo->method('getAllMedici')->willReturn(['medici']);

        $ownerRepo = $this->createMock(OwnerRepository::class);
        $ownerRepo->method('getAllOwners')->willReturn(['owners']);

        $mediciTrimitatoriRepo = $this->createMock(MediciTrimitatoriRepository::class);
        $mediciTrimitatoriRepo->method('findAll')->willReturn(['mediciTrimitatori']);

        $this->em->method('getRepository')->willReturnMap([
            [Servicii::class, $serviciiRepo],
            [User::class, $mediciRepo],
            [Owner::class, $ownerRepo],
            [MediciTrimitatori::class, $mediciTrimitatoriRepo],
        ]);

        $user = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('ciprianmarta.cm@gmail.com');

        $this->client->loginUser($user, 'main');

        $this->client->request('GET', '/consultatii');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('Consultatii, investigatii si documente pacienti',
            $response->getContent());
    }

    /**
     * @covers \App\Controller\ConsultatiiController::listConsultatiiCurenteCabinet
     */
    public function testConsultatiiAccessDeniedForListCons(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('VIEW', $this->isInstanceOf(Consultatii::class))
            ->willReturn(false);

        $this->client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->client->request('GET', '/consultatii/list_consultatii_curente_cabinet');

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\ConsultatiiController::listConsultatiiCurenteCabinet
     */
    public function testItCanReturnListConsultatiiCurenteCabinet(): void
    {
        $this->consRepo->method('getAllConsultatiiByFilter')->with(['filter'])->willReturn(['consultatii']);

        $this->client->loginUser($this->testMedic, 'main');

        $roleMock = $this->createMock(Role::class);
        $userMock = $this->createMock(User::class);
        $userMock->method('getRole')
            ->willReturn($roleMock);
        $roleMock->method('getDenumire')
            ->willReturn(ConsultatiiController::ROL_MEDIC);

        $userMock->method('getId')->willReturn($this->testMedic->getId());

        $this->assertEquals($userMock->getId(), $this->testMedic->getId());

        $this->client->request('GET', '/consultatii/list_consultatii_curente_cabinet');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('recordsFiltered', $response->getContent());
    }

    /**
     * @covers \App\Controller\ConsultatiiController::cabinet
     */
    public function testConsultatiiAccessDeniedForListConsCabinet(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('VIEW', $this->isInstanceOf(Consultatii::class))
            ->willReturn(false);

        $this->client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->client->request('GET', '/consultatii_curente_cabinet');

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\ConsultatiiController::cabinet
     */
    public function testItCanRenderConsultatiiCurenteCabinet(): void
    {
        $serviciiRepo = $this->createMock(ServiciiRepository::class);
        $serviciiRepo->method('getAllServicii')->willReturn(['servicii']);

        $mediciRepo = $this->createMock(UserRepository::class);
        $mediciRepo->method('getAllMedici')->willReturn(['medici']);

        $ownerRepo = $this->createMock(OwnerRepository::class);
        $ownerRepo->method('getAllOwners')->willReturn(['owners']);

        $mediciTrimitatoriRepo = $this->createMock(MediciTrimitatoriRepository::class);
        $mediciTrimitatoriRepo->method('findAll')->willReturn(['mediciTrimitatori']);

        $this->em->method('getRepository')->willReturnMap([
            [Servicii::class, $serviciiRepo],
            [User::class, $mediciRepo],
            [Owner::class, $ownerRepo],
            [MediciTrimitatori::class, $mediciTrimitatoriRepo],
        ]);

        $controller = $this->getMockBuilder(ConsultatiiController::class)
            ->setConstructorArgs([$this->em, $this->translator, $this->consultatiiService, $this->authorizationChecker])
            ->onlyMethods(['getUser'])
            ->getMock();

        $this->client->loginUser($this->testMedic, 'main');

        $controller->method('getUser')->willReturn($this->testMedic);

        $this->client->request('GET', '/consultatii_curente_cabinet');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('Consultatii curente in cabinet', $response->getContent());
    }

    /**
     * @covers \App\Controller\ConsultatiiController::stergeConsultatie
     */
    public function testConsultatiiAccessDeniedForDelete(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('DELETE', $this->isInstanceOf(Consultatii::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->stergeConsultatie(new Request([], ['id' => 10]));

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\ConsultatiiController::stergeConsultatie
     */
    public function testItCanDeleteConsultatie()
    {
        $this->authorizationChecker->method('isGranted')->willReturn(true);

        $this->consRepo->expects($this->once())
            ->method('deleteConsultatie')
            ->with(123);

        $this->em->method('getRepository')
            ->with(Consultatii::class)
            ->willReturn($this->consRepo);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $request = new Request([], ['id' => 123]);

        $response = $this->controller->stergeConsultatie($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
        $this->assertEquals('Successful operation', $data['message']);
    }

    /**
     * @covers \App\Controller\ConsultatiiController::getConsultatieInvestigatieEvaluare
     */
    public function testItCanRenderConsultatie()
    {
        $this->client->loginUser($this->testMedic, 'main');

        $this->client->request(
            'GET',
            '/consultatii/get_consultatie_investigatie_eval?id=' . $this->consultatie->getId() . '&view=1');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('data', $response->getContent());
        $this->assertStringContainsString('cnp', $response->getContent());
    }

    /**
     * @covers \App\Controller\ConsultatiiController::salveazaConsultatie
     */
    public function testConsultatiiAccessDeniedForEditCons(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('ADD_EDIT', $this->isInstanceOf(Consultatii::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->em->method('getRepository')
            ->with(Consultatii::class)
            ->willReturn($this->consRepo);

        $this->consRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => '10'])
            ->willReturn(new Consultatii());

        $this->client->loginUser($this->testUser, 'main');

        $dto = $this->createMock(ConsultatiiDTO::class);
        $this->controller->salveazaConsultatie(new Request([], ['id' => 10]), $dto);

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\ConsultatiiController::salveazaInvestigatie
     */
    public function testConsultatiiAccessDeniedForEditAnotherMedicCons(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('ADD_EDIT', $this->isInstanceOf(Consultatii::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->em->method('getRepository')
            ->with(Consultatii::class)
            ->willReturn($this->consRepo);

        $this->consRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => '10'])
            ->willReturn(new Consultatii());

        $this->client->loginUser($this->testMedic, 'main');

        $dto = $this->createMock(ConsultatiiDTO::class);
        $this->controller->salveazaInvestigatie(new Request([], ['id' => 10]), $dto);

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\ConsultatiiController::salveazaConsultatie
     */
    public function testItCanEditConsultatie()
    {
        $this->authorizationChecker->method('isGranted')->willReturn(true);

        $this->em->method('getRepository')
            ->with(Consultatii::class)
            ->willReturn($this->consRepo);

        $this->consRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => '10'])
            ->willReturn(new Consultatii());

        $this->consRepo->expects($this->once())
            ->method('saveConsultatie')
            ->with($this->dto);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $request = new Request([], ['id' => 10]);

        $response = $this->controller->salveazaConsultatie($request, $this->dto);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
        $this->assertEquals('Successful operation', $data['message']);
    }

    /**
     * @covers \App\Controller\ConsultatiiController::salveazaInvestigatie
     */
    public function testItCanEditInvestigatie()
    {
        $this->authorizationChecker->method('isGranted')->willReturn(true);

        $this->em->method('getRepository')
            ->with(Consultatii::class)
            ->willReturn($this->consRepo);

        $this->consRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => '10'])
            ->willReturn(new Consultatii());

        $this->consRepo->expects($this->once())
            ->method('saveInvestigatie')
            ->with($this->dto);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $request = new Request([], ['id' => 10]);

        $response = $this->controller->salveazaInvestigatie($request, $this->dto);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
        $this->assertEquals('Successful operation', $data['message']);
    }

    /**
     * @covers \App\Controller\ConsultatiiController::salveazaEvaluarePsihologica
     */
    public function testConsultatiiAccessDeniedForSaveEval(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('ADD_EDIT', $this->isInstanceOf(Consultatii::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->em->method('getRepository')
            ->with(Consultatii::class)
            ->willReturn($this->consRepo);

        $this->consRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => '10'])
            ->willReturn(new Consultatii());

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->salveazaEvaluarePsihologica(new Request([], ['id' => 10]), $this->dto);

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\ConsultatiiController::salveazaEvaluarePsihologica
     */
    public function testItCanEditEvalPsiho()
    {
        $this->authorizationChecker->method('isGranted')->willReturn(true);

        $this->em->method('getRepository')
            ->with(Consultatii::class)
            ->willReturn($this->consRepo);

        $this->consRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => '10'])
            ->willReturn(new Consultatii());

        $this->consRepo->expects($this->once())
            ->method('saveEvaluarePsihologica')
            ->with($this->dto);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $request = new Request([], ['id' => 10]);

        $response = $this->controller->salveazaEvaluarePsihologica($request, $this->dto);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
        $this->assertEquals('Successful operation', $data['message']);
    }

    /**
     * @covers \App\Controller\ConsultatiiController::getIstoricPacient
     */
    public function testItCanGetIstoricPacient()
    {
        $this->client->loginUser($this->testMedic, 'main');

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $this->client->request('GET', '/consultatii/get_istoric_pacient',
            ['pacient_id' => $this->pacient->getId(), 'tip_serviciu' => 0]);

        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('tipServiciu', $response->getContent());
        $this->assertStringContainsString('prenume', $response->getContent());
    }

    /**
     * @covers \App\Controller\ConsultatiiController::pdfServiciiFormulare
     */
    public function testItCanGeneratePDF()
    {
        $template = 'buletin_investigatie.html.twig';

        $request = new Request([], [
            'id' => 1,
            'template' => $template
        ]);

        $pdfService = $this->createMock(PdfService::class);

        $pdfService->expects($this->once())
            ->method('printToPdf')
            ->with(
                1,
                $template,
                [
                    'orientation' => 'L',
                    'footer' => '{PAGENO}/{nb}'
                ]
            );

        $response = $this->controller->pdfServiciiFormulare($request, $pdfService);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \App\Controller\ConsultatiiController::inchideDeschide
     */
    public function testConsultatiiAccessDeniedInchideDeschide(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('ADD_EDIT', $this->isInstanceOf(Consultatii::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->em->method('getRepository')
            ->with(Consultatii::class)
            ->willReturn($this->consRepo);

        $this->consRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => '10'])
            ->willReturn(new Consultatii());

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->inchideDeschide(new Request([], ['id' => '10']));

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\ConsultatiiController::inchideDeschide
     */
    public function testInchideDeschideCons()
    {
        $this->authorizationChecker->method('isGranted')->willReturn(true);

        $this->em->method('getRepository')
            ->with(Consultatii::class)
            ->willReturn($this->consRepo);

        $this->consRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => '10'])
            ->willReturn(new Consultatii());

        $request = new Request([], ['id' => 10]);

        $response = $this->controller->inchideDeschide($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \App\Controller\ConsultatiiController::getConsultatiiPeLuni
     */
    public function testItCanGetConsultatiiPeLuni()
    {
        $this->client->loginUser($this->testMedic, 'main');

        $this->client->request('GET', '/consultatii/get_consultatii_luni', ['id' => 2]);

        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('consultatiiMedic', $response->getContent());
        $this->assertStringContainsString('medic', $response->getContent());
    }

    /**
     * @covers \App\Controller\ConsultatiiController::getIncasariMedicPeLuni
     */
    public function testItCanGetIncasariMedicPeLuni()
    {
        $this->client->loginUser($this->testMedic, 'main');

        $this->client->request('GET', '/consultatii/get_incasari_medici_luni', ['id' => 2]);

        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('incasariMedic', $response->getContent());
        $this->assertStringContainsString('incasariMedic', $response->getContent());
    }
}
