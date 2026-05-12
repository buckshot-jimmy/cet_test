<?php

namespace App\Entity;

use App\Repository\FacturiRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "facturi")]
#[ORM\UniqueConstraint(
    name: "factura_data",
    columns: ["serie","numar","data"]
)]
#[ORM\Entity(repositoryClass: FacturiRepository::class)]
class Facturi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 5)]
    private ?string $serie = null;

    #[ORM\Column]
    private ?int $numar = null;

    #[ORM\Column(type: "date", nullable: false)]
    private ?\DateTimeInterface $data = null;

    #[ORM\Column(type: "date", nullable: true)]
    private ?\DateTimeInterface $scadenta = null;

    #[ORM\ManyToOne(inversedBy: 'facturi')]
    private ?Pacienti $pacient = null;

    #[ORM\ManyToOne(inversedBy: 'facturi')]
    private ?PersoaneJuridice $clientPj = null;

    #[ORM\Column(type: Types::SMALLINT, options: ["comment" => "0-normala,1-storno"])]
    private ?int $tip = null;

    #[ORM\OneToOne(targetEntity: Facturi::class)]
    #[ORM\JoinColumn(name: "stornare_id", referencedColumnName: "id", nullable: true,
        options: ["comment" => "stornata cu factura cu id sau storneaza factura cu id"])]
    private ?Facturi $stornare = null;

    #[ORM\ManyToOne(inversedBy: 'facturi')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Owner $owner = null;

    /**
     * @var Collection<int, FacturaConsultatie>
     */
    #[ORM\OneToMany(mappedBy: 'factura', targetEntity: FacturaConsultatie::class)]
    private Collection $facturaConsultatii;

    public function __construct()
    {
        $this->facturaConsultatii = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSerie(): ?string
    {
        return $this->serie;
    }

    public function setSerie(string $serie): static
    {
        $this->serie = $serie;

        return $this;
    }

    public function getNumar(): ?int
    {
        return $this->numar;
    }

    public function setNumar(int $numar): static
    {
        $this->numar = $numar;

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

    public function getScadenta(): \DateTimeInterface
    {
        return $this->scadenta;
    }

    public function setScadenta(?\DateTime $scadenta): static
    {
        $this->scadenta = $scadenta;

        return $this;
    }

    public function getPacient(): ?Pacienti
    {
        return $this->pacient;
    }

    public function setPacient(?Pacienti $pacient): static
    {
        $this->pacient = $pacient;

        return $this;
    }

    public function getClientPj(): ?PersoaneJuridice
    {
        return $this->clientPj;
    }

    public function setClientPj(?PersoaneJuridice $clientPj): static
    {
        $this->clientPj = $clientPj;

        return $this;
    }

    public function getTip(): ?int
    {
        return $this->tip;
    }

    public function setTip(int $tip): static
    {
        $this->tip = $tip;

        return $this;
    }

    public function getStornare(): ?self
    {
        return $this->stornare;
    }

    public function setStornare(?self $stornare): static
    {
        $this->stornare = $stornare;

        return $this;
    }

    public function getOwner(): ?Owner
    {
        return $this->owner;
    }

    public function setOwner(?Owner $owner): static
    {
        $this->owner = $owner;

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
            $facturaConsultatii->setFactura($this);
        }

        return $this;
    }

    public function removeFacturaConsultatii(FacturaConsultatie $facturaConsultatii): static
    {
        if ($this->facturaConsultatii->removeElement($facturaConsultatii)) {
            // set the owning side to null (unless already changed)
            if ($facturaConsultatii->getFactura() === $this) {
                $facturaConsultatii->setFactura(null);
            }
        }

        return $this;
    }
}
