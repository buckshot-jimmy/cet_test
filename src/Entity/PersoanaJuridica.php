<?php

namespace App\Entity;

use App\Repository\PersoanaJuridicaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "persoane_juridice")]
#[ORM\Entity(repositoryClass: PersoanaJuridicaRepository::class)]
class PersoanaJuridica
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, nullable: false, unique: true)]
    private ?string $denumire = null;

    #[ORM\Column(length: 10, nullable: false, unique: true)]
    private ?string $cui = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $adresa = null;

    #[ORM\Column(type: "boolean", nullable: false, options: ["default" => "0", "comment" => "0-nesters,1-sters"])]
    private ?bool $sters = null;

    /**
     * @var Collection<int, Factura>
     */
    #[ORM\OneToMany(mappedBy: 'client_pj', targetEntity: Factura::class)]
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

    public function setDenumire(string $denumire): static
    {
        $this->denumire = $denumire;

        return $this;
    }

    public function getCui(): ?string
    {
        return $this->cui;
    }

    public function setCui(string $cui): static
    {
        $this->cui = $cui;

        return $this;
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

    public function isSters(): ?bool
    {
        return $this->sters;
    }

    public function setSters(bool $sters): static
    {
        $this->sters = $sters;

        return $this;
    }

    /**
     * @return Collection<int, Factura>
     */
    public function getFacturi(): Collection
    {
        return $this->facturi;
    }

    public function addFacturi(Factura $facturi): static
    {
        if (!$this->facturi->contains($facturi)) {
            $this->facturi->add($facturi);
            $facturi->setClientPj($this);
        }

        return $this;
    }

    public function removeFacturi(Factura $facturi): static
    {
        if ($this->facturi->removeElement($facturi)) {
            // set the owning side to null (unless already changed)
            if ($facturi->getClientPj() === $this) {
                $facturi->setClientPj(null);
            }
        }

        return $this;
    }
}
