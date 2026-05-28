<?php

namespace App\Tests\Services;

use App\Entity\Consultatie;
use App\Entity\Owner;
use App\Entity\Pacient;
use App\Entity\Pret;
use App\Entity\Role;
use App\Entity\Serviciu;
use App\Entity\User;
use App\Services\ConsultatieService;
use App\Services\NomenclatoareService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ConsultatieServiceTest extends KernelTestCase
{
    public function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->em = $container->get('doctrine.orm.entity_manager');
        $nomenclatoare = new NomenclatoareService();
        
        $this->service = new ConsultatieService($this->em, $nomenclatoare);

        $this->em->getConnection()->beginTransaction();

        $role = (new Role())->setDenumire('ROLE_TestMedic');
        $this->em->persist($role);

        $medic = (new User())
            ->setUsername('test_medic')
            ->setPassword('test_password_hash')
            ->setNume('Nume_Test')
            ->setPrenume('Prenume_Test')
            ->setTelefon('0700000000')
            ->setRole($role);
        $medic->setSters(false);
        $medic->setParolaSchimbata(false);
        $this->em->persist($medic);

        $owner = (new Owner())->setDenumire('Owner')->setCui('12345678');
        $owner->setSters(false);
        $owner->setAdresa('Addr00');
        $owner->setSerieFactura('Ftest11');
        $this->em->persist($owner);

        $serviciu = (new Serviciu())->setDenumire('Consult_Test');
        $serviciu->setTip(0);
        $serviciu->setSters(false);
        $this->em->persist($serviciu);

        $this->pret = new Pret();
        $this->pret->setMedic($medic);
        $this->pret->setOwner($owner);
        $this->pret->setServiciu($serviciu);
        $this->pret->setPret(100);
        $this->pret->setProcentajMedic(50);
        $this->pret->setSters(false);
        $this->pret->setCotaTva(0);
        $this->em->persist($this->pret);

        $pacient = new Pacient();
        $pacient->setNume('Pacient_Test');
        $pacient->setPrenume('Prenume_Test');
        $pacient->setCnp('1234567890122');
        $pacient->setTelefon('0711111111');
        $pacient->setAdresa('Addr');
        $pacient->setTara('Romania');
        $pacient->setSters(false);
        $pacient->setDataInreg(new \DateTime());
        $this->em->persist($pacient);

        $consultatie = new Consultatie();
        $consultatie->setPret($this->pret);
        $consultatie->setPacient($pacient);
        $consultatie->setDiagnostic('diag');
        $consultatie->setConsultatie('cons');
        $consultatie->setTratament('trat');
        $consultatie->setNrInreg('1');
        $consultatie->setDataConsultatie(new \DateTime());
        $consultatie->setTarif(100);
        $consultatie->setLoc('C');
        $consultatie->setInchisa(true);
        $consultatie->setStearsa(false);
        $consultatie->setIncasata(true);
        $consultatie->setEvalPsiho('eval');
        $this->em->persist($consultatie);

        $this->em->flush();
        $this->em->clear();
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
     * @covers \App\Services\ConsultatieService::__construct
     */
    public function testItCanBuildService()
    {
        $this->assertInstanceOf(ConsultatieService::class, $this->service);
    }

    /**
     * @covers \App\Services\ConsultatieService::calculeazaConsultatiiPeLuni
     */
    public function testItCancalculeazaConsultatiiPeLuni()
    {
        $result = $this->service->calculeazaConsultatiiPeLuni($this->pret->getMedic());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('consultatiiMedic', $result);
        $this->assertArrayHasKey('consultatii', $result);
        $this->assertSame(1, end($result['consultatiiMedic']['consultatii']));
    }

    /**
     * @covers \App\Services\ConsultatieService::calculeazaIncasariMedicPeLuni
     */
    public function testItCalculeazaIncasariMedicPeLuni()
    {
        $result = $this->service->calculeazaIncasariMedicPeLuni($this->pret->getMedic());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('incasari', $result);
        $this->assertArrayHasKey('comision', $result);
        $this->assertSame(100, intval(end($result['incasari'])));
        $this->assertSame(50, intval(end($result['comision'])));
    }
}
