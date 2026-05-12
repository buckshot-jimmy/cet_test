<?php

namespace App\Tests\Controller;

use App\Controller\UserController;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\LoginFormAuthenticator;
use App\Services\AdminService;
use App\Validator\UserConstraints;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->userConstraints = $this->createMock(UserConstraints::class);
        $this->adminService = $this->createMock(AdminService::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->controller = new UserController(
            $this->em,
            $this->translator,
            $this->adminService,
            $this->validator,
            $this->userConstraints,
            $this->authorizationChecker
        );

        $this->testUser = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('test@test.ro');

        $this->testMedic = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('damian.popescu@mindreset.ro');

        $this->userAdmin = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('ciprianmarta.cm@gmail.com');

        $this->userRepo = $this->createMock(UserRepository::class);
        $this->em->method('getRepository')->willReturn($this->userRepo);

        $this->uai = $this->createMock(UserAuthenticatorInterface::class);
        $this->lfa = $this->createMock(LoginFormAuthenticator::class);
    }

    /**
     * @covers \App\Controller\UserController::__construct
     */
    public function testCanBuildController()
    {
        $controller = new UserController(
            $this->em,
            $this->translator,
            $this->adminService,
            $this->validator,
            $this->userConstraints,
            $this->authorizationChecker
        );

        $this->assertInstanceOf(UserController::class, $controller);
    }

    /**
     * @covers \App\Controller\UserController::utilizatori
     */
    public function testRenderUsersWithDeniedAccess()
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('VIEW', $this->isInstanceOf(User::class))
            ->willReturn(false);

        $this->client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->client->request('GET', '/user');

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\UserController::utilizatori
     */
    public function testRenderUsers()
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('VIEW', $this->isInstanceOf(User::class))
            ->willReturn(true);

        $this->client->loginUser($this->testMedic, 'main');

        $this->client->request('GET', '/user');

        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('Utilizatori', $response->getContent());
    }

    /**
     * @covers \App\Controller\UserController::list
     */
    public function testListUsersWithDeniedAccess()
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('VIEW', $this->isInstanceOf(User::class))
            ->willReturn(false);

        $this->client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->client->request('GET', '/user/list');

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\UserController::list
     */
    public function testListUsers()
    {
        $this->client->loginUser($this->testMedic, 'main');

        $this->authorizationChecker
            ->method('isGranted')
            ->with('VIEW', $this->isInstanceOf(User::class))
            ->willReturn(true);

        $this->client->request('GET', '/user/list', ['loggedUserId' => 1, 'sort' => null]);
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('recordsTotal', $response->getContent());
        $this->assertStringContainsString('recordsFiltered', $response->getContent());
    }

    /**
     * @covers \App\Controller\UserController::sterge
     */
    public function testStergeWithDeniedAccess()
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('DELETE', $this->isInstanceOf(User::class))
            ->willReturn(false);

        $this->client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testMedic, 'main');

        $this->controller->sterge(new Request([], ['id' => 10]));

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\UserController::sterge
     */
    public function testStergeUser()
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('DELETE', $this->isInstanceOf(User::class))
            ->willReturn(true);

        $this->client->loginUser($this->userAdmin, 'main');

        $this->userRepo->expects($this->once())
            ->method('deleteUser')
            ->with(123);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $request = new Request([], ['id' => 123]);

        $response = $this->controller->sterge($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
        $this->assertEquals('Successful operation', $data['message']);
    }

    /**
     * @covers \App\Controller\UserController::salveazaUtilizator
     */
    public function testSalveazaWithDeniedAccessAdd()
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('ADD', $this->isInstanceOf(User::class))
            ->willReturn(false);

        $this->client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testMedic, 'main');

        $uai = $this->createMock(UserAuthenticatorInterface::class);
        $lfa = $this->createMock(LoginFormAuthenticator::class);

        $this->controller->salveazaUtilizator(new Request([]), $uai, $lfa);

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\UserController::salveazaUtilizator
     */
    public function testSalveazaWithDeniedAccessEdit()
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('EDIT', $this->isInstanceOf(User::class))
            ->willReturn(false);

        $this->client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testMedic, 'main');

        $this->userRepo->method('findOneBy')->willReturn($this->testUser);

        $this->controller->salveazaUtilizator(new Request([], ['form' => 'editUserId=1']), $this->uai, $this->lfa);

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\UserController::salveazaUtilizator
     * @dataProvider dataProviderEdit
     */
    public function testSalveazaWithValidationErrors($editUserId)
    {
        $this->client->loginUser($this->userAdmin, 'main');

        $violation = new ConstraintViolation(
            'Some validation error',
            null,
            [],
            '',
            'name',
            null
        );

        $violations = new ConstraintViolationList([$violation]);

        if ($editUserId) {
            $this->userRepo->method('findOneBy')->willReturn($this->testUser);
        }

        $this->authorizationChecker
            ->method('isGranted')
            ->with('EDIT', $this->isInstanceOf(User::class))
            ->willReturn(true);

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

        $response = $this->controller->salveazaUtilizator(
            new Request([], ['form' => 'editUserId=' . $editUserId]), $this->uai, $this->lfa);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $data['status']);
        $this->assertSame('Failed operation. Some validation error', $data['message']);
    }

    /**
     * @covers \App\Controller\UserController::salveazaUtilizator
     * @dataProvider dataProvider
     */
    public function testSalveazaWithSuccess($operation, $editUserId, $loggedUserId = null, $roleName = null)
    {
        $this->client->loginUser($this->userAdmin, 'main');

        $form = 'editUserId=' . $editUserId . '&loggedUserId=' . $loggedUserId . '&role_name=' . $roleName;

        if ($editUserId) {
            $this->userRepo->method('findOneBy')->willReturn($this->testUser);
        }

        $this->authorizationChecker
            ->method('isGranted')
            ->with($operation, $this->isInstanceOf(User::class))
            ->willReturn(true);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation.');

        $savedUser = new User();

        $this->userRepo->expects($this->once())
            ->method('saveUser')
            ->with(['editUserId' => $editUserId, 'loggedUserId' => $loggedUserId, 'role_name' => $roleName])
            ->willReturn($savedUser);

        if ((string) $editUserId !== '' && (string) $editUserId === (string) $loggedUserId) {
            $this->uai->expects($this->once())
                ->method('authenticateUser')
                ->with(
                    $this->isInstanceOf(User::class),
                    $this->identicalTo($this->lfa),
                    $this->isInstanceOf(Request::class)
                )
                ->willReturn(new Response());
        }

        $response = $this->controller->salveazaUtilizator(
            new Request([], ['form' => $form]), $this->uai, $this->lfa);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
        $this->assertEquals('Successful operation.', $data['message']);
    }

    protected function dataProvider()
    {
        yield ['operation' => 'ADD', 'editUserId' => '', 'loggedUserId' => '', 'role_name' => ''];
        yield ['operation' => 'EDIT', 'editUserId' => '11', 'loggedUserId' => '11', 'role_name' => 'ROLE_Test'];
        yield ['operation' => 'EDIT', 'editUserId' => '1', 'loggedUserId' => '1', 'role_name' => 'ROLE_Test'];
    }

    protected function dataProviderEdit()
    {
        yield ['editUserId' => 11];
        yield ['editUserId' => 1, 'loggedUserId' => 1];
    }

    /**
     * @covers \App\Controller\UserController::getUtilizator
     */
    public function testCanGetUser()
    {
        $userId = $this->testUser->getId();

        $repoResult = [
            'id' => $userId,
            'username' => 'test',
            'titulatura' => '',
        ];

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $this->client->loginUser($this->testMedic, 'main');

        $this->client->request('GET', '/user/get_user', ['id' => $userId]);

        $response = $this->client->getResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertStringContainsString('titulatura', $response->getContent());
        $this->assertStringContainsString('username', $response->getContent());

        $this->assertSame(Response::HTTP_OK, $data['status']);
//        $this->assertSame('Successful operation', $data['message']);
    }
}
