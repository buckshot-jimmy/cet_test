<?php

namespace App\Tests\Validator;

use App\Validator\PacientConstraints;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;

class PacientConstraintsTest extends TestCase
{
    public function testItExtendsConstraint()
    {
        $constraint = new PacientConstraints();

        $this->assertInstanceOf(Constraint::class, $constraint);
    }

    public function testDefaultMessagesAreCorrect()
    {
        $constraint = new PacientConstraints();

        $this->assertIsArray($constraint->messages);

        $this->assertArrayHasKey('cnp', $constraint->messages);
        $this->assertArrayHasKey('cnpIdUnic', $constraint->messages);

        $this->assertEquals(
            'Introduceti un CNP valid',
            $constraint->messages['cnp']
        );

        $this->assertEquals(
            'CNP / ID introdus exista in baza de date',
            $constraint->messages['cnpIdUnic']
        );
    }
}
