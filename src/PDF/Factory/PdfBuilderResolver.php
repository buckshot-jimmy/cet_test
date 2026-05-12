<?php

namespace App\PDF\Factory;

use App\PDF\Contract\PdfDocumentBuilderInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class PdfBuilderResolver
{
    private array $map = [];

    public function __construct(
        #[TaggedIterator('app.pdf_builder')]
        iterable $builders
    ) {
        foreach ($builders as $builder) {
            $this->map[$builder->getSupportedTemplates()] = $builder;
        }
    }

    public function resolve(string $template): PdfDocumentBuilderInterface
    {
        if (!isset($this->map[$template])) {
            throw new \Exception("Template not found", 4001);
        }

        return $this->map[$template];
    }
}