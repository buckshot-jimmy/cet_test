<?php

namespace App\Entity;

use App\Repository\FacturaConsultatieRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "factura_consultatie")]
#[ORM\UniqueConstraint(
    name: "factura_consultatie",
    columns: ["factura_id","consultatie_id"]
)]
#[ORM\Entity(repositoryClass: FacturaConsultatieRepository::class)]
class FacturaConsultatie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'facturaConsultatii')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Facturi $factura = null;

    #[ORM\ManyToOne(inversedBy: 'facturaConsultatii')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Consultatii $consultatie = null;

    #[ORM\Column]
    private ?int $valoare = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFactura(): ?Facturi
    {
        return $this->factura;
    }

    public function setFactura(?Facturi $factura): static
    {
        $this->factura = $factura;

        return $this;
    }

    public function getConsultatie(): ?Consultatii
    {
        return $this->consultatie;
    }

    public function setConsultatie(?Consultatii $consultatie): static
    {
        $this->consultatie = $consultatie;

        return $this;
    }

    public function getValoare(): ?int
    {
        return $this->valoare;
    }

    public function setValoare(int $valoare): static
    {
        $this->valoare = $valoare;

        return $this;
    }
}
