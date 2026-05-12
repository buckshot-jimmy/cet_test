<?php

namespace App\Tests\Controller;

use App\Controller\ProgramariController;
use App\Entity\Pacienti;
use App\Entity\Preturi;
use App\Entity\Programari;
use App\Entity\Role;
use App\Entity\User;
use App\Repository\ProgramariRepository;
use App\Repository\UserRepository;
use App\Services\AdminService;
use App\Services\EmailService;
use App\Validator\ProgramareConstraints;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProgramariControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->constraint = $this->createMock(ProgramareConstraints::class);
        $this->adminService = $this->createMock(AdminService::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->controller = new ProgramariController(
            $this->em,
            $this->translator,
            $this->validator,
            $this->constraint,
            $this->adminService,
            $this->authorizationChecker
        );

        $this->controllerMock = $this->getMockBuilder(ProgramariController::class)
            ->setConstructorArgs([$this->em, $this->translator, $this->validator, $this->constraint,
                $this->adminService, $this->authorizationChecker])
            ->onlyMethods(['getUser'])
            ->getMock();

        $this->testUser = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('test@test.ro');

        $this->repo = $this->createMock(ProgramariRepository::class);

        $this->testMedic = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('damian.popescu@mindreset.ro');

        $this->dbEm = $this->getContainer()->get(EntityManagerInterface::class);
        $this->dbEm->getConnection()->beginTransaction();

        $this->pacient = new Pacienti();
        $this->pacient->setNume('Pacient_Test');
        $this->pacient->setPrenume('Prenume_Test');
        $this->pacient->setCnp('2222222222222');
        $this->pacient->setTelefon('0711111111');
        $this->pacient->setAdresa('Addr');
        $this->pacient->setTara('Romania');
        $this->pacient->setCi('XB');
        $this->pacient->setCiEliberat('City');
        $this->pacient->setSters(false);
        $this->pacient->setDataInreg(new \DateTime());
        $this->dbEm->persist($this->pacient);

        $this->pret = $this->dbEm->getRepository(Preturi::class)->findAll()[0];

        $this->programare = new Programari();
        $this->programare->setPacient($this->pacient);
        $this->programare->setPret($this->pret);
        $data = \DateTime::createFromFormat('d-m-Y', '01-01-2026');
        $dataFormatata = $data->format('Y-m-d');
        $this->programare->setData(new \DateTime($dataFormatata));
        $this->programare->setOra(\DateTime::createFromFormat('H:i', '09:00'));
        $this->programare->setAnulata(false);
        $this->programare->setAdaugataDe($this->testMedic);
        $this->dbEm->persist($this->programare);

        $this->dbEm->flush();
        $this->dbEm->clear();

        $this->pacient = $this->dbEm->getRepository(Pacienti::class)->find($this->pacient->getId());
        $this->pret = $this->dbEm->getRepository(Preturi::class)->find($this->pret->getId());
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
     * @covers \App\Controller\ProgramariController::__construct
     */
    public function testCanBuildController()
    {
        $controller = new ProgramariController(
            $this->em, $this->translator, $this->validator, $this->constraint, $this->adminService,
            $this->authorizationChecker
        );

        $this->assertInstanceOf(ProgramariController::class, $controller);
    }

    /**
     * @covers \App\Controller\ProgramariController::programari
     */
    public function testRendersProgramariTemplateWithCorrectData(): void
    {
        $mediciRepo = $this->createMock(UserRepository::class);
        $mediciRepo->method('getAllMedici')->willReturn(['medici']);

        $this->em->method('getRepository')->willReturnMap([
            [User::class, $mediciRepo],
        ]);

        $this->client->loginUser($this->testMedic, 'main');

        $this->client->request('GET', '/programari');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('Programari pacienti',
            $response->getContent());
    }

    /**
     * @covers \App\Controller\ProgramariController::list
     */
    public function testItCanReturnListAllProgramari(): void
    {
        $this->repo->method('getAllProgramari')->with(['filter'])->willReturn(['programari']);

        $this->client->loginUser($this->testMedic, 'main');

        $roleMock = $this->createMock(Role::class);
        $userMock = $this->createMock(User::class);
        $userMock->method('getRole')
            ->willReturn($roleMock);
        $roleMock->method('getDenumire')
            ->willReturn(ProgramariController::ROL_MEDIC);

        $userMock->method('getId')->willReturn($this->testMedic->getId());

        $this->assertEquals($userMock->getId(), $this->testMedic->getId());

        $this->client->request('GET', '/programari/list_programari');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('recordsFiltered', $response->getContent());
    }

    /**
     * @covers \App\Controller\ProgramariController::adaugaProgramare
     */
    public function testEditProgramareWithAccessDenied(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('ADD', $this->isInstanceOf(Programari::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->adaugaProgramare(new Request());

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\ProgramariController::adaugaProgramare
     */
    public function testProgramariCanShowValidationErrorsFromEdit(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('EDIT', $this->isInstanceOf(Programari::class))
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

        $this->adminService->expects($this->once())
            ->method('buildValidationErrors')
            ->with($violations)
            ->willReturn('Some validation error');

        $this->em->method('getRepository')
            ->with(Programari::class)
            ->willReturn($this->repo);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => '10'])
            ->willReturn(new Programari());

        $this->client->loginUser($this->testMedic, 'main');

        $response = $this->controller->adaugaProgramare(new Request([], ['form' => 'programare_id=10']));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $data['status']);
        $this->assertSame('Failed operation. Some validation error', $data['message']);
    }

    /**
     * @covers \App\Controller\ProgramariController::adaugaProgramare
     */
    public function testItCanEditProgramare()
    {
        $this->client->loginUser($this->testMedic, 'main');

        $this->authorizationChecker->method('isGranted')->willReturn(true);

        $this->em->method('getRepository')
            ->with(Programari::class)
            ->willReturn($this->repo);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => '10'])
            ->willReturn(new Programari());

        $programare = [
            'programare_id' => '10',
            'programare_pacient' => '10',
            'programare_pret_serviciu' => '10',
            'programare_data' => '2026-01-01',
            'programare_ora' => '09:00',
        ];

        $form = "programare_id=10&programare_pacient=10&programare_pret_serviciu=10&programare_data=2026-01-01&" .
                "programare_ora=09:00";

        $this->repo->method('saveProgramare')->with($programare, $this->testMedic);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $this->controllerMock->method('getUser')->willReturn($this->testMedic);

        $request = new Request([], ['form' => $form]);

        $response = $this->controllerMock->adaugaProgramare($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
        $this->assertEquals('Successful operation', $data['message']);
    }

    /**
     * @covers \App\Controller\ProgramariController::getProgramare
     */
    public function testItCanGetProgramare()
    {
        $this->client->loginUser($this->testMedic, 'main');

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $this->em->method('getRepository')
            ->with(Programari::class)
            ->willReturn($this->repo);

        $this->client->request('GET', '/programari/get_programare', ['id' => $this->programare->getId()]);

        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('data', $response->getContent());
        $this->assertStringContainsString('ora', $response->getContent());
    }

    /**
     * @covers \App\Controller\ProgramariController::anuleazaProgramare
     */
    public function testProgramareAccessDeniedForCancel(): void
    {
        $this->em->method('getRepository')
            ->with(Programari::class)
            ->willReturn($this->repo);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => '10'])
            ->willReturn(new Programari());

        $this->authorizationChecker
            ->method('isGranted')
            ->with('CANCEL', $this->isInstanceOf(Programari::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->anuleazaProgramare(new Request([], ['id' => 10]));

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\ProgramariController::anuleazaProgramare
     */
    public function testPacientiCanBeDeleted(): void
    {
        $this->em->method('getRepository')
            ->with(Programari::class)
            ->willReturn($this->repo);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => '123'])
            ->willReturn(new Programari());

        $this->authorizationChecker
            ->method('isGranted')
            ->with('CANCEL', $this->isInstanceOf(Programari::class))
            ->willReturn(true);

        $this->repo->expects($this->once())
            ->method('cancelProgramare')
            ->with(123);

        $this->em->method('getRepository')
            ->with(Programari::class)
            ->willReturn($this->repo);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $request = new Request([], ['id' => 123]);

        $response = $this->controller->anuleazaProgramare($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
        $this->assertEquals('Successful operation', $data['message']);
    }

    /**
     * @covers \App\Controller\ProgramariController::pacientAnuleazaProgramare
     */
    public function testPacientAnuleazaProgramarePageLoads(): void
    {
        $this->client->request('GET', '/programari/pacient_anuleaza_programare',
            ['programareId' => $this->programare->getId()]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }


    /**
     * @covers \App\Controller\ProgramariController::pacientAnuleazaProgramare
     */
    public function testSubmitPacientAnuleazaProgramareInvalidToken(): void
    {
        $request = new Request(
            [],
            [
                'programareId' => 10,
                'patient_cancel_appointment_form' => [
                    '_token' => 'valid-token'
                ]
            ],
            [],
            [],
            [],
            [
                'HTTP_REFERER' => '/some-page'
            ]
        );

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);

        $controller = $this->getMockBuilder(ProgramariController::class)
            ->setConstructorArgs([
                $this->em,
                $this->translator,
                $this->validator,
                $this->constraint,
                $this->adminService,
                $this->authorizationChecker
            ])
            ->onlyMethods(['createForm', 'isCsrfTokenValid', 'addFlash', 'redirect'])
            ->getMock();

        $controller->method('createForm')->willReturn($form);
        $controller->method('isCsrfTokenValid')->willReturn(false);

        $controller->expects($this->once())->method('addFlash');

        $controller->method('redirect')
            ->willReturn(new RedirectResponse('/some-page', 302));

        $response = $controller->pacientAnuleazaProgramare($request);

        $this->assertEquals(302, $response->getStatusCode());
    }

    /**
     * @covers \App\Controller\ProgramariController::pacientAnuleazaProgramare
     */
    public function testPacientAnuleazaProgramareSuccessValidToken()
    {
        $request = new Request(
            [],
            [
                'programareId' => 10,
                'patient_cancel_appointment_form' => [
                    '_token' => 'valid-token'
                ]
            ],
            [],
            [],
            [],
            [
                'HTTP_REFERER' => '/some-page'
            ]
        );

        $repo = $this->createMock(ProgramariRepository::class);
        $repo->expects($this->once())
            ->method('cancelProgramare')
            ->with(10);

        $this->em->method('getRepository')->willReturn($repo);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);

        $controller = $this->getMockBuilder(ProgramariController::class)
            ->setConstructorArgs([
                $this->em,
                $this->translator,
                $this->validator,
                $this->constraint,
                $this->adminService,
                $this->authorizationChecker
            ])
            ->onlyMethods(['createForm', 'isCsrfTokenValid', 'addFlash', 'redirect'])
            ->getMock();

        $controller->method('createForm')->willReturn($form);
        $controller->method('isCsrfTokenValid')->willReturn(true);

        $controller->expects($this->once())
            ->method('addFlash')
            ->with('success', $this->anything());

        $controller->method('redirect')
            ->willReturn(new RedirectResponse('/some-page', 302));

        $response = $controller->pacientAnuleazaProgramare($request);

        $this->assertEquals(302, $response->getStatusCode());
    }

    /**
     * @covers \App\Controller\ProgramariController::verificaDisponibilitate
     */
    public function testItCanCheckAvailabilityWithNotFreeException()
    {
        $this->client->request(
            'GET',
            '/programari/check_availability',
            [
                'form' => 'programare_medic=' . $this->testMedic->getId() .
                    '&programare_data=01-01-2026&programare_ora=09:00'
            ]
        );

        $response = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(500);

        $this->assertStringContainsString('Ora indisponibila pentru medicul selectat',$response->getContent());
    }

    /**
     * @covers \App\Controller\ProgramariController::verificaDisponibilitate
     */
    public function testItCanCheckAvailabilityWithFree()
    {
        $this->client->request(
            'GET',
            '/programari/check_availability',
            [
                'form' => 'programare_medic=' . $this->testMedic->getId() .
                    '&programare_data=01-01-2026&programare_ora=08:00'
            ]
        );

        $response = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(200);

        $this->assertStringContainsString('Operatiune reusita',$response->getContent());
    }

    /**
     * @covers \App\Controller\ProgramariController::trimiteEmailProgramare
     */
    public function testItCanSendEmailFail()
    {
        $emailService = $this->createMock(EmailService::class);
        $emailService->method('sendEmail')
            ->willReturn([
                'status' => 500,
                'message' => 'Email failed'
            ]);

        self::getContainer()->set(EmailService::class, $emailService);

        $this->client->loginUser($this->testMedic, 'main');

        $this->client->request('POST', '/programari/trimite_email_programare', [
            'id' => $this->programare->getId()
        ]);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame(500, $data['status']);
        $this->assertSame('Email failed', $data['message']);
    }

    /**
     * @covers \App\Controller\ProgramariController::trimiteEmailProgramare
     */
    public function testItCanSendEmailSuccess()
    {
        $emailService = $this->createMock(EmailService::class);
        $emailService->method('sendEmail')
            ->willReturn([
                'status' => 200,
                'message' => 'Email sent successfully'
            ]);

        self::getContainer()->set(EmailService::class, $emailService);

        $this->client->loginUser($this->testMedic, 'main');

        $this->client->request('POST', '/programari/trimite_email_programare', [
            'id' => $this->programare->getId()
        ]);

        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();

        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['status']);
        $this->assertSame('Email sent successfully', $data['message']);
    }
}
