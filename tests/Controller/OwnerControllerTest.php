<?php

namespace App\Tests\Controller;

use App\Controller\OwnersController;
use App\Entity\Consultatii;
use App\Entity\Owner;
use App\Repository\ConsultatiiRepository;
use App\Repository\OwnerRepository;
use App\Repository\UserRepository;
use App\Services\AdminService;
use App\Validator\FirmeConstraints;
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

class OwnerControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->adminService = $this->createMock(AdminService::class);
        $this->ownerConstraint = $this->createMock(FirmeConstraints::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->controller = new OwnersController(
            $this->em,
            $this->validator,
            $this->ownerConstraint,
            $this->translator,
            $this->adminService,
            $this->authorizationChecker
        );

        $this->controllerMock = $this->getMockBuilder(OwnersController::class)
            ->setConstructorArgs([$this->em, $this->validator, $this->ownerConstraint, $this->translator,
                $this->adminService, $this->authorizationChecker])
            ->onlyMethods(['getUser'])
            ->getMock();

        $this->testUser = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('test@test.ro');

        $this->testMedic = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('damian.popescu@mindreset.ro');

        $this->admin = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('ciprianmarta.cm@gmail.com');

        $this->repo = $this->createMock(OwnerRepository::class);

        $this->dbEm = $this->getContainer()->get(EntityManagerInterface::class);
        $this->dbEm->getConnection()->beginTransaction();

        $this->owner = new Owner();
        $this->owner->setDenumire('test');
        $this->owner->setSerieFactura('Ftest');
        $this->owner->setCui('443344445');
        $this->owner->setAdresa('Addr');
        $this->owner->setSters(false);
        $this->dbEm->persist($this->owner);

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
     * @covers \App\Controller\OwnersController::__construct
     */
    public function testCanBuildController()
    {
        $controller = new OwnersController(
            $this->em,
            $this->validator,
            $this->ownerConstraint,
            $this->translator,
            $this->adminService,
            $this->authorizationChecker
        );

        $this->assertInstanceOf(OwnersController::class, $controller);
    }

    /**
     * @covers \App\Controller\OwnersController::owners
     */
    public function testRendersOwnersTemplateWithCorrectData(): void
    {
        $this->client->loginUser($this->admin, 'main');

        $this->client->request('GET', '/owners');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('Firme', $response->getContent());
    }

    /**
     * @covers \App\Controller\OwnersController::owners
     */
    public function testRendersOwnersWithAccessDenied(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('VIEW', $this->isInstanceOf(Owner::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->owners();

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\OwnersController::list
     */
    public function testItCanFetchOwnersList()
    {
        $this->repo->method('getAllOwners')->with(['filter'])->willReturn(['owners']);

        $this->client->loginUser($this->admin, 'main');

        $this->client->request('GET', '/owners/list');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('recordsFiltered', $response->getContent());
    }

    /**
     * @covers \App\Controller\OwnersController::salveazaOwner
     */
    public function testOwnersCanShowValidationErrorsFromEdit(): void
    {
        $this->authorizationChecker->method('isGranted')->willReturn(true);

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

        $request = new Request([], ['form' => 'denumire=own&cui=101&adresa=adresa&serieFactura=F']);

        $this->client->loginUser($this->admin, 'main');

        $response = $this->controller->salveazaOwner($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $data['status']);
        $this->assertSame('Failed operation. Some validation error', $data['message']);
    }

    /**
     * @covers \App\Controller\OwnersController::salveazaOwner
     */
    public function testSaveWithAccessDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->salveazaOwner(new Request());

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\OwnersController::salveazaOwner
     */
    public function testItCanSaveOwner()
    {
        $this->authorizationChecker->method('isGranted')->willReturn(true);

        $this->em->method('getRepository')
            ->with(Owner::class)
            ->willReturn($this->repo);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $this->client->loginUser($this->admin, 'main');

        $this->controllerMock->method('getUser')->willReturn($this->admin);

        $owner = [
            'denumire' => 'own',
            'cui' => '101',
            'adresa' => 'adresa',
            'serieFactura' => 'F'
        ];

        $this->repo->method('saveOwner')->with($owner);

        $request = new Request([], ['form' => 'denumire=own&cui=101&adresa=adresa&serieFactura=F']);

        $response = $this->controllerMock->salveazaOwner($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
        $this->assertEquals('Successful operation', $data['message']);
    }

    /**
     * @covers \App\Controller\OwnersController::getOwner
     */
    public function testItCanGetOwner()
    {
        $this->client->loginUser($this->admin, 'main');

        $this->authorizationChecker
            ->method('isGranted')
            ->with('VIEW', $this->isInstanceOf(Owner::class))
            ->willReturn(true);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $this->em->method('getRepository')
            ->with(Owner::class)
            ->willReturn($this->repo);

        $this->client->request('GET', '/owners/get_owner', ['id' => $this->owner->getId()]);

        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('adresa', $response->getContent());
        $this->assertStringContainsString('cui', $response->getContent());
    }

    /**
     * @covers \App\Controller\OwnersController::getOwner
     */
    public function testGetOwnerWithAccessDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->getOwner(new Request());

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\OwnersController::sterge
     */
    public function testOwnerAccessDeniedForDelete(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('DELETE', $this->isInstanceOf(Owner::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->sterge(new Request([], ['id' => 10]));

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\OwnersController::sterge
     */
    public function testDeleteWithHasConsultationsOpenDenied(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('DELETE', $this->isInstanceOf(Owner::class))
            ->willReturn(true);

        $cons = $this->createMock(ConsultatiiRepository::class);

        $this->em->method('getRepository')
            ->with(Consultatii::class)
            ->willReturn($cons);

        $cons->method('ownerAreConsultatiiDeschise')->willReturn(true);

        $this->translator->method('trans')
            ->with('Owner has open consultations')
            ->willReturn('Owner has open consultations.');

        $request = new Request([], ['id' => $this->owner->getId()]);

        $response = $this->controller->sterge($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $data['status']);
        $this->assertEquals('Owner has open consultations.', $data['message']);
    }

    /**
     * @covers \App\Controller\OwnersController::sterge
     */
    public function testPacientiCanBeDeleted(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('DELETE', $this->isInstanceOf(Owner::class))
            ->willReturn(true);

        $this->repo->expects($this->once())
            ->method('deleteOwner')
            ->with($this->owner->getId());

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $request = new Request([], ['id' => $this->owner->getId()]);

        $cons = $this->createMock(ConsultatiiRepository::class);

        $this->em->method('getRepository')->willReturnMap([
            [Consultatii::class, $cons],
            [Owner::class, $this->repo],
        ]);

        $cons->method('ownerAreConsultatiiDeschise')->willReturn(false);

        $response = $this->controller->sterge($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
        $this->assertEquals('Successful operation', $data['message']);
    }
}
