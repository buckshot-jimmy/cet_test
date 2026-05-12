<?php

namespace App\Tests\PDF\Builder;

use App\Entity\Consultatii;
use App\Entity\Owner;
use App\Entity\Pacienti;
use App\Entity\Preturi;
use App\Entity\RapoarteColaboratori;
use App\Entity\Role;
use App\Entity\Servicii;
use App\Entity\User;
use App\PDF\Builder\RaportColaboratorPdfBuilder;
use App\PDF\DTO\PdfDocument;
use App\Repository\RapoarteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RaportColaboratorPdfBuilderTest extends KernelTestCase
{
    public function setUp(): void
    {
        self::bootKernel();

        $this->em = $this->getContainer()->get(EntityManagerInterface::class);

        $this->em->getConnection()->beginTransaction();

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

        $this->owner = (new Owner())->setDenumire('Owner')->setCui('12345678');
        $this->owner->setSters(false);
        $this->owner->setAdresa('Addr');
        $this->owner->setSerieFactura('F');
        $this->em->persist($this->owner);

        $this->pret = new Preturi();
        $this->pret->setMedic($this->medic);
        $this->pret->setOwner($this->owner);
        $this->pret->setServiciu($this->serviciu);
        $this->pret->setPret(100);
        $this->pret->setProcentajMedic(50);
        $this->pret->setSters(false);
        $this->pret->setCotaTva(0);
        $this->em->persist($this->pret);

        $this->pacient = new Pacienti();
        $this->pacient->setNume('Pacient_Test');
        $this->pacient->setPrenume('Prenume_Test');
        $this->pacient->setCnp('1790630060770');
        $this->pacient->setTelefon('0711111111');
        $this->pacient->setAdresa('Addr');
        $this->pacient->setTara('Romania');
        $this->pacient->setCi('XB');
        $this->pacient->setCiEliberat('City');
        $this->pacient->setSters(false);
        $this->pacient->setDataInreg(new \DateTime());
        $this->em->persist($this->pacient);

        $date = new \DateTime('2026-01-01');

        $this->consultatie = new Consultatii();
        $this->consultatie->setPret($this->pret);
        $this->consultatie->setPacient($this->pacient);
        $this->consultatie->setDiagnostic('diag');
        $this->consultatie->setConsultatie('cons');
        $this->consultatie->setTratament('trat');
        $this->consultatie->setNrInreg('1');
        $this->consultatie->setDataConsultatie($date);
        $this->consultatie->setTarif(200);
        $this->consultatie->setLoc('C');
        $this->consultatie->setInchisa(true);
        $this->consultatie->setStearsa(false);
        $this->consultatie->setIncasata(true);
        $this->consultatie->setEvalPsiho('eval');
        $this->em->persist($this->consultatie);

        $this->raport = new RapoarteColaboratori();
        $this->raport->setDataGenerarii($date);
        $this->raport->setSuma(200);
        $this->raport->setMedic($this->medic);
        $this->raport->setOwner($this->owner);
        $this->raport->setLuna('Ianuarie');
        $this->raport->setAn($date->format('Y'));
        $this->raport->setStare(RapoarteRepository::STARE_NEPLATITA);
        $this->em->persist($this->raport);

        $this->em->flush();
        $this->em->clear();

        $this->builder = new RaportColaboratorPdfBuilder($this->em);
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->rollBack();
        $this->em->close();

        parent::tearDown();
    }

    /**
     * @covers \App\PDF\Builder\RaportColaboratorPdfBuilder::__construct
     */
    public function testCanBuildBuilder()
    {
        $this->assertInstanceOf(RaportColaboratorPdfBuilder::class, $this->builder);
    }

    /**
     * @covers \App\PDF\Builder\RaportColaboratorPdfBuilder::getSupportedTemplates
     */
    public function testReturnsSupportedTemplate()
    {
        $template = $this->builder->getSupportedTemplates();

        $this->assertIsString($template);
        $this->assertSame('plata_colaborator.html.twig', $template);
    }

    /**
     * @covers \App\PDF\Builder\RaportColaboratorPdfBuilder::build
     */
    public function testItCanBuildRaportColaborator()
    {
        $doc = $this->builder->build($this->raport->getId());

        $this->assertInstanceOf(PDFDocument::class, $doc);
        $this->assertIsArray($doc->dateFirma);
        $this->assertIsString($doc->outputName);
    }
}
