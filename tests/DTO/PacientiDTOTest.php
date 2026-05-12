<?php

namespace App\Tests\DTO;

use App\DTO\PacientiDTO;
use PHPUnit\Framework\TestCase;

class PacientiDTOTest extends TestCase
{
    public function testItCanBuildDto()
    {
        $dto = new PacientiDTO(1, 'n', 'p', '1790630060774', '0745545689' ,
            '', 'ciprianmarta.cm@gmail.com', 'a', 'Alba', 'Baciu', 'Romania',
            'M', '30-06-1979', 'l', 'o', '2026-01-01', false,
            'o', 1, 1, [], []
        );

        $this->assertInstanceOf(PacientiDTO::class, $dto);
    }
}
