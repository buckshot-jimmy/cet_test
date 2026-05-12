<?php

namespace App\Tests\Services;

use App\Entity\Consultatii;
use App\Entity\Facturi;
use App\Entity\Owner;
use App\Entity\Pacienti;
use App\Entity\PersoaneJuridice;
use App\Entity\Preturi;
use App\Entity\Role;
use App\Entity\Servicii;
use App\Entity\User;
use App\Services\FacturaService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FacturaServiceTest extends KernelTestCase
{
    public function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $registry = $container->get(ManagerRegistry::class);
        $this->em = $registry->getManager();

        $this->service = new FacturaService($this->em);

        $this->em->getConnection()->beginTransaction();

        $this->owner = new Owner();
        $this->owner->setSters(false);
        $this->owner->setCui(00000001);
        $this->owner->setDenumire('MyOwn');
        $this->owner->setAdresa('Addr');
        $this->owner->setSerieFactura('Fact');
        $this->em->persist($this->owner);

        $this->pacient = new Pacienti();
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

        $this->pj = new PersoaneJuridice();
        $this->pj->setDenumire('test11');
        $this->pj->setCui('443333445');
        $this->pj->setAdresa('Addr22');
        $this->pj->setSters(false);
        $this->em->persist($this->pj);

        $this->serviciu = (new Servicii())->setDenumire('Consult_Test');
        $this->serviciu->setTip(0);
        $this->serviciu->setSters(false);
        $this->em->persist($this->serviciu);

        $this->role = (new Role())->setDenumire('ROLE_TestMedic');
        $this->em->persist($this->role);

        $this->medic = (new User())
            ->setUsername('test_medic')
            ->setPassword('test_password_hash')
            ->setNume('Nume_Test')
            ->setPrenume('Prenume_Test')
            ->setTelefon('0700000000')
            ->setRole($this->role);
        $this->medic->setSters(false);
        $this->medic->setParolaSchimbata(false);
        $this->em->persist($this->medic);

        $this->pret = new Preturi();
        $this->pret->setMedic($this->medic);
        $this->pret->setOwner($this->owner);
        $this->pret->setServiciu($this->serviciu);
        $this->pret->setPret(100);
        $this->pret->setProcentajMedic(50);
        $this->pret->setSters(false);
        $this->pret->setCotaTva(0);
        $this->em->persist($this->pret);

        $this->consultatie = new Consultatii();
        $this->consultatie->setPret($this->pret);
        $this->consultatie->setPacient($this->pacient);
        $this->consultatie->setDiagnostic('diag');
        $this->consultatie->setConsultatie('cons');
        $this->consultatie->setTratament('trat');
        $this->consultatie->setNrInreg('1');
        $this->consultatie->setDataConsultatie(new \DateTime());
        $this->consultatie->setTarif(100);
        $this->consultatie->setLoc('C');
        $this->consultatie->setInchisa(false);
        $this->consultatie->setStearsa(false);
        $this->consultatie->setIncasata(false);
        $this->consultatie->setEvalPsiho('eval');
        $this->em->persist($this->consultatie);

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
     * @covers \App\Services\FacturaService::__construct
     */
    public function testItCanBuildService()
    {
        $this->assertInstanceOf(FacturaService::class, $this->service);
    }

    /**
     * @covers \App\Services\FacturaService::prepareInvoice
     */
    public function testItCanPrepareInvoice()
    {
        $result = $this->service->prepareInvoice([
            'owner_factura' => $this->owner->getId(),
            'factura_pacient' => $this->pacient->getId(),
            'factura_pj' => null,
            'consultatii_factura' => implode([$this->consultatie->getId()])
        ]);

        $this->assertInstanceOf(Facturi::class, $result);
        $this->assertSame($result->getOwner(), $this->owner);

        $result = $this->service->prepareInvoice([
            'owner_factura' => $this->owner->getId(),
            'factura_pacient' => null,
            'factura_pj' => $this->pj->getId(),
            'consultatii_factura' => implode([$this->consultatie->getId()])
        ]);

        $this->assertInstanceOf(Facturi::class, $result);
        $this->assertSame($result->getClientPj()->getId(), $this->pj->getId());
    }
}
