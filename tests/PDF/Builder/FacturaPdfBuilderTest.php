<?php

namespace App\Tests\PDF\Builder;

use App\Entity\Consultatii;
use App\Entity\FacturaConsultatie;
use App\Entity\Facturi;
use App\Entity\Owner;
use App\Entity\Pacienti;
use App\Entity\Preturi;
use App\PDF\Builder\FacturaPdfBuilder;
use App\PDF\DTO\PdfDocument;
use App\Repository\FacturiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FacturaPdfBuilderTest extends KernelTestCase
{
    public function setUp(): void
    {
        self::bootKernel();

        $this->em = $this->getContainer()->get(EntityManagerInterface::class);

        $pret = $this->em->getRepository(Preturi::class)->findAll()[0];
        $owner = $this->em->getRepository(Owner::class)->findAll()[0];

        $this->em->getConnection()->beginTransaction();

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
        $this->consultatie->setInchisa(false);
        $this->consultatie->setStearsa(false);
        $this->consultatie->setIncasata(false);
        $this->consultatie->setEvalPsiho('eval');
        $this->em->persist($this->consultatie);

        $this->invoice = new Facturi();
        $this->invoice->setOwner($owner);
        $this->invoice->setPacient($this->pacient);
        $this->invoice->setSerie($owner->getSerieFactura());
        $this->invoice->setNumar(9999);
        $this->invoice->setData(new \DateTime());
        $this->invoice->setScadenta(new \DateTime());
        $this->invoice->setTip(0);
        $this->em->persist($this->invoice);

        $this->storno = new Facturi();
        $this->storno->setOwner($owner);
        $this->storno->setPacient($this->pacient);
        $this->storno->setSerie($owner->getSerieFactura());
        $this->storno->setNumar(100001);
        $this->storno->setData(new \DateTime());
        $this->storno->setScadenta(new \DateTime());
        $this->storno->setTip(1);
        $this->em->persist($this->storno);

        $this->fc = new FacturaConsultatie();
        $this->fc->setFactura($this->invoice);
        $this->fc->setConsultatie($this->consultatie);
        $this->fc->setValoare(100);

        $this->invoice->addFacturaConsultatii($this->fc);
        $this->invoice->setStornare($this->storno);
        $this->em->persist($this->fc);

        $this->em->flush();

        $this->builder = new FacturaPdfBuilder($this->em);
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->rollBack();
        $this->em->close();

        parent::tearDown();
    }

    /**
     * @covers \App\PDF\Builder\FacturaPdfBuilder::__construct
     */
    public function testCanBuildBuilder()
    {
        $this->assertInstanceOf(FacturaPdfBuilder::class, $this->builder);
    }

    /**
     * @covers \App\PDF\Builder\FacturaPdfBuilder::getSupportedTemplates
     */
    public function testReturnsSupportedTemplate()
    {
        $template = $this->builder->getSupportedTemplates();

        $this->assertIsString($template);
        $this->assertSame('factura.html.twig', $template);
    }

    /**
     * @covers \App\PDF\Builder\FacturaPdfBuilder::build
     * @dataProvider typeProvider
     */
    public function testItCanBuildFactura($type)
    {
        $doc = $this->builder->build($type === 0 ? $this->invoice->getId() : $this->storno->getId());

        $this->assertInstanceOf(PDFDocument::class, $doc);
        $this->assertIsArray($doc->dateFirma);
        $this->assertIsString($doc->outputName);
    }

    private function typeProvider()
    {
        yield [0];
        yield [1];
    }
}
