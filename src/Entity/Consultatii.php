<?php

namespace App\Entity;

use App\Repository\ConsultatiiRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConsultatiiRepository::class)]
#[ORM\Table(name: "consultatii")]
class Consultatii
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    public function getId(): ?int
    {
        return $this->id;
    }

    #[ORM\ManyToOne(targetEntity: "App\Entity\Pacienti", inversedBy: "consultatii")]
    #[ORM\JoinColumn(name: "pacient_id", referencedColumnName: "id", nullable: false)]
    private $pacient;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Preturi")]
    #[ORM\JoinColumn(name: "pret_id", referencedColumnName: "id", nullable: false)]
    private $pret;

    #[ORM\Column(type: "string", length: 3000, nullable: false)]
    private $diagnostic = "";

    #[ORM\Column(type: "string", length: 3000, nullable: false)]
    private $consultatie = "";

    #[ORM\Column(type: "string", length: 3000, nullable: false)]
    private $tratament = "";

    #[ORM\Column(type: "string", length: 1000, nullable: true)]
    private $ahc = "";

    #[ORM\Column(type: "string", length: 1000, nullable: true)]
    private $app = "";

    #[ORM\Column(type: "string", length: 10, nullable: false)]
    private $nrInreg = 0;

    #[ORM\Column(type: "datetime", nullable: false)]
    private $dataConsultatie;

    #[ORM\Column(type: "integer", nullable: false)]
    private $tarif;

    #[ORM\Column(type: "string", length: 1, nullable: false, options: ["comment" => "C-cabinet,D-domiciliu"])]
    private $loc = "C";

    #[ORM\Column(type: "integer", nullable: true, options: ["comment" => "nr. zile c.m."])]
    private $zileCm;

    #[ORM\Column(type: "string", length: 20, nullable: true, options: ["comment" => "certif. c.m."])]
    private $certifCm;

    #[ORM\Column(type: "boolean", nullable: false, options: ["default" => "0", "comment" => "0-deschisa,1-inchisa"])]
    private $inchisa = false;

    #[ORM\Column(type: "boolean", nullable: false, options: ["default" => "0", "comment" => "0-nestearsa,1-stearsa"])]
    private $stearsa = false;

    #[ORM\Column(type: "boolean", nullable: false, options: ["default" => "0", "comment" => "0-neincasata,1-incasata"])]
    private $incasata = false;

    #[ORM\Column(type: "boolean", nullable: false, options: ["after" => "incasata", "default" => "0", "comment" => "0-neplatita,1-platita"])]
    private $platitaColaborator = false;

    #[ORM\Column(type: "string", length: 30, nullable: true)]
    private $medicTrimitator;

    #[ORM\Column(type: "string", nullable: true)]
    private $investigatiiUrmate = '';

    #[ORM\Column(type: "string", nullable: true)]
    private $tratamenteUrmate = '';

    #[ORM\Column(type: "string", nullable: true)]
    private $observatii = '';

    #[ORM\Column(type: "string", length: 5000, nullable: true)]
    private $evalPsiho = '';

    #[ORM\ManyToOne(targetEntity: Programari::class, inversedBy: 'consultatii')]
    #[ORM\JoinColumn(name: "programare_id", referencedColumnName: "id", nullable: true)]
    private ?Programari $programare = null;

    /**
     * @var Collection<int, FacturaConsultatie>
     */
    #[ORM\OneToMany(mappedBy: 'consultatie', targetEntity: FacturaConsultatie::class)]
    private Collection $facturaConsultatii;

    public function __construct()
    {
        $this->facturaConsultatii = new ArrayCollection();
    }

    /**
     * @return bool
     */
    public function isPlatitaColaborator(): bool
    {
        return $this->platitaColaborator;
    }

    /**
     * @param bool $platita_colaborator
     */
    public function setPlatitaColaborator(bool $platita_colaborator): void
    {
        $this->platitaColaborator = $platita_colaborator;
    }

    public function getPacient()
    {
        return $this->pacient;
    }

    public function setPacient($pacient): void
    {
        $this->pacient = $pacient;
    }

    public function getDiagnostic(): string
    {
        return $this->diagnostic;
    }

    public function setDiagnostic(string $diagnostic): void
    {
        $this->diagnostic = $diagnostic;
    }

    public function getConsultatie(): string
    {
        return $this->consultatie;
    }

    public function setConsultatie(string $consultatie): void
    {
        $this->consultatie = $consultatie;
    }

    public function getTratament(): string
    {
        return $this->tratament;
    }

    public function setTratament(string $tratament): void
    {
        $this->tratament = $tratament;
    }

    public function getNrInreg(): string
    {
        return $this->nrInreg;
    }

    public function setNrInreg(string $nrInreg): void
    {
        $this->nrInreg = $nrInreg;
    }

    public function getDataConsultatie(): \DateTime
    {
        return $this->dataConsultatie;
    }

    public function setDataConsultatie(\DateTime $dataConsultatie): void
    {
        $this->dataConsultatie = $dataConsultatie;
    }

    public function getTarif()
    {
        return $this->tarif;
    }

    public function setTarif($tarif): void
    {
        $this->tarif = $tarif;
    }

    /**
     * @return mixed
     */
    public function getLoc()
    {
        return $this->loc;
    }

    /**
     * @param mixed $loc
     */
    public function setLoc($loc): void
    {
        $this->loc = $loc;
    }

    /**
     * @return mixed
     */
    public function getZileCm()
    {
        return $this->zileCm;
    }

    /**
     * @param mixed $zileCm
     */
    public function setZileCm($zileCm): void
    {
        $this->zileCm = $zileCm;
    }

    /**
     * @return mixed
     */
    public function getCertifCm()
    {
        return $this->certifCm;
    }

    /**
     * @param mixed $certifCm
     */
    public function setCertifCm($certifCm): void
    {
        $this->certifCm = $certifCm;
    }

    /**
     * @return mixed
     */
    public function getMedicTrimitator()
    {
        return $this->medicTrimitator;
    }

    /**
     * @param mixed $medicTrimitator
     */
    public function setMedicTrimitator($medicTrimitator): void
    {
        $this->medicTrimitator = $medicTrimitator;
    }

    /**
     * @return mixed
     */
    public function getInchisa()
    {
        return $this->inchisa;
    }

    /**
     * @param mixed $inchisa
     */
    public function setInchisa($inchisa): void
    {
        $this->inchisa = $inchisa;
    }

    /**
     * @return mixed
     */
    public function getPret()
    {
        return $this->pret;
    }

    /**
     * @param mixed $pret
     */
    public function setPret($pret): void
    {
        $this->pret = $pret;
    }

    /**
     * @return mixed
     */
    public function getStearsa()
    {
        return $this->stearsa;
    }

    /**
     * @param mixed $stearsa
     */
    public function setStearsa($stearsa): void
    {
        $this->stearsa = $stearsa;
    }

    /**
     * @return bool
     */
    public function isIncasata(): bool
    {
        return $this->incasata;
    }

    /**
     * @param bool $incasata
     */
    public function setIncasata(bool $incasata): void
    {
        $this->incasata = $incasata;
    }

    /**
     * @return mixed
     */
    public function getAhc()
    {
        return $this->ahc;
    }

    /**
     * @param mixed $ahc
     */
    public function setAhc($ahc): void
    {
        $this->ahc = $ahc;
    }

    /**
     * @return mixed
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * @param mixed $app
     */
    public function setApp($app): void
    {
        $this->app = $app;
    }

    public function getInvestigatiiUrmate()
    {
        return $this->investigatiiUrmate;
    }

    public function setInvestigatiiUrmate($investigatiiUrmate)
    {
        $this->investigatiiUrmate = $investigatiiUrmate;
    }

    public function getTratamenteUrmate()
    {
        return $this->tratamenteUrmate;
    }

    public function setTratamenteUrmate($tratamenteUrmate)
    {
        $this->tratamenteUrmate = $tratamenteUrmate;
    }

    public function getObservatii()
    {
        return $this->observatii;
    }

    public function setObservatii($observatii)
    {
        $this->observatii = $observatii;
    }

    public function getEvalPsiho()
    {
        return $this->evalPsiho;
    }

    public function setEvalPsiho($evalPsiho)
    {
        $this->evalPsiho = $evalPsiho;
    }

    public function getProgramare(): ?Programari
    {
        return $this->programare;
    }

    public function setProgramare(?Programari $programare): static
    {
        $this->programare = $programare;

        return $this;
    }

    /**
     * @return Collection<int, FacturaConsultatie>
     */
    public function getFacturaConsultatii(): Collection
    {
        return $this->facturaConsultatii;
    }

    public function addFacturaConsultatii(FacturaConsultatie $facturaConsultatii): static
    {
        if (!$this->facturaConsultatii->contains($facturaConsultatii)) {
            $this->facturaConsultatii->add($facturaConsultatii);
            $facturaConsultatii->setConsultatie($this);
        }

        return $this;
    }

    public function removeFacturaConsultatii(FacturaConsultatie $facturaConsultatii): static
    {
        if ($this->facturaConsultatii->removeElement($facturaConsultatii)) {
            // set the owning side to null (unless already changed)
            if ($facturaConsultatii->getConsultatie() === $this) {
                $facturaConsultatii->setConsultatie(null);
            }
        }

        return $this;
    }
}
