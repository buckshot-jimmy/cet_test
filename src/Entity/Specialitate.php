<?php

namespace App\Entity;

use App\Repository\SpecialitateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SpecialitateRepository::class)]
#[ORM\Table(name: "specialitati")]
class Specialitate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 50, nullable: false)]
    private string $denumire;

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

    public function __toString()
    {
        return $this->getDenumire();
    }
}
