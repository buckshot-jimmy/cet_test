<?php

namespace App\Entity;

use App\Repository\OwnerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "owner")]
#[ORM\Entity(repositoryClass: OwnerRepository::class)]
class Owner
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 100, unique: true)]
    private $denumire;

    #[ORM\Column(type: "string", length: 10, unique: true)]
    private $cui;

    #[ORM\Column(type: "boolean", nullable: false, options: ["default" => "0", "comment" => "0-nesters,1-sters"])]
    private $sters;

    #[ORM\Column(length: 255)]
    private ?string $adresa = null;

    #[ORM\Column(length: 34, nullable: true)]
    private ?string $contBancar = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $banca = null;

    #[ORM\Column(length: 10)]
    private ?string $serieFactura = null;

    #[ORM\Column(length: 25, nullable: true)]
    private ?string $regCom = null;

    #[ORM\Column(nullable: true)]
    private ?int $capitalSocial = null;

    /**
     * @var Collection<int, Facturi>
     */
    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Facturi::class)]
    private Collection $facturi;

    public function __construct()
    {
        $this->facturi = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDenumire(): ?string
    {
        return $this->denumire;
    }

    public function setDenumire(string $denumire): self
    {
        $this->denumire = $denumire;

        return $this;
    }

    public function getCui(): ?string
    {
        return $this->cui;
    }

    public function setCui(string $cui): self
    {
        $this->cui = $cui;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSters()
    {
        return $this->sters;
    }

    /**
     * @param mixed $sters
     */
    public function setSters($sters): void
    {
        $this->sters = $sters;
    }

    public function getAdresa(): ?string
    {
        return $this->adresa;
    }

    public function setAdresa(string $adresa): static
    {
        $this->adresa = $adresa;

        return $this;
    }

    public function getContBancar(): ?string
    {
        return $this->contBancar;
    }

    public function setContBancar(string $cont_bancar): static
    {
        $this->contBancar = $cont_bancar;

        return $this;
    }

    public function getBanca(): ?string
    {
        return $this->banca;
    }

    public function setBanca(string $banca): static
    {
        $this->banca = $banca;

        return $this;
    }

    public function getSerieFactura(): ?string
    {
        return $this->serieFactura;
    }

    public function setSerieFactura(string $serie_factura): static
    {
        $this->serieFactura = $serie_factura;

        return $this;
    }

    public function getRegCom(): ?string
    {
        return $this->regCom;
    }

    public function setRegCom(?string $regCom): static
    {
        $this->regCom = $regCom;

        return $this;
    }

    public function getCapitalSocial(): ?int
    {
        return $this->capitalSocial;
    }

    public function setCapitalSocial(int $capitalSocial): static
    {
        $this->capitalSocial = $capitalSocial;

        return $this;
    }

    /**
     * @return Collection<int, Facturi>
     */
    public function getFacturi(): Collection
    {
        return $this->facturi;
    }

    public function addFacturi(Facturi $facturi): static
    {
        if (!$this->facturi->contains($facturi)) {
            $this->facturi->add($facturi);
            $facturi->setOwner($this);
        }

        return $this;
    }

    public function removeFacturi(Facturi $facturi): static
    {
        if ($this->facturi->removeElement($facturi)) {
            // set the owning side to null (unless already changed)
            if ($facturi->getOwner() === $this) {
                $facturi->setOwner(null);
            }
        }

        return $this;
    }
}
