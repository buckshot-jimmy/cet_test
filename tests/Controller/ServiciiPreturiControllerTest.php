<?php

namespace App\Tests\Controller;

use App\Controller\ServiciiPreturiController;
use App\Entity\Owner;
use App\Entity\Preturi;
use App\Entity\Servicii;
use App\Entity\User;
use App\Repository\OwnerRepository;
use App\Repository\PreturiRepository;
use App\Repository\ServiciiRepository;
use App\Repository\UserRepository;
use App\Services\AdminService;
use App\Validator\FirmeConstraints;
use App\Validator\TarifConstraints;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ServiciiPreturiControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->adminService = $this->createMock(AdminService::class);
        $this->tarifConstraints = $this->createMock(TarifConstraints::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->controller = new ServiciiPreturiController(
            $this->em,
            $this->validator,
            $this->tarifConstraints,
            $this->translator,
            $this->adminService,
            $this->authorizationChecker
        );

        $this->testUser = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('test@test.ro');

        $this->testMedic = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('damian.popescu@mindreset.ro');

        $this->preturiRepo = $this->createMock(PreturiRepository::class);
    }

    /**
     * @covers \App\Controller\ServiciiPreturiController::__construct
     */
    public function testCanBuildController()
    {
        $controller = new ServiciiPreturiController(
            $this->em,
            $this->validator,
            $this->tarifConstraints,
            $this->translator,
            $this->adminService,
            $this->authorizationChecker
        );

        $this->assertInstanceOf(ServiciiPreturiController::class, $controller);
    }

    /**
     * @covers \App\Controller\ServiciiPreturiController::preturi
     */
    public function testCanGetOwners()
    {
        $this->authorizationChecker->method('isGranted')->willReturn(true);

        $serviciiRepo = $this->createMock(ServiciiRepository::class);
        $serviciiRepo->method('getAllServicii')->willReturn(['servicii']);

        $mediciRepo = $this->createMock(UserRepository::class);
        $mediciRepo->method('getAllMedici')->willReturn(['medici']);

        $ownerRepo = $this->createMock(OwnerRepository::class);
        $ownerRepo->method('getAllOwners')->willReturn(['owners']);

        $this->em->method('getRepository')->willReturnMap([
            [Servicii::class, $serviciiRepo],
            [User::class, $mediciRepo],
            [Owner::class, $ownerRepo],
        ]);

        $this->client->loginUser($this->testMedic, 'main');

        $this->client->request('GET', '/servicii_preturi');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('Servicii si tarife', $response->getContent());
    }

    /**
     * @covers \App\Controller\ServiciiPreturiController::list
     */
    public function testItCanReturnListPreturi(): void
    {
        $this->em->method('getRepository')
            ->with(Preturi::class)
            ->willReturn($this->preturiRepo);

        $this->preturiRepo->method('getAllPreturi')->with(['filter'])->willReturn(['preturi']);

        $this->client->loginUser($this->testMedic, 'main');

        $this->client->request('GET', '/servicii_preturi/list');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('recordsTotal', $response->getContent());
        $this->assertStringContainsString('recordsFiltered', $response->getContent());
    }

    /**
     * @covers \App\Controller\ServiciiPreturiController::stergePret
     */
    public function testCanDeletePretWithAccessDenied()
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('DELETE', $this->isInstanceOf(Servicii::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->stergePret(new Request([], ['id' => 10]));

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\ServiciiPreturiController::stergePret
     */
    public function testCanDeletePret()
    {
        $this->authorizationChecker->method('isGranted')->willReturn(true);

        $this->preturiRepo->expects($this->once())
            ->method('deletePret')
            ->with(123);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $request = new Request([], ['id' => 123]);

        $this->em->method('getRepository')
            ->with(Preturi::class)
            ->willReturn($this->preturiRepo);

        $response = $this->controller->stergePret($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
        $this->assertEquals('Successful operation', $data['message']);
    }

    /**
     * @covers \App\Controller\ServiciiPreturiController::salveazaServiciu
     */
    public function testCanSaveServiciuWithAccessDenied()
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('ADD_EDIT', $this->isInstanceOf(Servicii::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->salveazaServiciu(new Request([], ['id' => 10]));

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\ServiciiPreturiController::salveazaServiciu
     */
    public function testCanSaveServiciu()
    {
        $this->authorizationChecker->method('isGranted')->willReturn(true);

        $pret = [
            'add_denumire_serviciu' => 'test',
            'add_tip_serviciu' => 'test',
            'field' => 'test'
        ];

        $form = 'add_denumire_serviciu=test&add_tip_serviciu=test&field=test';

        $serviciiRepo = $this->createMock(ServiciiRepository::class);
        $this->em->method('getRepository')
            ->with(Servicii::class)
            ->willReturn($serviciiRepo);

        $serviciiRepo->expects($this->once())
            ->method('saveServiciu')
            ->with($pret);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $request = new Request([], ['form' => $form]);

        $response = $this->controller->salveazaServiciu($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
        $this->assertEquals('Successful operation', $data['message']);
    }

    /**
     * @covers \App\Controller\ServiciiPreturiController::salveazaPret
     */
    public function testCanSavePretWithAccessDenied()
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('ADD_EDIT', $this->isInstanceOf(Preturi::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->salveazaPret(new Request([], ['id' => 10]));

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\ServiciiPreturiController::salveazaPret
     */
    public function testCanSavePretWithValidationErrors()
    {
        $violation = new ConstraintViolation(
            'Some validation error',
            null,
            [],
            '',
            'name',
            null
        );

        $violations = new ConstraintViolationList([$violation]);

        $this->authorizationChecker
            ->method('isGranted')
            ->with('ADD_EDIT', $this->isInstanceOf(Preturi::class))
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

        $this->client->loginUser($this->testMedic, 'main');

        $response = $this->controller->salveazaPret(new Request([],
            ['form' => 'pret_id=10']));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $data['status']);
        $this->assertSame('Failed operation. Some validation error', $data['message']);
    }

    /**
     * @covers \App\Controller\ServiciiPreturiController::salveazaPret
     */
    public function testCanSavePret()
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('ADD_EDIT', $this->isInstanceOf(Preturi::class))
            ->willReturn(true);

        $pret = ['pret_id' => '101'];

        $form = 'pret_id=101';

        $this->em->method('getRepository')
            ->with(Preturi::class)
            ->willReturn($this->preturiRepo);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $user = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('ciprianmarta.cm@gmail.com');

        $this->client->loginUser($user, 'main');

        $this->preturiRepo->method('savePret')->with($pret);

        $request = new Request([], ['form' => $form]);

        $response = $this->controller->salveazaPret($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
        $this->assertEquals('Successful operation', $data['message']);
    }

    /**
     * @covers \App\Controller\ServiciiPreturiController::getPret
     */
    public function testItCanGetPret()
    {
        $this->client->loginUser($this->testMedic, 'main');

        $this->em->method('getRepository')
            ->with(Preturi::class)
            ->willReturn($this->preturiRepo);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $this->client->request('GET', '/servicii_preturi/get_pret', ['id' => 1]);

        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('pretData', $response->getContent());
    }

    /**
     * @covers \App\Controller\ServiciiPreturiController::getPreturiForMedic
     */
    public function testItCanGetPreturiMedic()
    {
        $this->client->loginUser($this->testMedic, 'main');

        $this->client->request('GET', '/servicii_preturi/get_preturi_medic',
            ['medic' => $this->testMedic->getId()]);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertIsArray($data['preturiMedic']);
    }
}
