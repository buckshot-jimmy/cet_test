<?php

namespace App\Tests\Validator;

use App\Validator\TarifConstraints;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;

class TarifConstraintsTest extends TestCase
{
    public function testItExtendsConstraint()
    {
        $constraint = new TarifConstraints();

        $this->assertInstanceOf(Constraint::class, $constraint);
    }

    public function testDefaultMessagesAreCorrect()
    {
        $constraint = new TarifConstraints();

        $this->assertIsArray($constraint->messages);

        $this->assertArrayHasKey('medicServiciuOwner', $constraint->messages);
        $this->assertArrayHasKey('tarifNegativ', $constraint->messages);

        $this->assertEquals(
            'Dr-Owner tariff in DB',
            $constraint->messages['medicServiciuOwner']
        );

        $this->assertEquals(
            'Negative tariff',
            $constraint->messages['tarifNegativ']
        );
    }
}
