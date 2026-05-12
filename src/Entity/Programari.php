<?php

namespace App\Entity;

use App\Repository\ProgramariRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\TimeImmutableType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "programari")]
#[ORM\UniqueConstraint(
    name: "medic_programare",
    columns: ["pret_id","data","ora"]
)]
#[ORM\Entity(repositoryClass: ProgramariRepository::class)]
class Programari
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Pacienti::class, inversedBy: "programari")]
    #[ORM\JoinColumn(name: "pacient_id", referencedColumnName: "id", nullable: false)]
    private ?Pacienti $pacient = null;

    #[ORM\ManyToOne(targetEntity: Preturi::class)]
    #[ORM\JoinColumn(name: "pret_id", referencedColumnName: "id", nullable: false)]
    private ?Preturi $pret = null;

    #[ORM\Column(type: "date", nullable: false)]
    private ?\DateTimeInterface $data = null;

    #[ORM\Column(type: "time", nullable: false)]
    private ?\DateTimeInterface $ora = null;

    #[ORM\Column(type: "boolean", nullable: false, options: ["default" => "0", "comment" => "1-anulata"])]
    private bool $anulata = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "adaugata_de", referencedColumnName: "id", nullable: false)]
    private ?User $adaugataDe = null;

    /**
     * @var Collection<int, Consultatii>
     */
    #[ORM\OneToMany(mappedBy: 'programare', targetEntity: Consultatii::class)]
    private Collection $consultatii;

    public function __construct()
    {
        $this->consultatii = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPacient()
    {
        return $this->pacient;
    }

    public function setPacient($pacient): void
    {
        $this->pacient = $pacient;
    }

    public function getPret(): ?Preturi
    {
        return $this->pret;
    }

    public function setPret(Preturi $pret): static
    {
        $this->pret = $pret;

        return $this;
    }

    public function getData(): \DateTimeInterface
    {
        return $this->data;
    }

    public function setData(\DateTime $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getOra()
    {
        return $this->ora;
    }

    public function setOra($ora)
    {
        $this->ora = $ora;

        return $this;
    }

    public function getAnulata(): ?int
    {
        return $this->anulata;
    }

    public function setAnulata(int $anulata): static
    {
        $this->anulata = $anulata;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAdaugataDe(): ?User
    {
        return $this->adaugataDe;
    }

    /**
     * @param mixed $adaugataDe
     */
    public function setAdaugataDe(?User $adaugataDe): void
    {
        $this->adaugataDe = $adaugataDe;
    }

    /**
     * @return Collection<int, Consultatii>
     */
    public function getConsultatii(): Collection
    {
        return $this->consultatii;
    }

    public function addConsultatii(Consultatii $consultatii): static
    {
        if (!$this->consultatii->contains($consultatii)) {
            $this->consultatii->add($consultatii);
            $consultatii->setProgramare($this);
        }

        return $this;
    }

    public function removeConsultatii(Consultatii $consultatii): static
    {
        if ($this->consultatii->removeElement($consultatii)) {
            if ($consultatii->getProgramare() === $this) {
                $consultatii->setProgramare(null);
            }
        }

        return $this;
    }
}
