<?php

namespace App\Tests\Validator;

use App\Validator\UserConstraints;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;

class UserConstraintsTest extends TestCase
{
    public function testItExtendsConstraint()
    {
        $constraint = new UserConstraints();

        $this->assertInstanceOf(Constraint::class, $constraint);
    }

    public function testDefaultMessagesAreCorrect()
    {
        $constraint = new UserConstraints();

        $this->assertIsArray($constraint->messages);

        $this->assertArrayHasKey('parolaCurenta', $constraint->messages);
        $this->assertArrayHasKey('modificareRol', $constraint->messages);
        $this->assertArrayHasKey('modificareParolaAltUtilizator', $constraint->messages);
        $this->assertArrayHasKey('modificareParolaAltAdministrator', $constraint->messages);

        $this->assertEquals(
            'Password cannot be the same as your current password',
            $constraint->messages['parolaCurenta']
        );

        $this->assertEquals(
            'You are not allowed to change your role',
            $constraint->messages['modificareRol']
        );

        $this->assertEquals(
            "You are not allowed to change another user's password",
            $constraint->messages['modificareParolaAltUtilizator']
        );

        $this->assertEquals(
            "You are not allowed to change another administrator's password",
            $constraint->messages['modificareParolaAltAdministrator']
        );
    }
}
