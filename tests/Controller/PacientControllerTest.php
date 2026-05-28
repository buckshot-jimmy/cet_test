<?php

namespace App\Tests\Controller;

use App\Controller\PacientController;
use App\DTO\PacientDTO;
use App\Entity\Consultatie;
use App\Entity\Owner;
use App\Entity\Pacient;
use App\Entity\Programare;
use App\Entity\Serviciu;
use App\Entity\User;
use App\Repository\ConsultatieRepository;
use App\Repository\OwnerRepository;
use App\Repository\PacientRepository;
use App\Repository\PretRepository;
use App\Repository\ServiciuRepository;
use App\Repository\UserRepository;
use App\Services\AdminService;
use App\Services\NomenclatoareService;
use App\Services\PushNotificationService;
use App\Validator\PacientConstraints;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PacientControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->constraint = $this->createMock(PacientConstraints::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->controller = new PacientController(
            $this->em,
            $this->validator,
            $this->constraint,
            $this->translator,
            $this->authorizationChecker
        );

        $this->testUser = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('test@test.ro');

        $this->pacientiRepo = $this->createMock(PacientRepository::class);
        $this->consRepo = $this->createMock(ConsultatieRepository::class);

        $this->testMedic = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('damian.popescu@mindreset.ro');

        $this->dto = new PacientDTO(1, 'n', 'p', '1790630060774', '0745545689' ,
            '', 'ciprianmarta.cm@gmail.com', 'a', 'Alba', 'Baciu', 'Romania',
            'XB', 'City', 'l', 'o', '2026-01-01', false,
            'o', 1, $this->testMedic->getId(), [], []);

        $this->controllerMock = $this->getMockBuilder(PacientController::class)
            ->setConstructorArgs([$this->em, $this->validator, $this->constraint, $this->translator,
                $this->authorizationChecker])
            ->onlyMethods(['getUser'])
            ->getMock();

        $this->dbEm = $this->getContainer()->get(EntityManagerInterface::class);
        $this->dbEm->getConnection()->beginTransaction();

        $this->pacient = new Pacient();
        $this->pacient->setNume('Pacient_Test');
        $this->pacient->setPrenume('Prenume_Test');
        $this->pacient->setCnp('1200606125232');
        $this->pacient->setTelefon('0711111111');
        $this->pacient->setAdresa('Addr');
        $this->pacient->setTara('Romania');
        $this->pacient->setCi('XB');
        $this->pacient->setCiEliberat('City');
        $this->pacient->setSters(false);
        $this->pacient->setDataInreg(new \DateTime());
        $this->dbEm->persist($this->pacient);

        $this->dbEm->flush();
        $this->dbEm->clear();
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
     * @covers \App\Controller\PacientController::__construct
     */
    public function testCanBuildController()
    {
        $controller = new PacientController(
            $this->em,
            $this->validator,
            $this->constraint,
            $this->translator,
            $this->authorizationChecker
        );

        $this->assertInstanceOf(PacientController::class, $controller);
    }

    /**
     * @covers \App\Controller\PacientController::pacienti
     */
    public function testRendersConsultatiiTemplateWithCorrectData(): void
    {
        $serviciiRepo = $this->createMock(ServiciuRepository::class);
        $serviciiRepo->method('getAllServicii')->willReturn(['servicii']);

        $mediciRepo = $this->createMock(UserRepository::class);
        $mediciRepo->method('getAllMedici')->willReturn(['medici']);

        $ownerRepo = $this->createMock(OwnerRepository::class);
        $ownerRepo->method('getAllOwners')->willReturn(['owners']);

        $nomenclatoare = $this->createMock(NomenclatoareService::class);
        $nomenclatoare->method('getTari')->willReturn(['tari']);
        $nomenclatoare->method('getJudete')->willReturn(['judete']);
        $nomenclatoare->method('getStariCivile')->willReturn(['stariCivile']);

        $this->em->method('getRepository')->willReturnMap([
            [Serviciu::class, $serviciiRepo],
            [User::class, $mediciRepo],
            [Owner::class, $ownerRepo]
        ]);

        $user = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('ciprianmarta.cm@gmail.com');

        $this->client->loginUser($user, 'main');

        $this->client->request('GET', '/pacienti');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('Lista pacienti', $response->getContent());
    }

    /**
     * @covers \App\Controller\PacientController::list
     */
    public function testItCanFetchPacientiList()
    {
        $this->pacientiRepo->method('getAllPacienti')->with(['filter'])->willReturn(['pacienti']);

        $this->client->loginUser($this->testMedic, 'main');

        $this->client->request('GET', '/pacienti/list');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('recordsFiltered', $response->getContent());
    }

    /**
     * @covers \App\Controller\PacientController::listPacientiCuConsultatii
     */
    public function testItCanListPacientiCuConsultatie()
    {
        $this->pacientiRepo->method('getAllPacientiCuConsultatii')->with(['filter'])->willReturn(['pac']);

        $this->client->loginUser($this->testMedic, 'main');

        $this->client->request('GET', '/pacienti/list_pacienti_cu_consultatii');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('recordsFiltered', $response->getContent());
    }

    /**
     * @covers \App\Controller\PacientController::listPacientiInCabinet
     */
    public function testItCanGetListPacientiInCabinet()
    {
        $this->pacientiRepo->method('getPacientiInCabinet')->with(['filter'])->willReturn(['pacienti']);

        $this->client->loginUser($this->testMedic, 'main');

        $this->client->request('GET', '/pacienti/list_pacienti_in_cabinet');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('recordsFiltered', $response->getContent());
    }

    /**
     * @covers \App\Controller\PacientController::sterge
     */
    public function testPacientiAccessDeniedForDelete(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('DELETE', $this->isInstanceOf(Pacient::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->sterge(new Request([], ['id' => 10]));

        $this->assertResponseStatusCodeSame(403);
    }
    /**
     * @covers \App\Controller\PacientController::salveazaPacient
     */
    public function testPacientiAccessDeniedForEdit(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('ADD_EDIT', $this->isInstanceOf(Pacient::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->salveazaPacient($this->createMock(AdminService::class), $this->dto);

        $this->assertResponseStatusCodeSame(403);
    }


    /**
     * @covers \App\Controller\PacientController::sterge
     */
    public function testPacientiDeleteWithPacientHasCnsultationOrAppointment(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('DELETE', $this->isInstanceOf(Pacient::class))
            ->willReturn(true);

        $this->em->method('getRepository')
            ->with(Pacient::class)
            ->willReturn($this->pacientiRepo);

        $this->pacientiRepo->method('find')->willReturn($this->pacient);
        $this->pacient->addConsultatii(new Consultatie());
        $this->pacient->addProgramari(new Programare());

        $this->translator->method('trans')
            ->with('Patient has consultations or appointments.')
            ->willReturn('Patient has consultations or appointments.');

        $request = new Request([], ['id' => $this->pacient->getId()]);

        $response = $this->controller->sterge($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $data['status']);
        $this->assertEquals('Patient has consultations or appointments.', $data['message']);
    }

    /**
     * @covers \App\Controller\PacientController::sterge
     */
    public function testPacientiCanBeDeleted(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('DELETE', $this->isInstanceOf(Pacient::class))
            ->willReturn(true);

        $this->pacientiRepo->expects($this->once())
            ->method('deletePacient')
            ->with($this->pacient->getId());

        $this->em->method('getRepository')
            ->with(Pacient::class)
            ->willReturn($this->pacientiRepo);

        $this->pacientiRepo->method('find')->willReturn($this->pacient);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $request = new Request([], ['id' => $this->pacient->getId()]);

        $response = $this->controller->sterge($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
        $this->assertEquals('Successful operation', $data['message']);
    }

    /**
     * @covers \App\Controller\PacientController::salveazaPacient
     */
    public function testPacientiCanShowValidationErrorsFromEdit(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('ADD_EDIT', $this->isInstanceOf(Pacient::class))
            ->willReturn(true);

        $violation = new ConstraintViolation(
            'Some validation error',
            null,
            [],
            '',
            'name',
            null
        );

        $violations = new ConstraintViolationList([$violation]);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->translator->method('trans')
            ->with('Failed operation')
            ->willReturn('Failed operation.');

        $admin = $this->createMock(AdminService::class);
        $admin->expects($this->once())
            ->method('buildValidationErrors')
            ->with($violations)
            ->willReturn('Some validation error');

        $this->client->loginUser($this->testMedic, 'main');

        $response = $this->controller->salveazaPacient($admin, $this->dto);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $data['status']);
        $this->assertSame('Failed operation. Some validation error', $data['message']);
    }

    /**
     * @covers \App\Controller\PacientController::salveazaPacient
     */
    public function testPacientiCanBeEdited(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('ADD_EDIT', $this->isInstanceOf(Pacient::class))
            ->willReturn(true);

        $this->em->method('getRepository')
            ->with(Pacient::class)
            ->willReturn($this->pacientiRepo);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $this->client->loginUser($this->testMedic, 'main');

        $this->controllerMock->method('getUser')->willReturn($this->testMedic);

        $this->pacientiRepo->method('savePacient')->with($this->dto, $this->testMedic);

        $response = $this->controllerMock->salveazaPacient(
            $this->createMock(AdminService::class), $this->dto);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
        $this->assertEquals('Successful operation', $data['message']);
    }

    /**
     * @covers \App\Controller\PacientController::getPacient
     */
    public function testItCanGetPacient()
    {
        $this->client->loginUser($this->testMedic, 'main');

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $this->em->method('getRepository')
            ->with(Pacient::class)
            ->willReturn($this->pacientiRepo);

        $this->client->request('GET', '/pacienti/get_pacient', ['id' => $this->pacient->getId()]);

        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('locMunca', $response->getContent());
        $this->assertStringContainsString('cnp', $response->getContent());
    }

    /**
     * @covers \App\Controller\PacientController::getPreturi
     */
    public function testCanGetPreturi()
    {
        $this->client->loginUser($this->testMedic, 'main');

        $preturiRepo = $this->createMock(PretRepository::class);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $this->em->method('getRepository')
            ->with(PretRepository::class)
            ->willReturn($preturiRepo);

        $this->em->method('getRepository')
            ->with(Consultatie::class)
            ->willReturn($this->consRepo);

        $preturiRepo->method('getAllPreturi')->willReturn(['preturi']);
        $this->consRepo->method('getServiciiPacient')->willReturn(['preturiPacient']);

        $this->client->request('GET', '/pacienti/get_preturi', ['value' => 'filter']);

        $response = $this->client->getResponse();

        $preturi = json_decode($response->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->arrayHasKey($preturi['preturi']['servicii_preturi']);
        $this->assertStringContainsString('serviciiPacientInCabinet', $response->getContent());
    }

    /**
     * @covers \App\Controller\PacientController::deschideStergeConsultatii
     */
    public function testDeschideStergeConsultatiiFromPacienti()
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('ADD_EDIT', $this->isInstanceOf(Consultatie::class))
            ->willReturn(true);

        $this->em->method('getRepository')
            ->with(Consultatie::class)
            ->willReturn($this->consRepo);

        $this->consRepo->expects($this->once())
            ->method('deschideStergeConsultatii')
            ->with(10, 10, [])
            ->willReturn([]);

        $push = $this->createMock(PushNotificationService::class);
        $push->method('pushNotificationToMercure')->willReturn(true);

        $request = new Request([],['pacientId' => 10, 'programareId' => 10]);

        $response = $this->controllerMock->deschideStergeConsultatii($request, $push);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \App\Controller\PacientController::deschideStergeConsultatii
     */
    public function testDeschideStergeConsultatiiFromPacientiWithAccessDeniedException()
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('ADD_EDIT', $this->isInstanceOf(Consultatie::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->em->method('getRepository')
            ->with(Consultatie::class)
            ->willReturn($this->consRepo);

        $this->client->loginUser($this->testUser, 'main');

        $push = $this->createMock(PushNotificationService::class);
        $push->method('pushNotificationToMercure')->willReturn(true);

        $request = new Request([],['pacientId' => 10]);

        $this->controllerMock->deschideStergeConsultatii($request, $push);

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\PacientController::getServiciiPacient
     */
    public function testGetServiciiPacient()
    {
        $this->client->loginUser($this->testMedic, 'main');

        $this->em->method('getRepository')
            ->with(Consultatie::class)
            ->willReturn($this->consRepo);

        $this->consRepo->method('getServiciiPacient')->willReturn(['servicii']);

        $this->client->request(
            'GET',
            '/pacienti/get_servicii_pacient',
            [
                'pacient_id' => 1,
                'inchisa' => 0,
                'incasata' => 0
            ]
        );

        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \App\Controller\PacientController::incaseazaConsultatii
     */
    public function testIncaseasaConsultatie()
    {
        $this->client->loginUser($this->testMedic, 'main');

        $this->em->method('getRepository')
            ->with(Consultatie::class)
            ->willReturn($this->consRepo);

        $this->consRepo->method('incaseazaConsultatii')->willReturn(true);

        $request = new Request([],['pacientId' => 10]);

        $response = $this->controllerMock->incaseazaConsultatii($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \App\Controller\PacientController::inchideToateConsInvPacient
     */
    public function testInchideToateConsInvPacientWithDeniedAccess()
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('CLOSE_ALL', $this->isInstanceOf(Consultatie::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->em->method('getRepository')
            ->with(Consultatie::class)
            ->willReturn($this->consRepo);

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->inchideToateConsInvPacient(new Request([], ['id' => '10']));

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\PacientController::inchideToateConsInvPacient
     */
    public function testInchideToateConsInvPacient()
    {
        $user = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('ciprianmarta.cm@gmail.com');

        $this->authorizationChecker
            ->method('isGranted')
            ->with('CLOSE_ALL', $this->isInstanceOf(Consultatie::class))
            ->willReturn(true);

        $this->em->method('getRepository')
            ->with(Consultatie::class)
            ->willReturn($this->consRepo);

        $this->consRepo->method('inchideToateConsInvPacient')->willReturn(true);

        $this->client->loginUser($user, 'main');

        $this->controllerMock->method('getUser')->willReturn($user);

        $request = new Request([],['id' => 10]);

        $response = $this->controllerMock->inchideToateConsInvPacient($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \App\Controller\PacientController::verificaUnicitateCnp
     */
    public function testItCanCheckUnicitateCnpWithDuplicateExceptionThrown()
    {
        $cnp = '1234567890123';

        $this->controllerMock->method('getUser')->willReturn($this->testMedic);

        $this->client->loginUser($this->testMedic, 'main');

        $this->em->method('getRepository')
            ->with(Pacient::class)
            ->willReturn($this->pacientiRepo);

        $this->expectException(BadRequestHttpException::class);

        $this->pacientiRepo->method('findOneBy')->willReturn('1234567890123');

        $response = $this->controllerMock->verificaUnicitateCnp(new Request([], ['cnp' => $cnp]));

        $this->assertInstanceOf(BadRequestHttpException::class, $response);
    }

    /**
     * @covers \App\Controller\PacientController::verificaUnicitateCnp
     */
    public function testItCanCheckUnicitateCnp()
    {
        $this->controllerMock->method('getUser')->willReturn($this->testMedic);

        $this->client->loginUser($this->testMedic, 'main');

        $this->em->method('getRepository')
            ->with(Pacient::class)
            ->willReturn($this->pacientiRepo);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $this->pacientiRepo->method('findOneBy')->willReturn(null);

        $response = $this->controllerMock->verificaUnicitateCnp(new Request([], ['cnp' => '1234567890123']));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \App\Controller\PacientController::getPacientiByCnp
     */
    public function testGetPacientiByCnp()
    {
        $this->client->loginUser($this->testMedic, 'main');

        $this->client->request('GET', '/pacienti/get_pacienti_by_cnp', ['cnp' => '1200606125232']);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals($this->pacient->getId(), $data['pacienti'][0]['id']);
    }
}
