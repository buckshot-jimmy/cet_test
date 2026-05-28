<?php

namespace App\Entity;

use App\Repository\MedicTrimitatorRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "medici_trimitatori")]
#[ORM\Entity(repositoryClass: MedicTrimitatorRepository::class)]
class MedicTrimitator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string", length: 100, unique: true)]
    private $nume;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNume(): ?string
    {
        return $this->nume;
    }

    public function setNume(string $nume): self
    {
        $this->nume = $nume;

        return $this;
    }
}
