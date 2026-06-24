<?php

namespace App\Tests\Services;

use App\Entity\Pacient;
use App\Form\PacientFormType;
use App\Repository\PacientRepository;
use App\Services\NomenclatoareService;
use App\Services\PacientService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class PacientServiceTest extends KernelTestCase
{
    public function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $registry = $container->get(ManagerRegistry::class);
        $this->em = $registry->getManager();

        $this->factory = $this->createMock(FormFactoryInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->emMock = $this->createMock(EntityManagerInterface::class);
        $this->pacientRepo = $this->createMock(PacientRepository::class);

        $this->service = new PacientService($this->factory, $this->translator, $this->emMock);

        $this->em->getConnection()->beginTransaction();

        $this->pacient = new Pacient();
        $this->pacient->setNume('Pacient_Test');
        $this->pacient->setPrenume('Prenume_Test');
        $this->pacient->setCnp('1891022414121');
        $this->pacient->setTelefon('0711111111');
        $this->pacient->setAdresa('Addr');
        $this->pacient->setTara('Romania');
        $this->pacient->setCi('XB');
        $this->pacient->setCiEliberat('City');
        $this->pacient->setSters(false);
        $this->pacient->setDataInreg(new \DateTime());
        $this->em->persist($this->pacient);
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        try {
            if (isset($this->em) && null !== $this->em) {
                $conn = $this->em->getConnection();

                if ($conn->isTransactionActive()) {
                    $conn->rollBack();
                }

                $this->em->clear();
                $this->em->close();
            }
        } finally {
            $this->em = null;
            $this->service = null;

            self::ensureKernelShutdown();
            parent::tearDown();
        }
    }

    /**
     * @covers \App\Services\PacientService::__construct
     */
    public function testItCanBuildService()
    {
        $this->assertInstanceOf(PacientService::class, $this->service);
    }

    /**
     * @covers \App\Services\PacientService::getPacientForRequest
     * @covers \App\Services\PacientService::getInitializedPacient
     */
    public function testCanGetPatientForRequestWithNew()
    {
        $result = $this->service->getPacientForRequest(new Request([], ['no' => 1]));

        $this->assertInstanceOf(Pacient::class, $result);
        $this->assertEquals($result->getNume(), '');
    }

    /**
     * @covers \App\Services\PacientService::getPacientForRequest
     * @covers \App\Services\PacientService::getInitializedPacient
     */
    public function testCanGetPatientForRequestWithException()
    {
        $this->expectException(\Exception::class);
        $this->translator->method('trans')->with('Missing ID')->willReturn('Negasit');

        $this->emMock->method('getRepository')->with(Pacient::class)->willReturn($this->pacientRepo);
        $this->pacientRepo->method('find')->with($this->pacient->getId())->willReturn(null);

        $this->service->getPacientForRequest(new Request([], ['pacient_id' => $this->pacient->getId()]));

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(4001);
        $this->expectExceptionMessage('Negasit');
    }

    /**
     * @covers \App\Services\PacientService::getPacientForRequest
     * @covers \App\Services\PacientService::getInitializedPacient
     */
    public function testCanGetPatientForRequest()
    {
        $this->emMock->method('getRepository')->with(Pacient::class)->willReturn($this->pacientRepo);
        $this->pacientRepo->method('find')->with($this->pacient->getId())->willReturn($this->pacient);

        $result = $this->service->getPacientForRequest(new Request([], ['pacient_id' => $this->pacient->getId()]));

        $this->assertInstanceOf(Pacient::class, $result);
    }

    /**
     * @covers \App\Services\PacientService::getPacientForData
     * @covers \App\Services\PacientService::getInitializedPacient
     */
    public function testGetPatientForDataEmpty()
    {
        $this->assertInstanceOf(Pacient::class, $this->service->getPacientForData([]));
    }

    /**
     * @covers \App\Services\PacientService::getPacientForData
     * @covers \App\Services\PacientService::getInitializedPacient
     */
    public function testGetPatientForData()
    {
        $data = [
          'nume' => 'Test',
          'prenume' => 'Test',
          'cnp' => '123',
          'telefon' => '123',
          'adresa' => 'Test',
          'tara' => 'Romania',
        ];

        $this->assertInstanceOf(Pacient::class, $this->service->getPacientForData($data));
        $this->assertEquals($this->service->getPacientForData($data)->getNume(), 'Test');
    }

    /**
     * @covers \App\Services\PacientService::createPacientForm
     */
    public function testItCanCreatePatientForm()
    {
        $nomenclatoare = $this->createMock(NomenclatoareService::class);
        $nomenclatoare->method('getTari')->willReturn(['Romania']);
        $nomenclatoare->method('getJudete')->willReturn(['Cluj']);
        $nomenclatoare->method('getStariCivile')->willReturn(['single' => 'Single']);

        $this->factory->expects($this->once())
            ->method('createNamed')
            ->with(
                '',
                PacientFormType::class,
                $this->pacient,
                $this->callback(function ($options) {
                    return $options['tari'] === ['Romania']
                        && $options['judete'] === ['Cluj']
                        && $options['stariCivile'] === ['single' => 'Single']
                        && $options['varsta'] === 123
                        && $options['pacient_id'] === $this->pacient->getId();
                })
            )
            ->willReturn($this->createMock(FormInterface::class));

        $result = $this->service->createPacientForm($this->pacient, $nomenclatoare, 123);

        $this->assertInstanceOf(FormInterface::class, $result);
    }

    /**
     * @covers \App\Services\PacientService::buildFormValidationErrors
     */
    public function testCanBuildValidationErrors()
    {
        $form = $this->createMock(Form::class);

        $error1 = new FormError('Data collection error');
        $error2 = new FormError('Failed operation');

        $this->translator->expects($this->any())->method('trans')->willReturnOnConsecutiveCalls(
          'Eroare la preluarea datelor.',
            'Operatiune esuata.'
        );

        $errors = new FormErrorIterator($form, [$error1, $error2]);

        $result = $this->service->buildFormValidationErrors($errors);

        $this->assertSame(' ' . 'Eroare la preluarea datelor. Operatiune esuata.', $result);
    }
}
