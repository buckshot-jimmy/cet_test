<?php

namespace App\PDF\Contract;

use App\PDF\DTO\PdfDocument;

interface PdfDocumentBuilderInterface
{
    public function getSupportedTemplates(): string;

    public function build(int $id): PdfDocument;
}