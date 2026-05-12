<?php

namespace App\PDF\DTO;

class PdfDocument
{
    public function __construct(public array $data, public array $dateFirma, public string $outputName ) {}
}