<?php

namespace App\Tests\PDF\Builder;

use App\Entity\Consultatii;
use App\Entity\Pacienti;
use App\Entity\Preturi;
use App\PDF\Builder\BuletinInvestigatiePdfBuilder;
use App\PDF\DTO\PdfDocument;
use App\Repository\ConsultatiiRepository;
use App\Repository\PacientiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BuletinInvestigatiePdfBuilderTest extends KernelTestCase
{
    public function setUp(): void
    {
        self::bootKernel();

        $this->em = $this->getContainer()->get(EntityManagerInterface::class);

        $pret = $this->em->getRepository(Preturi::class)->findAll()[0];

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

        $this->em->flush();

        $this->builder = new BuletinInvestigatiePdfBuilder($this->em);
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->rollBack();
        $this->em->close();

        parent::tearDown();
    }

    /**
     * @covers \App\PDF\Builder\BuletinInvestigatiePdfBuilder::__construct
     */
    public function testCanBuildBuilder()
    {
        $this->assertInstanceOf(BuletinInvestigatiePdfBuilder::class, $this->builder);
    }

    /**
     * @covers \App\PDF\Builder\BuletinInvestigatiePdfBuilder::getSupportedTemplates
     */
    public function testReturnsSupportedTemplate()
    {
        $template = $this->builder->getSupportedTemplates();

        $this->assertIsString($template);
        $this->assertSame('buletin_investigatie.html.twig', $template);
    }

    /**
     * @covers \App\PDF\Builder\BuletinInvestigatiePdfBuilder::build
     */
    public function testItCanBuildBuletinInvestigatie()
    {
        $doc = $this->builder->build($this->consultatie->getId());

        $this->assertInstanceOf(PDFDocument::class, $doc);
        $this->assertIsArray($doc->dateFirma);
        $this->assertIsString($doc->outputName);
    }
}
