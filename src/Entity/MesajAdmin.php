<?php

namespace App\Entity;

use App\Repository\MesajAdminRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "mesaj_admin")]
#[ORM\Entity(repositoryClass: MesajAdminRepository::class)]
class MesajAdmin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string", length: 255)]
    private $mesaj;

    #[ORM\Column(type: "boolean")]
    private $activ;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMesaj(): ?string
    {
        return $this->mesaj;
    }

    public function setMesaj(string $mesaj): self
    {
        $this->mesaj = $mesaj;

        return $this;
    }

    public function getActiv(): ?bool
    {
        return $this->activ;
    }

    public function setActiv(bool $activ): self
    {
        $this->activ = $activ;

        return $this;
    }
}
