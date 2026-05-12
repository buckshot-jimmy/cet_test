<?php

namespace App\Entity;

use App\Repository\PreturiRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "preturi")]
#[ORM\UniqueConstraint(
    name: "medic_serviciu",
    columns: ["medic_id","serviciu_id","owner_id"]
)]
#[ORM\Entity(repositoryClass: PreturiRepository::class)]
class Preturi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    public function getId(): ?int
    {
        return $this->id;
    }

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "medic_id", referencedColumnName: "id", nullable: false)]
    private $medic;

    #[ORM\ManyToOne(targetEntity: Servicii::class)]
    #[ORM\JoinColumn(name: "serviciu_id", referencedColumnName: "id", nullable: false)]
    private $serviciu;

    #[ORM\ManyToOne(targetEntity: Owner::class)]
    #[ORM\JoinColumn(name: "owner_id", referencedColumnName: "id", nullable: false)]
    private $owner;

    #[ORM\Column(type: "integer", nullable: false)]
    private int $pret;

    #[ORM\Column(type: "integer", nullable: false)]
    private int $procentajMedic;

    #[ORM\Column(type: "boolean", nullable: false, options: ["default" => "0", "comment" => "0-nesters,1-sters"])]
    private bool $sters = false;

    #[ORM\Column(nullable: false, options: ["default" => 0])]
    private ?int $cotaTva = null;

    /**
     * @return mixed
     */
    public function getMedic()
    {
        return $this->medic;
    }

    /**
     * @param mixed $medic
     */
    public function setMedic($medic): void
    {
        $this->medic = $medic;
    }

    /**
     * @return mixed
     */
    public function getServiciu()
    {
        return $this->serviciu;
    }

    /**
     * @param mixed $serviciu
     */
    public function setServiciu($serviciu): void
    {
        $this->serviciu = $serviciu;
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
    public function getProcentajMedic()
    {
        return $this->procentajMedic;
    }

    /**
     * @param mixed $procentajMedic
     */
    public function setProcentajMedic($procentajMedic): void
    {
        $this->procentajMedic = $procentajMedic;
    }

    /**
     * @return mixed
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param mixed $owner
     */
    public function setOwner($owner): void
    {
        $this->owner = $owner;
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

    public function getCotaTva(): ?int
    {
        return $this->cotaTva;
    }

    public function setCotaTva(int $cotaTva): static
    {
        $this->cotaTva = $cotaTva;

        return $this;
    }
}
