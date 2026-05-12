<?php

namespace App\Tests\Controller;

use App\Controller\RapoarteController;
use App\Entity\Consultatii;
use App\Entity\Owner;
use App\Entity\RapoarteColaboratori;
use App\Entity\User;
use App\PDF\Service\PdfService;
use App\Repository\ConsultatiiRepository;
use App\Repository\OwnerRepository;
use App\Repository\RapoarteRepository;
use App\Repository\UserRepository;
use App\Services\NomenclatoareService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class RapoarteControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->controller = new RapoarteController(
            $this->em,
            $this->translator,
            $this->authorizationChecker
        );

        $this->testUser = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('test@test.ro');

        $this->rapoarteRepo = $this->createMock(RapoarteRepository::class);
        $this->consRepo = $this->createMock(ConsultatiiRepository::class);

        $this->testMedic = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('damian.popescu@mindreset.ro');

        $this->controllerMock = $this->getMockBuilder(RapoarteController::class)
            ->setConstructorArgs([$this->em, $this->translator, $this->authorizationChecker])
            ->onlyMethods(['getUser'])
            ->getMock();
    }

    /**
     * @covers \App\Controller\RapoarteController::__construct
     */
    public function testCanBuildController()
    {
        $controller = new RapoarteController(
            $this->em,
            $this->translator,
            $this->authorizationChecker
        );

        $this->assertInstanceOf(RapoarteController::class, $controller);
    }

    /**
     * @covers \App\Controller\RapoarteController::rapoarte
     */
    public function testViewRapoarteWithAccessDenied()
    {
        $this->client->loginUser($this->testUser, 'main');

        $this->authorizationChecker
            ->method('isGranted')
            ->with('VIEW', $this->isInstanceOf(RapoarteColaboratori::class))
            ->willReturn(false);

        $this->client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->request('GET', '/rapoarte');

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\RapoarteController::rapoarte
     */
    public function testViewRapoarteColaboratoriRender()
    {
        $this->client->loginUser($this->testMedic, 'main');

        $mediciRepo = $this->createMock(UserRepository::class);
        $mediciRepo->method('getAllMedici')->willReturn(['medici']);

        $ownerRepo = $this->createMock(OwnerRepository::class);
        $ownerRepo->method('getAllOwners')->willReturn(['owners']);

        $nomenclatoare = $this->createMock(NomenclatoareService::class);
        $nomenclatoare->method('getLunileAnului')->willReturn(['luni']);

        $this->em->method('getRepository')->willReturnMap([
            [User::class, $mediciRepo],
            [Owner::class, $ownerRepo]
        ]);

        $this->client->request('GET', '/rapoarte');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('Rapoarte plati medici colaboratori', $response->getContent());
    }

    /**
     * @covers \App\Controller\RapoarteController::listRapoarteColaboratori
     */
    public function testViewRapoarteColaboratoriListRenderWithAccessDenied()
    {
        $this->client->loginUser($this->testUser, 'main');

        $this->authorizationChecker
            ->method('isGranted')
            ->with('VIEW', $this->isInstanceOf(RapoarteColaboratori::class))
            ->willReturn(false);

        $this->client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->request('GET', '/list_rapoarte_colaboratori');

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\RapoarteController::listRapoarteColaboratori
     */
    public function testViewRapoarteColaboratoriListRender()
    {
        $this->client->loginUser($this->testMedic, 'main');

        $this->em->method('getRepository')->willReturn($this->rapoarteRepo);

        $this->controllerMock->method('getUser')->willReturn($this->testMedic);

        $this->client->request('GET', '/list_rapoarte_colaboratori');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('recordsTotal', $response->getContent());
        $this->assertStringContainsString('recordsFiltered', $response->getContent());
    }

    /**
     * @covers \App\Controller\RapoarteController::saveRapoarteColaboratori
     */
    public function testSaveRaportWithAccessDenied()
    {
        $this->client->loginUser($this->testUser, 'main');

        $this->authorizationChecker
            ->method('isGranted')
            ->with('ADD_EDIT', $this->isInstanceOf(RapoarteColaboratori::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->controller->saveRapoarteColaboratori(new Request([]));

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\RapoarteController::saveRapoarteColaboratori
     */
    public function testSaveRaport()
    {
        $this->authorizationChecker->method('isGranted')->willReturn(true);

        $this->rapoarteRepo->expects($this->once())
            ->method('saveRaportColaboratori')
            ->with(['id' => 10]);

        $this->em->method('getRepository')
            ->with(RapoarteColaboratori::class)
            ->willReturn($this->rapoarteRepo);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $response = $this->controller->saveRapoarteColaboratori(new Request([], ['formData' => 'id=10']));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
        $this->assertEquals('Successful operation', $data['message']);
    }

    /**
     * @covers \App\Controller\RapoarteController::calculeazaPlataColaborator
     * @dataProvider addRaportDataProvider
     */
    public function testCalculeazaPlataColaborator($stare, $totalDePlata, $message)
    {
        $this->client->loginUser($this->testUser, 'main');

        $this->authorizationChecker->method('isGranted')->willReturn(true);

        $this->em->method('getRepository')->willReturnMap([
            [RapoarteColaboratori::class, $this->rapoarteRepo],
            [Consultatii::class, $this->consRepo],
        ]);

        $this->translator->method('trans')
            ->with($message)
            ->willReturn($message);

        switch ($stare) {
            case 'nou':
                $this->rapoarteRepo->expects($this->once())->method('getRaportByFilters')
                    ->willReturn(null);
                $this->consRepo->method('calculeazaPlataColaborator')->willReturn($totalDePlata);
                break;
            case 'neplatita':
                $this->rapoarteRepo->expects($this->once())->method('getRaportByFilters')
                    ->willReturn(['id' => 10, 'stare' => $stare]);
                $this->consRepo->method('calculeazaPlataColaborator')->willReturn($totalDePlata);
                break;
            case 'platita':
                $this->rapoarteRepo->expects($this->once())->method('getRaportByFilters')
                    ->willReturn(['id' => 10, 'stare' => $stare]);
                $this->consRepo->method('calculeazaPlataColaborator')->willReturn($totalDePlata);
                break;
            case 'nimic':
                $this->rapoarteRepo->expects($this->once())->method('getRaportByFilters')
                    ->willReturn([]);
                $this->consRepo->method('calculeazaPlataColaborator')->willReturn(null);
                break;
        }

        $response = $this->controllerMock->calculeazaPlataColaborator(new Request([]));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
    }

    public function addRaportDataProvider()
    {
        yield ['stare' => 'nou', 'totalDePlata' => 10, 'message' => 'Successful operation'];
        yield ['stare' => 'neplatita', 'totalDePlata' => 10, 'message' => 'It can be paid'];
        yield ['stare' => 'platita', 'totalDePlata' => 10, 'message' => 'There is a report for the selected data'];
        yield ['stare' => 'nimic', 'totalDePlata' => null, 'message' => 'No amounts to pay'];
    }

    /**
     * @covers \App\Controller\RapoarteController::platesteColaborator
     */
    public function testPlatesteColaboratorWithDeniedAccess()
    {
        $this->client->loginUser($this->testUser, 'main');

        $this->authorizationChecker
            ->method('isGranted')
            ->with('ADD_EDIT', $this->isInstanceOf(RapoarteColaboratori::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->controller->platesteColaborator(new Request([]));

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\RapoarteController::platesteColaborator
     */
    public function testPlatesteColaborator()
    {
        $this->authorizationChecker->method('isGranted')->willReturn(true);

        $this->rapoarteRepo->expects($this->once())
            ->method('platesteColaborator')
            ->with(['raport_colaboratori_id' => 1]);

        $this->em->method('getRepository')
            ->with(RapoarteColaboratori::class)
            ->willReturn($this->rapoarteRepo);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $response = $this->controller->platesteColaborator(
            new Request([], ['formData' => 'raport_colaboratori_id=1']));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
        $this->assertEquals('Successful operation', $data['message']);
    }

    /**
     * @covers \App\Controller\RapoarteController::pdf
     */
    public function testCanGenerateRaportPdf()
    {
        $this->authorizationChecker->method('isGranted')->willReturn(true);

        $this->client->loginUser($this->testMedic, 'main');

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $request = new Request([], ['id' => '1']);

        $pdfServiceMock = $this->createMock(PdfService::class);
        $pdfServiceMock->expects($this->once())->method('printToPdf')
            ->with(
                1,
                'plata_colaborator.html.twig',
                ['orientation' => 'L','footer' => '{PAGENO}/{nb}']);

        $response = $this->controller->pdf($request, $pdfServiceMock);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data[0]);
    }

    /**
     * @covers \App\Controller\RapoarteController::plataColaborator
     */
    public function testPlataColaboratorWithAccessDenied()
    {
        $this->client->loginUser($this->testUser, 'main');

        $this->authorizationChecker
            ->method('isGranted')
            ->with('ADD_EDIT', $this->isInstanceOf(RapoarteColaboratori::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->controller->plataColaborator(new Request([]));

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\RapoarteController::plataColaborator
     */
    public function testPlataColaborator()
    {
        $this->authorizationChecker->method('isGranted')->willReturn(true);

        $this->em->method('getRepository')
            ->with(RapoarteColaboratori::class)
            ->willReturn($this->rapoarteRepo);

        $this->rapoarteRepo->expects($this->once())
            ->method('platesteColaborator')
            ->with(['raport_plateste_id' => '1', 'raport_colaboratori_id' => '1'])
            ->willReturn(true);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $response = $this->controller->plataColaborator(
            new Request([], ['formData' => 'raport_plateste_id=1']));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
        $this->assertEquals('Successful operation', $data['message']);
    }

    /**
     * @covers \App\Controller\RapoarteController::getColaboratoriOwner
     */
    public function testCanGetColaboratoriOwner()
    {
        $this->client->loginUser($this->testMedic, 'main');

        $this->translator->method('trans')
            ->with('Data collection success')
            ->willReturn('Data collection success');

        $this->client->request('GET', '/rapoarte/get_colaboratori_owner', ['ownerId' => 11222]);

        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('data', $response->getContent());
    }
}
