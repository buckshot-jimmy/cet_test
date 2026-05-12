<?php

namespace App\Entity;

use App\Repository\RapoarteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RapoarteRepository::class)]
#[ORM\Table(name: "rapoarte_colaboratori")]
class RapoarteColaboratori
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $dataGenerarii;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "medic_id", referencedColumnName: "id", nullable: false)]
    private ?User $medic = null;

    #[ORM\ManyToOne(targetEntity: Owner::class)]
    #[ORM\JoinColumn(name: "owner_id", referencedColumnName: "id", nullable: false)]
    private ?Owner $owner = null;

    #[ORM\Column(type: "string", length: 4)]
    private string $an;

    #[ORM\Column(type: "string", length: 10)]
    private string $luna;

    #[ORM\Column(type: "integer")]
    private int $suma;

    #[ORM\Column(type: "string", length: 9)]
    private string $stare = "neplatita";

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDataGenerarii(): ?\DateTimeInterface
    {
        return $this->dataGenerarii;
    }

    public function setDataGenerarii(\DateTimeInterface $data_generarii): self
    {
        $this->dataGenerarii = $data_generarii;

        return $this;
    }

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

    public function getAn(): ?string
    {
        return $this->an;
    }

    public function setAn(string $an): self
    {
        $this->an = $an;

        return $this;
    }

    public function getLuna(): ?string
    {
        return $this->luna;
    }

    public function setLuna(string $luna): self
    {
        $this->luna = $luna;

        return $this;
    }

    public function getSuma(): ?int
    {
        return $this->suma;
    }

    public function setSuma(int $suma): self
    {
        $this->suma = $suma;

        return $this;
    }

    public function getStare(): ?string
    {
        return $this->stare;
    }

    public function setStare(string $stare): self
    {
        $this->stare = $stare;

        return $this;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
