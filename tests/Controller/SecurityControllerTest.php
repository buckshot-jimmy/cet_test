<?php

namespace App\Tests\Controller;

use App\Controller\SecurityController;
use App\Entity\MesajAdmin;
use App\Entity\ResetPasswordRequest;
use App\Entity\Role;
use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\MesajAdminRepository;
use App\Repository\UserRepository;
use App\Services\AdminService;
use App\Services\EmailService;
use App\Services\NomenclatoareService;
use App\Services\AuthService;
use App\Validator\UserConstraints;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->container = static::getContainer();

        $this->entityManager = $this->container->get(EntityManagerInterface::class);

        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->userConstraint = $this->createMock(UserConstraints::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->adminService = $this->createMock(AdminService::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->email = $this->createMock(EmailService::class);
        $this->mockHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->passwordHasher = $this->container->get(UserPasswordHasherInterface::class);

        $this->controller = new SecurityController(
            $this->em,
            $this->requestStack,
            $this->adminService,
            $this->translator,
            $this->userConstraint,
            $this->validator,
        );

        $this->testUser = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('ciprianmarta.cm@gmail.com');

        $this->testMedic = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('damian.popescu@mindreset.ro');

        $this->controllerMock = $this->getMockBuilder(SecurityController::class)
            ->setConstructorArgs([
                $this->em,
                $this->requestStack,
                $this->adminService,
                $this->translator,
                $this->userConstraint,
                $this->validator,
            ])
            ->onlyMethods(['getUser', 'render', 'createForm', 'addFlash', 'redirectToRoute', 'isCsrfTokenValid'])
            ->getMock();
        
        $this->authService = $this->createMock(AuthService::class);
        $this->sessionMock = new Session(new MockArraySessionStorage());
        $this->userMock = $this->createMock(User::class);
        $this->roleMock = $this->createMock(Role::class);
        $this->passwordField = $this->createMock(FormInterface::class);
        $this->confirmPasswordField = $this->createMock(FormInterface::class);
        $this->formMock = $this->createMock(FormInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $this->testUser->getEmail()]);

        $user->setPassword(
            $this->passwordHasher->hashPassword($user, 'Admin_1')
        );

        $this->entityManager->flush();
    }

    /**
     * @covers \App\Controller\SecurityController::__construct
     */
    public function testCanBuildController()
    {
        $controller = new SecurityController(
            $this->em,
            $this->requestStack,
            $this->adminService,
            $this->translator,
            $this->userConstraint,
            $this->validator
        );

        $this->assertInstanceOf(SecurityController::class, $controller);
    }

    /**
     * @covers \App\Controller\SecurityController::login
     */
    public function testRenderLoginPage()
    {
        $this->client->request('GET', '/');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertStringContainsString('Bun venit!', $response->getContent());
    }

    /**
     * @covers \App\Controller\SecurityController::login
     */
    public function testRenderLoginPageWithLastAuthenticationError()
    {
        $authenticationUtils = $this->createMock(AuthenticationUtils::class);
        $authenticationUtils
            ->method('getLastAuthenticationError')
            ->willReturn(null);

        $authenticationUtils
            ->method('getLastUsername')
            ->willReturn('test');

        $this->controllerMock->expects($this->once())
            ->method('render')
            ->with('@security/login.html.twig', [
                'last_username' => 'test',
                'error' => null
            ])
            ->willReturn(new Response('Bun venit!', Response::HTTP_OK));

        $response = $this->controllerMock->login($authenticationUtils);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Bun venit!', $response->getContent());
    }

    /**
     * @covers \App\Controller\SecurityController::login
     */
    public function testLoginShowsErrorWithWrongCredentials(): void
    {
        $crawler = $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Login')->form([
            'username' => 'test',
            'password' => 'wrong_password',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/');

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    /**
     * @covers \App\Controller\SecurityController::logout
     */
    public function testLogoutIsInterceptedAndRedirects(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This method can be blank - it will be intercepted by the logout key');

        $this->controller->logout(new Request());
    }

    /**
     * @covers \App\Controller\SecurityController::dashboard
     */
    public function testDashboardRendersAndSetsSessionInfo(): void
    {
        $this->requestStack
            ->method('getSession')
            ->willReturn($this->sessionMock);

        $nomenclatoareMed = [
            'roluri' => ['roluri'],
            'specialitati' => ['specialitati'],
            'titulaturi' => ['titulaturi'],
        ];

        $userData = ['id' => 123];
        $valoriServicii = ['vs' => 10];
        $totaluriPacienti = [
            'valoriCabinet' => ['vc' => 1],
            'consultatiiPacientiMedic' => ['vm' => 2],
        ];

        $this->adminService
            ->expects($this->once())
            ->method('getNomenclatoareMedicale')
            ->willReturn($nomenclatoareMed);

        $this->adminService
            ->expects($this->once())
            ->method('getLoggedUserData')
            ->with($this->testMedic)
            ->willReturn($userData);

        $this->adminService
            ->expects($this->once())
            ->method('getValoriGrafice')
            ->with($userData)
            ->willReturn($valoriServicii);

        $this->adminService
            ->expects($this->once())
            ->method('getTotaluriPacienti')
            ->with($userData)
            ->willReturn($totaluriPacienti);

        $this->adminService
            ->expects($this->once())
            ->method('setSessionInfo')
            ->with(
                $this->sessionMock,
                [
                    'loggedUserData' => $userData,
                    'roluri' => $nomenclatoareMed['roluri'],
                    'specialitati' => $nomenclatoareMed['specialitati'],
                    'titulaturi' => $nomenclatoareMed['titulaturi'],
                    'informare' => '',
                ]
            );

        $service = $this->createMock(NomenclatoareService::class);
        $service
            ->method('getLunileAnului')
            ->willReturn(['luni']);

        $this->controllerMock->method('getUser')->willReturn($this->testMedic);

        $this->controllerMock
            ->expects($this->once())
            ->method('render')
            ->with(
                '@templates/main/index.html.twig',
                $this->callback(function (array $vars) use ($valoriServicii, $totaluriPacienti): bool {
                    return $vars['valoriCabinet'] === $totaluriPacienti['valoriCabinet']
                        && $vars['consultatiiPacientiMedic'] === $totaluriPacienti['consultatiiPacientiMedic']
                        && $vars['valoriMedic'] === $valoriServicii
                        && array_key_exists('luna', $vars);
                })
            )
            ->willReturn(new Response('OK', Response::HTTP_OK));

        $mesajAdminRepo = $this->createMock(MesajAdminRepository::class);
        $this->em->method('getRepository')
            ->with(MesajAdmin::class)
            ->willReturn($mesajAdminRepo);
        $mesajAdminRepo->method('getMesajAdmin')->willReturn('');

        $response = $this->controllerMock->dashboard($service);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('OK', $response->getContent());
    }

    /**
     * @covers \App\Controller\SecurityController::salveazaInformare
     */
    public function testSalveazaInformare()
    {
        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $mesajAdminRepo = $this->createMock(MesajAdminRepository::class);
        $this->em->method('getRepository')
            ->with(MesajAdmin::class)
            ->willReturn($mesajAdminRepo);
        $mesajAdminRepo->method('saveMesaj')->with('mesaj info', true);

        $response = $this->controller->salveazaInformare(new Request([], ['mesaj' => 'mesaj info', 'activ' => 1]));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \App\Controller\SecurityController::getInformare
     */
    public function testCanGetInformare()
    {
        $mesajAdminRepo = $this->createMock(MesajAdminRepository::class);
        $this->em->method('getRepository')
            ->with(MesajAdmin::class)
            ->willReturn($mesajAdminRepo);

        $response = $this->controller->getInformare();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \App\Controller\SecurityController::salveazaNouaParola
     */
    public function testCanSaveNewPasswordWithException()
    {
        $this->controllerMock->method('getUser')->willReturn($this->testMedic);

        $this->client->loginUser($this->testMedic, 'main');

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $this->expectException(\Exception::class);

        $response =
            $this->controllerMock->salveazaNouaParola(new Request([], ['parola' => 'p', 'parolaConfirmata' => 'x']));

        $this->assertInstanceOf(\Exception::class, $response);
    }

    /**
     * @covers \App\Controller\SecurityController::salveazaNouaParola
     */
    public function testCanSaveNewPasswordWithValidationErrors()
    {
        $this->controllerMock->method('getUser')->willReturn($this->testMedic);

        $this->client->loginUser($this->testMedic, 'main');

        $this->translator->method('trans')
            ->with('Failed operation')
            ->willReturn('Failed operation.');

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

        $this->adminService->expects($this->once())
            ->method('buildValidationErrors')
            ->with($violations)
            ->willReturn('Some validation error');

        $response =
            $this->controllerMock->salveazaNouaParola(new Request([], ['parola' => 'p', 'parolaConfirmata' => 'p']));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $data['status']);
        $this->assertSame('Failed operation. Some validation error', $data['message']);
    }

    /**
     * @covers \App\Controller\SecurityController::salveazaNouaParola
     */
    public function testCanSaveNewPassword()
    {
        $this->controllerMock->method('getUser')->willReturn($this->testMedic);

        $this->client->loginUser($this->testMedic, 'main');

        $this->translator->method('trans')
            ->with('Password changed')
            ->willReturn('Password changed');

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList([]));

        $userMock = $this->createMock(UserRepository::class);
        $this->em->method('getRepository')->with(User::class)->willReturn($userMock);
        $userMock->method('saveFirstTimeNewPassword')
            ->with(['password' => 'p', 'passConf' => 'p', 'user_id' => $this->testMedic->getId()]);

        $response =
            $this->controllerMock->salveazaNouaParola(new Request([], ['parola' => 'p', 'parolaConfirmata' => 'p']));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
        $this->assertEquals('Password changed', $data['message']);
    }

    /**
     * @covers \App\Controller\SecurityController::forgotPasswordEmail
     */
    public function testForgotPasswordEmailPageLoads(): void
    {
        $this->client->request('GET', '/security/forgot_password_email');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    /**
     * @covers \App\Controller\SecurityController::forgotPasswordEmail
     */
    public function testForgotPasswordEmailWithNonExistentEmailAddress(): void
    {
        $crawler = $this->client->request('GET', '/security/forgot_password_email');

        $form = $crawler->selectButton('Trimite')->form([
            'reset_password_request_form[email_forgot_password]' => 'none@email.com',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/security/forgot_password_email');

        $this->client->followRedirect();

        $this->assertSelectorTextContains('.alert-danger', 'Utilizatorul nu a fost gasit.');
    }

    /**
     * @covers \App\Controller\SecurityController::forgotPasswordEmail
     */
    public function testForgotPasswordEmailWithInvalidForm(): void
    {
        $crawler = $this->client->request('GET', '/security/forgot_password_email');

        $form = $crawler->selectButton('Trimite')->form([
            'reset_password_request_form[email_forgot_password]' => 'invalid@email',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/security/forgot_password_email');

        $this->client->followRedirect();

        $this->assertSelectorTextContains('.alert-danger', 'Introduceti o adresa de email valida.');

        $this->removeResetToken();
    }

    /**
     * @covers \App\Controller\SecurityController::forgotPasswordEmail
     */
    public function testSubmitForgotPasswordEmail(): void
    {
        $crawler = $this->client->request('GET', '/security/forgot_password_email');

        $form = $crawler->selectButton('Trimite')->form([
            'reset_password_request_form[email_forgot_password]' => $this->testUser->getEmail(),
        ]);

        $this->email->method('sendEmail')->willReturn(['message' => 'Email sent', 'status' => 200]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/');

        $this->client->followRedirect();

        $this->assertSelectorTextContains('.alert-success', 'Email-ul a fost trimis.');

        $this->removeResetToken();
    }

    /**
     * @covers \App\Controller\SecurityController::forgotPasswordEmail
     */
    public function testSubmitResendForgotPasswordEmail(): void
    {
        $crawler = $this->client->request('GET', '/security/forgot_password_email');

        $form = $crawler->selectButton('Retrimite')->form([
            'reset_password_request_form[email_forgot_password]' => $this->testUser->getEmail(),
        ]);

        $this->email->method('sendEmail')->willReturn(
            new Response(json_encode(['message' => 'Email sent', 'token' => 'token'])));

        $this->client->submit($form);

        $this->assertResponseRedirects('/');

        $this->client->followRedirect();

        $this->assertSelectorTextContains('.alert-success', 'Email-ul a fost trimis.');

        $this->removeResetToken();
    }

    /**
     * @covers \App\Controller\SecurityController::forgotPasswordEmail
     */
    public function testSubmitForgotPasswordEmailExceptionAlreadyRequestedReset(): void
    {
        $form = $this->createMock(FormInterface::class);
        $emailField = $this->createMock(FormInterface::class);

        $this->controllerMock->expects($this->once())
            ->method('createForm')
            ->with(ResetPasswordRequestFormType::class)
            ->willReturn($form);

        $form->expects($this->once())->method('handleRequest')->willReturnSelf();
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $form->expects($this->once())->method('get')
            ->with('email_forgot_password')
            ->willReturn($emailField);

        $emailField->expects($this->once())->method('getData')->willReturn('john@example.com');

        $repo = $this->createMock(ObjectRepository::class);
        $user = $this->createMock(User::class);

        $this->em->expects($this->once())->method('getRepository')
            ->with(User::class)->willReturn($repo);
        $repo->expects($this->once())->method('findOneBy')->with(['email' => 'john@example.com'])
            ->willReturn($user);

        $this->authService->expects($this->once())
            ->method('generateResetToken')
            ->with($user)
            ->willReturn(null);

        $emailService = $this->createMock(EmailService::class);
        $emailService->expects($this->never())->method('sendEmail');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('You have already requested a password reset')
            ->willReturn('Ai solicitat deja resetarea parolei.');

        $this->controllerMock->expects($this->once())
            ->method('addFlash')
            ->with('danger', 'Ai solicitat deja resetarea parolei.');

        $this->controllerMock->expects($this->once())
            ->method('redirectToRoute')
            ->with('app_login')
            ->willReturn($redirect = new RedirectResponse('/login'));
        $response = $this->controllerMock->forgotPasswordEmail(
            Request::create('/security/forgot_password_email', 'POST'),
            $emailService,
            $this->authService,
        );

        $this->assertSame($redirect, $response);
        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \App\Controller\SecurityController::forgotPasswordReset
     */
    public function testRedirectWhenTokenProvided(): void
    {
        $this->client->request('GET', '/security/forgot_password_reset?token=123abc');

        $session = $this->client->getRequest()->getSession();
        $session->set('reset-password-token', '123abc');
        $session->save();

        $this->assertResponseRedirects('/security/forgot_password_reset');

        $this->assertEquals('123abc', $session->get('reset-password-token'));

        $this->removeResetToken();
    }

    /**
     * @covers \App\Controller\SecurityController::forgotPasswordReset
     */
    public function testRedirectWhenNoToken(): void
    {
        $this->client->request('GET', '/security/forgot_password_reset');

        $session = $this->client->getRequest()->getSession();
        $session->set('reset-password-token', null);
        $session->save();

        $this->assertResponseRedirects('/security/forgot_password_email');

        $this->assertNotEmpty($session->getFlashBag()->get('danger'));

        $this->removeResetToken();
    }

    /**
     * @covers \App\Controller\SecurityController::forgotPasswordReset
     */
    public function testSubmitValidForm(): void
    {
        $this->removeResetToken();

        $crawlerEmail = $this->client->request('GET', '/security/forgot_password_email');

        $this->client->enableProfiler();

        $formEmail = $crawlerEmail->selectButton('Trimite email')->form([
            'reset_password_request_form[email_forgot_password]' => $this->testUser->getEmail(),
        ]);
        $this->client->submit($formEmail);

        $mailerCollector = $this->client->getProfile()->getCollector('mailer');
        $messages = $mailerCollector->getEvents()->getMessages();

        $this->assertNotEmpty($messages, 'The password reset email was not sent.');

        $email = $messages[0];
        $html = $email->getHtmlBody();

        preg_match('/token=([A-Za-z0-9]+)/', $html, $matches);
        $token = $matches[1];

        $session = $this->client->getRequest()->getSession();
        $session->set('reset-password-token', $token);
        $session->save();

        $crawler = $this->client->request('GET', '/security/forgot_password_reset');

        $random = random_int(100000, 999999);
        $form = $crawler->selectButton('Confirma')->form([
            'change_password_form[new_password]' => 'Admin_1' . $random,
            'change_password_form[confirm_new_password]' => 'Admin_1' . $random,
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects('/');

        $this->assertNotEmpty($session->getFlashBag()->get('success'));

        $this->removeResetToken();
    }

    /**
     * @covers \App\Controller\SecurityController::forgotPasswordReset
     */
    public function testSubmitPasswordsMismatch(): void
    {
        $this->sessionMock->start();
        $this->sessionMock->set('reset-password-token', 'valid_token');

        $request = Request::create('/security/forgot_password_reset', 'POST');
        $request->setSession($this->sessionMock);

        $this->authService->expects($this->once())
            ->method('validateResetTokenAndGetUser')
            ->with('valid_token')
            ->willReturn($this->userMock);

        $this->authService->expects($this->never())->method('processRequestReset');

        $this->controllerMock->expects($this->once())
            ->method('createForm')
            ->with(ChangePasswordFormType::class)
            ->willReturn($this->formMock);

        $this->formMock->expects($this->once())->method('handleRequest')->with($request)->willReturnSelf();
        $this->formMock->expects($this->once())->method('isSubmitted')->willReturn(true);
        $this->formMock->expects($this->exactly(2))
            ->method('GET')
            ->willReturnMap([
                ['new_password', $this->passwordField],
                ['confirm_new_password', $this->confirmPasswordField],
            ]);
        $this->passwordField->expects($this->once())->method('getData')->willReturn('Admin_3');
        $this->confirmPasswordField->expects($this->once())->method('getData')->willReturn('Admin_4');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('Passwords are not equal')
            ->willReturn('Parolele nu se potrivesc.');

        $this->controllerMock->expects($this->once())
            ->method('addFlash')
            ->with('danger', 'Parolele nu se potrivesc.');

        $this->formMock->expects($this->once())->method('createView')->willReturn(new FormView());

        $this->controllerMock->expects($this->once())
            ->method('render')
            ->with('reset_password/forgot_password_reset.html.twig', $this->arrayHasKey('resetForm'))
            ->willReturn(new Response('Parolele nu se potrivesc.', Response::HTTP_OK));

        $response = $this->controllerMock->forgotPasswordReset($request, $this->authService);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('Parolele nu se potrivesc.', $response->getContent());
    }

    /**
     * @covers \App\Controller\SecurityController::forgotPasswordReset
     */
    public function testSubmitPasswordsWithValidationErrors(): void
    {
        $this->sessionMock->start();
        $this->sessionMock->set('reset-password-token', 'valid_token');

        $request = Request::create('/security/forgot_password_reset', 'POST');
        $request->setSession($this->sessionMock);

        $this->roleMock->expects($this->once())->method('getDenumire')->willReturn('ADMIN');
        $this->userMock->expects($this->exactly(2))->method('getId')->willReturn(7);
        $this->userMock->expects($this->once())->method('getRole')->willReturn($this->roleMock);

        $this->authService->expects($this->once())
            ->method('validateResetTokenAndGetUser')
            ->with('valid_token')
            ->willReturn($this->userMock);

        $this->authService->expects($this->never())->method('processRequestReset');

        $this->controllerMock->expects($this->once())
            ->method('createForm')
            ->with(ChangePasswordFormType::class)
            ->willReturn($this->formMock);

        $this->formMock->expects($this->once())->method('handleRequest')->with($request)->willReturnSelf();
        $this->formMock->expects($this->once())->method('isSubmitted')->willReturn(true);
        $this->formMock->expects($this->exactly(2))
            ->method('GET')
            ->willReturnMap([
                ['new_password', $this->passwordField],
                ['confirm_new_password', $this->confirmPasswordField],
            ]);
        $this->passwordField->expects($this->once())->method('getData')->willReturn('12');
        $this->confirmPasswordField->expects($this->once())->method('getData')->willReturn('12');

        $errors = new ConstraintViolationList([
            new ConstraintViolation('Parola este prea slaba.', '',
                [], null, 'password', '12'),
        ]);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with([
                'loggedUserId' => 7,
                'editUserId' => 7,
                'role' => 'ADMIN',
                'password' => '12',
            ], $this->userConstraint)
            ->willReturn($errors);

        $this->adminService->expects($this->once())
            ->method('buildValidationErrors')
            ->with($errors)
            ->willReturn('Parola este prea slaba.');

        $this->controllerMock->expects($this->once())
            ->method('addFlash')
            ->with('danger', 'Parola este prea slaba.');

        $this->formMock->expects($this->once())->method('createView')->willReturn(new FormView());

        $this->controllerMock->expects($this->once())
            ->method('render')
            ->with('reset_password/forgot_password_reset.html.twig', $this->arrayHasKey('resetForm'))
            ->willReturn(new Response('Parola este prea slaba.', Response::HTTP_OK));

        $response = $this->controllerMock->forgotPasswordReset($request, $this->authService);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('Parola este prea slaba.', $response->getContent());
    }

    /**
     * @covers \App\Controller\SecurityController::forgotPasswordReset
     */
    public function testSubmitPasswordsWithInvalidToken(): void
    {
        $this->sessionMock->start();
        $this->sessionMock->set('reset-password-token', 'invalid_token');

        $request = Request::create('/security/forgot_password_reset');
        $request->setSession($this->sessionMock);

        $this->authService->expects($this->once())
            ->method('validateResetTokenAndGetUser')
            ->with('invalid_token')
            ->willReturn(null);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('Invalid reset link')
            ->willReturn('Link-ul pentru resetare este invalid.');

        $this->controllerMock->expects($this->once())
            ->method('addFlash')
            ->with('danger', 'Link-ul pentru resetare este invalid.');

        $this->controllerMock->expects($this->once())
            ->method('redirectToRoute')
            ->with('app_login')
            ->willReturn(new RedirectResponse('/'));

        $response = $this->controllerMock->forgotPasswordReset($request, $this->authService);

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertNull($this->sessionMock->get('reset-password-token'));
    }

    /**
     * @covers \App\Controller\SecurityController::resendTwoFactorCode
     */
    public function testResendTwoFactorCodeWithInvalidCsrfToken(): void
    {
        $request = Request::create('/2fa/resend', 'POST', ['_token' => 'invalid']);

        $this->controllerMock->expects($this->once())
            ->method('isCsrfTokenValid')
            ->with('2fa_resend', 'invalid')
            ->willReturn(false);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('Invalid 2FA request')
            ->willReturn('Invalid 2FA request');

        $this->controllerMock->expects($this->once())
            ->method('addFlash')
            ->with('danger', 'Invalid 2FA request');

        $this->controllerMock->expects($this->once())
            ->method('redirectToRoute')
            ->with('2fa_login')
            ->willReturn(new RedirectResponse('/2fa'));

        $this->authService->expects($this->never())->method('resendTwoFactorCode');

        $response = $this->controllerMock->resendTwoFactorCode($request, $this->authService);

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \App\Controller\SecurityController::resendTwoFactorCode
     */
    public function testResendTwoFactorCodeWithInvalidUserType(): void
    {
        $request = Request::create('/2fa/resend', 'POST', ['_token' => 'valid']);

        $this->controllerMock->expects($this->once())
            ->method('isCsrfTokenValid')
            ->with('2fa_resend', 'valid')
            ->willReturn(true);

        $this->controllerMock->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('2FA code is invalid. Please login again or request a new code.')
            ->willReturn('2FA code is invalid. Please login again or request a new code.');

        $this->controllerMock->expects($this->once())
            ->method('addFlash')
            ->with('danger', '2FA code is invalid. Please login again or request a new code.');

        $this->controllerMock->expects($this->once())
            ->method('redirectToRoute')
            ->with('app_login')
            ->willReturn(new RedirectResponse('/'));

        $service = $this->createMock(AuthService::class);
        $service->expects($this->never())->method('resendTwoFactorCode');

        $response = $this->controllerMock->resendTwoFactorCode($request, $service);

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \App\Controller\SecurityController::resendTwoFactorCode
     */
    public function testResendTwoFactorCodeWithServiceFailure(): void
    {
        $request = Request::create('/2fa/resend', 'POST', ['_token' => 'valid']);
        $user = $this->testMedic;

        $this->controllerMock->expects($this->once())
            ->method('isCsrfTokenValid')
            ->with('2fa_resend', 'valid')
            ->willReturn(true);

        $this->controllerMock->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->authService->expects($this->once())
            ->method('resendTwoFactorCode')
            ->with($user)
            ->willReturn(false);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('Could not send a new 2FA token. Please try again')
            ->willReturn('Could not send a new 2FA token. Please try again');

        $this->controllerMock->expects($this->once())
            ->method('addFlash')
            ->with('danger', 'Could not send a new 2FA token. Please try again');

        $this->controllerMock->expects($this->once())
            ->method('redirectToRoute')
            ->with('2fa_login')
            ->willReturn(new RedirectResponse('/2fa'));

        $response = $this->controllerMock->resendTwoFactorCode($request, $this->authService);

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \App\Controller\SecurityController::resendTwoFactorCode
     */
    public function testResendTwoFactorCodeWithServiceSuccess(): void
    {
        $request = Request::create('/2fa/resend', 'POST', ['_token' => 'valid']);
        $user = $this->testMedic;

        $this->controllerMock->expects($this->once())
            ->method('isCsrfTokenValid')
            ->with('2fa_resend', 'valid')
            ->willReturn(true);

        $this->controllerMock->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->authService->expects($this->once())
            ->method('resendTwoFactorCode')
            ->with($user)
            ->willReturn(true);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('A new 2FA token was sent to your email')
            ->willReturn('A new 2FA token was sent to your email');

        $this->controllerMock->expects($this->once())
            ->method('addFlash')
            ->with('success', 'A new 2FA token was sent to your email');

        $this->controllerMock->expects($this->once())
            ->method('redirectToRoute')
            ->with('2fa_login')
            ->willReturn(new RedirectResponse('/2fa'));

        $response = $this->controllerMock->resendTwoFactorCode($request, $this->authService);

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \App\Controller\SecurityController::cancelTwoFactor
     */
    public function testCancelTwoFactorInvalidCsrf()
    {
        $request = new Request([], ['_token' => 'invalid']);

        $this->controllerMock->method('isCsrfTokenValid')->willReturn(false);
        $this->controllerMock->method('redirectToRoute')
            ->with('2fa_login')
            ->willReturn(new RedirectResponse('/2fa/login'));

        $response = $this->controllerMock->cancelTwoFactor(
            $request,
            $this->createMock(TokenStorageInterface::class)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/2fa/login', $response->getTargetUrl());
    }

    /**
     * @covers \App\Controller\SecurityController::cancelTwoFactor
     */
    public function testCancelTwoFactorValidCsrfWithSession()
    {
        $request = new Request([], ['_token' => 'good']);

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method('invalidate');

        $request->setSession($session);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->once())
            ->method('setToken')
            ->with(null);

        $this->controllerMock->method('isCsrfTokenValid')->willReturn(true);

        $this->controllerMock->method('redirectToRoute')
            ->with('app_login')
            ->willReturn(new RedirectResponse('/login'));

        $response =  $this->controllerMock->cancelTwoFactor($request, $tokenStorage);

        $this->assertEquals('/login', $response->getTargetUrl());
    }

    /**
     * @covers \App\Controller\SecurityController::cancelTwoFactor
     */
    public function testCancelTwoFactorValidCsrfWithoutSession()
    {
        $request = new Request([], ['_token' => 'good']);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->once())->method('setToken')->with(null);

        $this->controllerMock->method('isCsrfTokenValid')->willReturn(true);
        $this->controllerMock->method('redirectToRoute')
            ->with('app_login')
            ->willReturn(new RedirectResponse('/login'));

        $response =  $this->controllerMock->cancelTwoFactor($request, $tokenStorage);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getTargetUrl());
    }

    private function removeResetToken()
    {
        $resetToken = $this->entityManager->getRepository(ResetPasswordRequest::class)
            ->findOneBy(['user' => $this->testUser->getId()]);

        if ($resetToken) {
            $this->entityManager->remove($resetToken);
            $this->entityManager->flush();
        }
    }
}
