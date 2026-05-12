<?php

namespace App\Tests\Controller;

use App\Controller\FacturiController;
use App\Entity\Consultatii;
use App\Entity\FacturaConsultatie;
use App\Entity\Facturi;
use App\Entity\Owner;
use App\Entity\Pacienti;
use App\Entity\Preturi;
use App\PDF\Service\PdfService;
use App\Repository\FacturiRepository;
use App\Repository\PacientiRepository;
use App\Repository\UserRepository;
use App\Services\AdminService;
use App\Services\EmailService;
use App\Services\FacturaService;
use App\Validator\FacturaConstraints;
use Doctrine\ORM\EntityManagerInterface;
use Mpdf\Output\Destination;
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

class FacturiControllerTest extends WebTestCase
{
    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->adminService = $this->createMock(AdminService::class);
        $this->constraint = $this->createMock(FacturaConstraints::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->pdfService = $this->createMock(PdfService::class);
        $this->facturaService = $this->createMock(FacturaService::class);

        $this->controller = new FacturiController(
            $this->em,
            $this->translator,
            $this->authorizationChecker,
            $this->facturaService,
            $this->adminService,
            $this->validator,
            $this->constraint,
            $this->pdfService
        );

        $this->controllerMock = $this->getMockBuilder(FacturiController::class)
            ->setConstructorArgs([
                $this->em,
                $this->translator,
                $this->authorizationChecker,
                $this->facturaService,
                $this->adminService,
                $this->validator,
                $this->constraint,
                $this->pdfService])
            ->onlyMethods(['getUser'])
            ->getMock();

        $this->testUser = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('test@test.ro');

        $this->admin = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('ciprianmarta.cm@gmail.com');

        $this->repo = $this->createMock(FacturiRepository::class);

        $this->dbEm = $this->getContainer()->get(EntityManagerInterface::class);
        $this->dbEm->getConnection()->beginTransaction();

        $this->owner = new Owner();
        $this->owner->setDenumire('test');
        $this->owner->setSerieFactura('Ftest');
        $this->owner->setCui('443344446');
        $this->owner->setAdresa('Addr');
        $this->owner->setSters(false);
        $this->dbEm->persist($this->owner);

        $this->pacient = new Pacienti();
        $this->pacient->setNume('Pacient_Test');
        $this->pacient->setPrenume('Prenume_Test');
        $this->pacient->setCnp('1200606125999');
        $this->pacient->setTelefon('0711111111');
        $this->pacient->setAdresa('Addr');
        $this->pacient->setTara('Romania');
        $this->pacient->setCi('XB');
        $this->pacient->setCiEliberat('City');
        $this->pacient->setSters(false);
        $this->pacient->setDataInreg(new \DateTime());
        $this->dbEm->persist($this->pacient);

        $pret = $this->dbEm->getRepository(Preturi::class)->findAll()[0];

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
        $this->consultatie->setInchisa(true);
        $this->consultatie->setStearsa(false);
        $this->consultatie->setIncasata(true);
        $this->consultatie->setEvalPsiho('eval');
        $this->dbEm->persist($this->consultatie);

        $this->invoice = new Facturi();
        $this->invoice->setOwner($this->owner);
        $this->invoice->setPacient($this->pacient);
        $this->invoice->setSerie($this->owner->getSerieFactura());
        $this->invoice->setNumar(999);
        $this->invoice->setData(new \DateTime());
        $this->invoice->setScadenta(new \DateTime());
        $this->invoice->setTip(0);
        $this->dbEm->persist($this->invoice);

        $this->storno = new Facturi();
        $this->storno->setOwner($this->owner);
        $this->storno->setPacient($this->pacient);
        $this->storno->setSerie($this->owner->getSerieFactura());
        $this->storno->setNumar(1000);
        $this->storno->setData(new \DateTime());
        $this->storno->setScadenta(new \DateTime());
        $this->storno->setTip(1);
        $this->dbEm->persist($this->storno);

        $this->fc = new FacturaConsultatie();
        $this->fc->setFactura($this->invoice);
        $this->fc->setConsultatie($this->consultatie);
        $this->fc->setValoare(100);

        $this->invoice->addFacturaConsultatii($this->fc);
        $this->invoice->setStornare($this->storno);
        $this->dbEm->persist($this->fc);

        $this->dbEm->flush();
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
     * @covers \App\Controller\FacturiController::__construct
     */
    public function testItCanBuildController()
    {
        $this->assertInstanceOf(FacturiController::class, $this->controller);
    }

    /**
     * @covers \App\Controller\FacturiController::facturi
     */
    public function testRendersFacturiTemplateWithCorrectData(): void
    {
        $this->client->loginUser($this->admin, 'main');

        $this->client->request('GET', '/facturi');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('Facturi', $response->getContent());
    }

    /**
     * @covers \App\Controller\FacturiController::facturi
     */
    public function testRendersFacturisWithAccessDenied(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('VIEW', $this->isInstanceOf(Facturi::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->facturi();

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\FacturiController::list
     */
    public function testItCanFetchFacturiList()
    {
        $this->repo->method('getAllFacturi')->with(['filter'])->willReturn(['facturi']);

        $this->client->loginUser($this->admin, 'main');

        $this->client->request('GET', '/facturi/list');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('recordsFiltered', $response->getContent());
    }

    /**
     * @covers \App\Controller\FacturiController::salveazaFactura
     */
    public function testSaveWithAccessDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->salveazaFactura(new Request());

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\FacturiController::salveazaFactura
     */
    public function testSaveInvoiceCanShowValidationErrors(): void
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

        $this->client->loginUser($this->admin, 'main');

        $request = new Request([], ['form' => 'mockData']);

        $response = $this->controller->salveazaFactura($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $data['status']);
        $this->assertSame('Failed operation. Some validation error', $data['message']);
    }

    /**
     * @covers \App\Controller\FacturiController::salveazaFactura
     */
    public function testItCanSaveFactura()
    {
        $this->authorizationChecker->method('isGranted')->willReturn(true);

        $this->em->method('getRepository')
            ->with(Facturi::class)
            ->willReturn($this->repo);

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $this->client->loginUser($this->admin, 'main');

        $this->controllerMock->method('getUser')->willReturn($this->admin);

        $this->facturaService->method('prepareInvoice')->willReturn($this->invoice);

        $this->repo->method('saveInvoice')->with($this->invoice);

        $request = new Request([], ['form' => 'invoiceData']);

        $response = $this->controllerMock->salveazaFactura($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data['status']);
        $this->assertEquals('Successful operation', $data['message']);
    }

    /**
     * @covers \App\Controller\FacturiController::pdf
     */
    public function testCanGenerateInvoicePdf()
    {
        $this->authorizationChecker->method('isGranted')->willReturn(true);

        $this->client->loginUser($this->admin, 'main');

        $this->translator->method('trans')
            ->with('Successful operation')
            ->willReturn('Successful operation');

        $request = new Request([], ['factura_id' => '1']);

        $this->pdfService->expects($this->once())->method('printToPdf')
            ->with(
                1,
                'factura.html.twig',
                []);

        $response = $this->controller->pdf($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $data[0]);
    }

    /**
     * @covers \App\Controller\FacturiController::trimiteEmailFactura
     */
    public function testItCanSendEmailFail()
    {
        $this->pdfService->expects($this->once())->method('printToPdf')->with(
            $this->invoice->getId(),
            'factura.html.twig',
            ['destination' => Destination::STRING_RETURN]
        )->willReturn('invoicePDF');

        $emailService = $this->createMock(EmailService::class);
        $emailService->method('sendEmail')
            ->willReturn([
                'status' => 500,
                'message' => 'Email failed'
            ]);

        self::getContainer()->set(EmailService::class, $emailService);
        self::getContainer()->set(PdfService::class, $this->pdfService);

        $this->client->loginUser($this->admin, 'main');

        $this->client->request('POST', '/facturi/trimite_email_factura', [
            'id' => $this->invoice->getId(),
            'email' => 'test@test.ro',
        ]);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame(500, $data['status']);
        $this->assertSame('Email failed', $data['message']);
    }

    /**
     * @covers \App\Controller\FacturiController::trimiteEmailFactura
     */
    public function testItCanSendEmailSuccess()
    {
        $this->pdfService->expects($this->once())->method('printToPdf')->with(
            $this->invoice->getId(),
            'factura.html.twig',
            ['destination' => Destination::STRING_RETURN]
        )->willReturn('invoicePDF');

        $emailService = $this->createMock(EmailService::class);
        $emailService->method('sendEmail')
            ->willReturn([
                'status' => 200,
                'message' => 'Email sent successfully'
            ]);

        self::getContainer()->set(EmailService::class, $emailService);
        self::getContainer()->set(PdfService::class, $this->pdfService);

        $this->client->loginUser($this->admin, 'main');

        $this->client->request('POST', '/facturi/trimite_email_factura', [
            'id' => $this->invoice->getId(),
            'email' => 'test@test.ro',
        ]);

        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();

        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['status']);
        $this->assertSame('Email sent successfully', $data['message']);
    }

    /**
     * @covers \App\Controller\FacturiController::storneaza
     */
    public function testStorneazaWithAccessDenied()
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('ADD', $this->isInstanceOf(Facturi::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->storneaza(new Request());

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\FacturiController::storneaza
     */
    public function testStorneazaWithAlreadyCreditedResponse()
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('ADD', $this->isInstanceOf(Facturi::class))
            ->willReturn(true);

        $this->client->loginUser($this->admin, 'main');

        $this->client->request('POST', '/facturi/storneaza_factura', [
            'factura_id' => $this->invoice->getId()
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    /**
     * @covers \App\Controller\FacturiController::storneaza
     */
    public function testStorneazaWithSuccess()
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('ADD', $this->isInstanceOf(Facturi::class))
            ->willReturn(true);

        $this->client->loginUser($this->admin, 'main');

        $this->invoice->setStornare(null);
        $this->em->persist($this->invoice);

        $this->client->request('POST', '/facturi/storneaza_factura', [
            'factura_id' => $this->invoice->getId()
        ]);

        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * @covers \App\Controller\FacturiController::consultatiiNefacturate
     */
    public function testRendersConsultatiiNefacturateTemplateWithCorrectData(): void
    {
        $this->client->loginUser($this->admin, 'main');

        $this->client->request('GET', '/facturi/consultatii_nefacturate');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('Pacienti', $response->getContent());
    }

    /**
     * @covers \App\Controller\FacturiController::consultatiiNefacturate
     */
    public function testRendersConsultatiiNefacturateWithAccessDenied(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('VIEW', $this->isInstanceOf(Facturi::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->consultatiiNefacturate();

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\FacturiController::consultatiiNefacturateList
     */
    public function testRendersConsultatiiNefacturateListWithAccessDenied(): void
    {
        $this->authorizationChecker
            ->method('isGranted')
            ->with('VIEW', $this->isInstanceOf(Facturi::class))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($this->testUser, 'main');

        $this->controller->consultatiiNefacturateList(new Request());

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @covers \App\Controller\FacturiController::consultatiiNefacturateList
     */
    public function testItCanFetchConsultatiiNefacturateList()
    {
        $pacientiRepo = $this->createMock(PacientiRepository::class);

        $this->em->method('getRepository')->with(Pacienti::class)->willReturn($pacientiRepo);

        $this->authorizationChecker
            ->method('isGranted')
            ->with('VIEW', $this->isInstanceOf(Facturi::class))
            ->willReturn(true);

        $pacientiRepo->method('getPacientiConsultatiiNefacturate')->with(['filter'])->willReturn(['pacienti']);

        $this->client->loginUser($this->admin, 'main');

        $this->client->request('GET', '/facturi/consultatii_nefacturate_list');
        $response = $this->client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertStringContainsString('recordsFiltered', $response->getContent());
    }

    /**
     * @covers \App\Controller\FacturiController::consultatiiNefacturatePacient
     */
    public function testItCanGetConsultatiiNefacturatePacient()
    {
        $this->client->loginUser($this->admin, 'main');

        $this->client->request('GET', '/facturi/consultatii_nefacturate_pacient',
            ['pacient_id' => $this->pacient->getId()]
        );
        $response = $this->client->getResponse();
        $consultatii = json_decode($response->getContent(), true);

        $this->assertIsArray($consultatii['serviciiPacient']);
    }
}
