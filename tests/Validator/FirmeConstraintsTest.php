<?php

namespace App\Tests\Validator;

use App\Validator\FirmeConstraints;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;

class FirmeConstraintsTest extends TestCase
{
    public function testItExtendsConstraint()
    {
        $constraint = new FirmeConstraints();

        $this->assertInstanceOf(Constraint::class, $constraint);
    }

    /**
     * @covers \App\Validator\FirmeConstraints::messages
     */
    public function testDefaultMessagesAreCorrect()
    {
        $constraint = new FirmeConstraints();

        $this->assertIsArray($constraint->messages);

        $this->assertArrayHasKey('cui', $constraint->messages);
        $this->assertArrayHasKey('cuiUnic', $constraint->messages);

        $this->assertEquals(
            'Introduceti un CUI valid',
            $constraint->messages['cui']
        );

        $this->assertEquals(
            'CUI introdus exista in baza de date',
            $constraint->messages['cuiUnic']
        );
    }
}
