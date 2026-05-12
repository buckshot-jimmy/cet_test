<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class PacientiDTO
{
    public function __construct(
        public $id,

        #[Assert\NotBlank]
        public string $nume,

        #[Assert\NotBlank]
        public string $prenume,

        #[Assert\NotBlank]
        public string $cnp,

        #[Assert\NotBlank]
        public string $telefon,

        public ?string $telefon2,
        public ?string $email,

        #[Assert\NotBlank]
        public string $adresa,

        public ?string $judet,
        public ?string $localitate,

        #[Assert\NotBlank]
        public string $tara,

        public ?string $ci,
        public ?string $ciEliberat,
        public ?string $locMunca,
        public ?string $ocupatie,
        public ?string $dataInreg,
        public ?bool $sters,
        public ?string $observatii,

        #[Assert\NotBlank]
        public string $stareCivila,

        public ?string $adaugatDe,
        public $consultatii = null,
        public $programari = null,
    ) {}
}