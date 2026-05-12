<?php

namespace App\PDF\Contract;

interface PdfInterface
{
    public function printToPdf(int $id, string $template, array $params = []);
}