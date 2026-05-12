<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Table(name: "users")]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 25, unique: true, nullable: false)]
    private $username;

    #[ORM\Column(type: "string", length: 100, nullable: false)]
    private $password;

    #[ORM\Column(type: "string", length: 25, nullable: false)]
    private $nume;

    #[ORM\Column(type: "string", length: 25, nullable: false)]
    private $prenume;

    #[ORM\Column(type: "string", length: 50, unique: true, nullable: true)]
    private $email;

    #[ORM\Column(type: "string", length: 20)]
    private $telefon;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Role")]
    private $role;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Specialitate")]
    private $specialitate;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Titulatura")]
    private $titulatura;

    #[ORM\Column(type: "string", length: 25, nullable: true)]
    private $codParafa;

    #[ORM\Column(type: "boolean", nullable: false, options: ["default" => "0", "comment" => "0-nesters,1-sters"])]
    private $sters = 0;

    #[ORM\Column(type: "boolean", nullable: false, options:
        ["default" => "0", "comment" => "0-neschimbata la prima logare,1-schimbata la prima logare"]
    )]
    private $parolaSchimbata = 0;

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $authCode;

    public function isEmailAuthEnabled(): bool
    {
//        return true; // This can be a persisted field to switch email code authentication on/off
        return false; // This can be a persisted field to switch email code authentication on/off
    }

    public function getEmailAuthRecipient(): string
    {
        return $this->email;
    }

    public function getEmailAuthCode(): string
    {
        if (null === $this->authCode) {
            throw new \LogicException('The email authentication code was not set');
        }

        return $this->authCode;
    }

    public function setEmailAuthCode(string $authCode): void
    {
        $this->authCode = $authCode;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
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

    public function getPrenume(): ?string
    {
        return $this->prenume;
    }

    public function setPrenume(string $prenume): self
    {
        $this->prenume = $prenume;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getTelefon(): ?string
    {
        return $this->telefon;
    }

    public function setTelefon(string $telefon): self
    {
        $this->telefon = $telefon;

        return $this;
    }

    public function getCodParafa(): ?string
    {
        return $this->codParafa;
    }

    public function setCodParafa(?string $codParafa): self
    {
        $this->codParafa = $codParafa;

        return $this;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getSpecialitate(): ?Specialitate
    {
        return $this->specialitate;
    }

    public function setSpecialitate(?Specialitate $specialitate): self
    {
        $this->specialitate = $specialitate;

        return $this;
    }

    public function getTitulatura(): ?Titulatura
    {
        return $this->titulatura;
    }

    public function setTitulatura(?Titulatura $titulatura): self
    {
        $this->titulatura = $titulatura;

        return $this;
    }

    public function getRoles(): array
    {
        $roles[] = $this->role->getDenumire();

        return $roles;
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

    /**
     * @return mixed
     */
    public function getParolaSchimbata()
    {
        return $this->parolaSchimbata;
    }

    /**
     * @param mixed $parolaSchimbata
     */
    public function setParolaSchimbata($parolaSchimbata): void
    {
        $this->parolaSchimbata = $parolaSchimbata;
    }

    public function getSalt() {}

    public function eraseCredentials() {}

    public function getUserIdentifier(): string
    {
        return $this->username;
    }
}
