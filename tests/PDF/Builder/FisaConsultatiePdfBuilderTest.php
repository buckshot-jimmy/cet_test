<?php

namespace App\Tests\PDF\Builder;

use App\Entity\Consultatii;
use App\Entity\Pacienti;
use App\Entity\Preturi;
use App\PDF\Builder\FisaConsultatiePdfBuilder;
use App\PDF\DTO\PdfDocument;
use App\Repository\ConsultatiiRepository;
use App\Repository\PacientiRepository;
use App\Services\NomenclatoareService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FisaConsultatiePdfBuilderTest extends KernelTestCase
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
        $this->pacient->setStareCivila(0);
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

        $this->builder = new FisaConsultatiePdfBuilder($this->em);
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->rollBack();
        $this->em->close();

        parent::tearDown();
    }

    /**
     * @covers \App\PDF\Builder\FisaConsultatiePdfBuilder::__construct
     */
    public function testCanBuildBuilder()
    {
        $this->assertInstanceOf(FisaConsultatiePdfBuilder::class, $this->builder);
    }

    /**
     * @covers \App\PDF\Builder\FisaConsultatiePdfBuilder::getSupportedTemplates
     */
    public function testReturnsSupportedTemplate()
    {
        $template = $this->builder->getSupportedTemplates();

        $this->assertIsString($template);
        $this->assertSame('fisa_consultatii.html.twig', $template);
    }

    /**
     * @covers \App\PDF\Builder\FisaConsultatiePdfBuilder::build
     */
    public function testItCanBuildFisaConsultatie()
    {
        $doc = $this->builder->build($this->consultatie->getId());

        $this->assertInstanceOf(PDFDocument::class, $doc);
        $this->assertIsArray($doc->dateFirma);
        $this->assertIsString($doc->outputName);
    }
}
