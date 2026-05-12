<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ConsultatiiDTO
{
    public function __construct(
        #[Assert\NotNull]
        public int $id,

        #[Assert\NotBlank]
        public string $nrInreg,

        #[Assert\NotBlank]
        public int $tarif,

        #[Assert\NotBlank]
        public string $loc,

        #[Assert\NotNull]
        public int $pret,

        #[Assert\NotNull]
        public int $pacient,

        public ?string $diagnostic,
        public ?string $consultatie,
        public ?string $tratament,
        public ?string $ahc,
        public ?string $app,
        public ?string $dataConsultatie,
        public ?int $zileCm,
        public ?string $certifCm,
        public ?bool $inchisa,
        public ?bool $stearsa,
        public ?bool $incasata,
        public ?bool $platitaColaborator,
        public ?string $medicTrimitator,
        public ?string $investigatiiUrmate,
        public ?string $tratamenteUrmate,
        public ?string $observatii,
        public ?string $evalPsiho,
        public ?string $concluzie,
        public ?string $rezultat,
        public ?string $obiectiv,
        public ?string $recomandari,
        public ?string $cognitiv_ce,
        public ?string $cognitiv_cu_ce,
        public ?string $comportamental_ce,
        public ?string $comportamental_cu_ce,
        public ?string $personalitate_ce,
        public ?string $personalitate_cu_ce,
        public ?string $psihofiziologic_ce,
        public ?string $psihofiziologic_cu_ce,
        public ?string $relationare_ce,
        public ?string $relationare_cu_ce,
        public ?string $subiectiv_ce,
        public ?string $subiectiv_cu_ce,
        public ?int $programare = null
    ) {}
}