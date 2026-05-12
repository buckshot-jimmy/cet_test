<?php

namespace App\Tests\PDF\Builder;

use App\Entity\Consultatii;
use App\Entity\Pacienti;
use App\Entity\Preturi;
use App\PDF\Builder\FisaPsihodiagnosticPdfBuilder;
use App\PDF\DTO\PdfDocument;
use App\Repository\ConsultatiiRepository;
use App\Repository\PacientiRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FisaPsihodiagnosticPdfBuilderTest extends KernelTestCase
{
    public function setUp(): void
    {
        self::bootKernel();

        $this->em = $this->getContainer()->get(EntityManagerInterface::class);

        $this->pret = $this->em->getRepository(Preturi::class)->findAll()[0];

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

        $this->evaluare = new Consultatii();
        $this->evaluare->setPret($this->pret);
        $this->evaluare->setPacient($this->pacient);
        $this->evaluare->setDiagnostic('diag');
        $this->evaluare->setConsultatie('cons');
        $this->evaluare->setTratament('trat');
        $this->evaluare->setNrInreg('1');
        $this->evaluare->setDataConsultatie(new \DateTime());
        $this->evaluare->setTarif(100);
        $this->evaluare->setLoc('C');
        $this->evaluare->setInchisa(false);
        $this->evaluare->setStearsa(false);
        $this->evaluare->setIncasata(false);
        $this->evaluare->setEvalPsiho(serialize('eval'));
        $this->em->persist($this->evaluare);

        $this->em->flush();

        $this->builder = new FisaPsihodiagnosticPdfBuilder($this->em);
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->rollBack();
        $this->em->close();

        parent::tearDown();
    }

    /**
     * @covers \App\PDF\Builder\FisaPsihodiagnosticPdfBuilder::__construct
     */
    public function testCanBuildBuilder()
    {
        $this->assertInstanceOf(FisaPsihodiagnosticPdfBuilder::class, $this->builder);
    }

    /**
     * @covers \App\PDF\Builder\FisaPsihodiagnosticPdfBuilder::getSupportedTemplates
     */
    public function testReturnsSupportedTemplate()
    {
        $template = $this->builder->getSupportedTemplates();

        $this->assertIsString($template);
        $this->assertSame('fisa_psihodiagnostic.html.twig', $template);
    }

    /**
     * @covers \App\PDF\Builder\FisaPsihodiagnosticPdfBuilder::build
     */
    public function testItCanBuildFisaPsihodiagnostic()
    {
        $doc = $this->builder->build($this->evaluare->getId());

        $this->assertInstanceOf(PDFDocument::class, $doc);
        $this->assertIsArray($doc->dateFirma);
        $this->assertIsString($doc->outputName);
    }
}
