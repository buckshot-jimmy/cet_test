<?php

namespace App\Tests\Validator;

use App\Validator\ProgramareConstraints;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;

class ProgramareConstraintsTest extends TestCase
{
    public function testItExtendsConstraint()
    {
        $constraint = new ProgramareConstraints();

        $this->assertInstanceOf(Constraint::class, $constraint);
    }

    public function testDefaultMessagesAreCorrect()
    {
        $constraint = new ProgramareConstraints();

        $this->assertIsArray($constraint->messages);

        $this->assertArrayHasKey('unavailableTime', $constraint->messages);

        $this->assertEquals('Ora indisponibila',$constraint->messages['unavailableTime']);
    }
}
