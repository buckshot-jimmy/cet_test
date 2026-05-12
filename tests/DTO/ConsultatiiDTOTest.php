<?php

namespace App\Tests\DTO;

use App\DTO\ConsultatiiDTO;
use PHPUnit\Framework\TestCase;

class ConsultatiiDTOTest extends TestCase
{
    public function testItCanBuildDto()
    {
        $dto = new ConsultatiiDTO(1, '1', '1', 'C', '10' , '1', 'd',
            'c', 't', 'ahc', 'app', '01-01-2026', 1, '23',
            false, false, 'MT', 'inv', 'tr',
            'inv', 'trat', 'obs', 'ev', 'con', 'rez',
            'ob', 'rec', 'cc', 'cc', 'cc',
            'cc', 'pc', 'pc', 'pc', 'pc',
            'rc', 'rc', 'sc', 'sc', 1
        );

        $this->assertInstanceOf(ConsultatiiDTO::class, $dto);
    }
}
