<?php

namespace App\Tests\Validator;

use App\Validator\FacturaConstraints;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;

class FacturaConstraintsTest extends TestCase
{
    public function testItExtendsConstraint()
    {
        $constraint = new FacturaConstraints();

        $this->assertInstanceOf(Constraint::class, $constraint);
    }

    /**
     * @covers \App\Validator\FirmeConstraints::messages
     */
    public function testDefaultMessagesAreCorrect()
    {
        $constraint = new FacturaConstraints();

        $this->assertIsArray($constraint->messages);

        $this->assertArrayHasKey('facturaUnica', $constraint->messages);
        $this->assertArrayHasKey('noClient', $constraint->messages);

        $this->assertEquals(
            'Factura cu seria, numarul si data exista in baza de date',
            $constraint->messages['facturaUnica']
        );

        $this->assertEquals(
            'Introduceti un client valid',
            $constraint->messages['noClient']
        );
    }
}
