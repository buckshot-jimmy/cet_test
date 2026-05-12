<?php

namespace App\Tests\Controller;

use App\Controller\ClientiPjController;
use App\Entity\PersoaneJuridice;
use App\Repository\PersoaneJuridiceRepository;
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

class ClientiPjControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->adminService = $this->createMock(AdminService::class);
        $this->firmeConstraint = $this->createMock(FirmeConstraints::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->controller = new ClientiPjController(
            $this->em,
            $this->validator,
            $this->firmeConstraint,
            $this->translator,
            $this->adminService,
            $this->authorizationChecker
        );

        $this->controllerMock = $this->getMockBuilder(ClientiPjController::class)
            ->setConstructorArgs([$this->em, $this->validator, $this->firmeConstraint, $this->translator,
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

        $this->repo = $this->createMock(PersoaneJuridiceRepository::class);

        $this->dbEm = $this->getContainer()->get(EntityManagerInterface::class);
        $this->dbEm->getConnection()->beginTransaction();

        $this->pj = new PersoaneJuridice();
        $this->pj->setDenumire('test11');
        $this->pj->setCui('443333445');
        $this->pj->setAdresa('Addr22');
        $this->pj->setSters(false);
        $this->dbEm->persist($this->pj);

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
     * @covers \App\Controller\ClientiPjController::__construct
     */
    public function testCanBuildController()
    {
        $controller = new ClientiPjController(
            $this->em,
            $this->validator,
            $this->firmeConstraint,
            $this->translator,
            $this->adminService,
            $this->authorizationChecker
        );

        $this->assertInstanceOf(ClientiPjController::class, $controller);
    }

    /**
     * @covers \App\Controller\ClientiPjController::clientiPj
     */
    public function testRendersPjTemplateWithCorrectData(): void
    {
        $this->client->loginUser($this->admin, 'main');

        $this->client->request('GET', '/clienti_pj');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('Clienti', $response->getContent());
    }

    /**
     * @covers \App\Controller\ClientiPjController::clientiPj
     */
    public function testRendersClientiWithAccessDenied(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('VIEW', $this->isInstanceOf(PersoaneJuridice::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->clientiPj();

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\ClientiPjController::list
     */
    public function testItCanFetchClientiList()
    {
        $this->repo->method('getAllClientiPj')->with(['filter'])->willReturn(['clienti']);

        $this->client->loginUser($this->admin, 'main');

        $this->client->request('GET', '/clienti_pj/list');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('recordsFiltered', $response->getContent());
    }

    /**
     * @covers \App\Controller\ClientiPjController::salveazaClientPj
     */
    public function testClientiCanShowValidationErrorsFromEdit(): void
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

        $this->client->loginUser($this->testMedic, 'main');

        $request = new Request([], ['form' => 'denumire=pj&cui=101&adresa=adresa']);

        $this->client->loginUser($this->admin, 'main');

        $response = $this->controller->salveazaClientPj($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $data['status']);
        $this->assertSame('Failed operation. Some validation error', $data['message']);
    }

    /**
     * @covers \App\Controller\ClientiPjController::salveazaClientPj
     */
    public function testSaveWithAccessDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->salveazaClientPj(new Request());

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\ClientiPjController::salveazaClientPj
     */
    public function testItCanSavepj()
    {
        $this->authorizationChecker->method('isGranted')->willReturn(true);

        $this->em->method('getRepository')
            ->with(PersoaneJuridice::class)
            ->willReturn($this->repo);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $this->client->loginUser($this->admin, 'main');

        $this->controllerMock->method('getUser')->willReturn($this->admin);

        $pj = [
            'denumire' => 'pj',
            'cui' => '101',
            'adresa' => 'adresa3',
        ];

        $this->repo->method('saveClientPj')->with($pj);

        $request = new Request([], ['form' => 'denumire=pj&cui=101&adresa=adresa3']);

        $response = $this->controllerMock->salveazaClientPj($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
        $this->assertEquals('Successful operation', $data['message']);
    }

    /**
     * @covers \App\Controller\ClientiPjController::getClientPj
     */
    public function testItCanGetPj()
    {
        $this->client->loginUser($this->admin, 'main');

        $this->authorizationChecker
            ->method('isGranted')
            ->with('VIEW', $this->isInstanceOf(PersoaneJuridice::class))
            ->willReturn(true);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $this->em->method('getRepository')
            ->with(PersoaneJuridice::class)
            ->willReturn($this->repo);

        $this->client->request('GET', '/get_client_pj', ['id' => $this->pj->getId()]);

        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('adresa', $response->getContent());
        $this->assertStringContainsString('cui', $response->getContent());
    }

    /**
     * @covers \App\Controller\ClientiPjController::getClientPj
     */
    public function testGetPjWithAccessDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->getClientPj(new Request());

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\ClientiPjController::sterge
     */
    public function testClientAccessDeniedForDelete(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('DELETE', $this->isInstanceOf(PersoaneJuridice::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->sterge(new Request([], ['id' => 10]));

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\ClientiPjController::sterge
     */
    public function testPacientiCanBeDeleted(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('DELETE', $this->isInstanceOf(PersoaneJuridice::class))
            ->willReturn(true);

        $this->repo->expects($this->once())
            ->method('deleteClientPj')
            ->with($this->pj->getId());

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $request = new Request([], ['id' => $this->pj->getId()]);

        $this->em->method('getRepository')->willReturnMap([
            [PersoaneJuridice::class, $this->repo],
        ]);

        $response = $this->controller->sterge($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
        $this->assertEquals('Successful operation', $data['message']);
    }

    /**
     * @covers \App\Controller\ClientiPjController::getClientiPjByCui
     */
    public function testGetClientiPjByCui()
    {
        $this->client->loginUser($this->testMedic, 'main');

        $this->client->request('GET', '/get_clienti_pj_by_cui', ['cui' => '443333445']);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals($this->pj->getId(), $data['clienti'][0]['id']);
    }
}
