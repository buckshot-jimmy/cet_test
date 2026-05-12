<?php

namespace App\Entity;

use App\Repository\ServiciiRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiciiRepository::class)]
#[ORM\Table(name: "servicii")]
class Servicii
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 50, unique: true, nullable: false)]
    private string $denumire;

    #[ORM\Column(type: "smallint", nullable: false, options:
        ["comment" => "0-consultatie,1-investigatie,2-psihodiagnostic"])]
    private int $tip;

    #[ORM\Column(type: "boolean", nullable: false, options: ["default" => "0", "comment" => "0-nesters,1-sters"])]
    private bool $sters = false;

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

    /**
     * @return mixed
     */
    public function getTip()
    {
        return $this->tip;
    }

    /**
     * @param mixed $tip
     */
    public function setTip($tip): void
    {
        $this->tip = $tip;
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
}
