<?php

namespace App\Tests\Validator;

use App\Entity\Role;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Validator\UserConstraints;
use App\Validator\UserConstraintsValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UserConstraintsValidatorTest extends ConstraintValidatorTestCase
{
    private $repo;
    private $hasher;
    private $user;
    private $role;

    /**
     * @covers \App\Validator\UserConstraintsValidator::__construct
     */
    public function testItCanBuildConstraintValidator()
    {
        $this->assertInstanceOf(UserConstraintsValidator::class, $this->createValidator());
    }

    protected function createValidator()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->repo = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find', 'findOneBy'])
            ->getMock();
        $em->method('getRepository')->willReturn($this->repo);

        $this->hasher = $this->createMock(UserPasswordHasherInterface::class);

        $this->user = new User();
        $this->role = new Role();

        return new UserConstraintsValidator($em, $this->hasher);
    }

    /**
     * @covers \App\Validator\UserConstraintsValidator::validate
     * @covers \App\Validator\UserConstraintsValidator::checkNewPasswordSameOld
     * @covers \App\Validator\UserConstraintsValidator::checkRoleChangeNotAdmin
     * @covers \App\Validator\UserConstraintsValidator::checkPasswordChangeAnotherUser
     * @covers \App\Validator\UserConstraintsValidator::checkPasswordChangeAnotherAdministrator
     * @covers \App\Validator\UserConstraintsValidator::checkPasswordStrength
     */
    public function testChangePasswordWithNoViolations()
    {
        $this->role->setDenumire('ROLE_Medic');
        $this->user->setRole($this->role);

        $this->repo->method('find')->willReturn($this->user);
        $this->repo->method('findOneBy')->willReturn(null);

        $constraint = new UserConstraints();

        $this->validator->validate(
            [
                'loggedUserId' => 1,
                'username' => 'username_unique',
                'email' => 'email@test.com',
                'editUserId' => 1,
                'edit_profile_password' => 'Admin_123',
                'role_name' => 'ROLE_Medic',
            ],
            $constraint);

        $this->assertNoViolation();
    }

    /**
     * @covers \App\Validator\UserConstraintsValidator::validate
     * @covers \App\Validator\UserConstraintsValidator::checkNewPasswordSameOld
     * @covers \App\Validator\UserConstraintsValidator::checkRoleChangeNotAdmin
     * @covers \App\Validator\UserConstraintsValidator::checkPasswordChangeAnotherUser
     * @covers \App\Validator\UserConstraintsValidator::checkPasswordChangeAnotherAdministrator
     */
    public function testChangePasswordWithSamePasswordViolations()
    {
        $this->role->setDenumire('ROLE_Administrator');
        $this->user->setRole($this->role);

        $this->repo->method('find')->willReturn($this->user);

        $this->hasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->user, 'Admin_123')
            ->willReturn(true);

        $constraint = new UserConstraints();

        $this->validator->validate(
            [
                'editUserId' => 1,
                'loggedUserId' => 1,
                'edit_profile_password' => 'Admin_123'
            ],
            $constraint);

        $this->buildViolation($constraint->messages['parolaCurenta'])->assertRaised();
    }

    /**
     * @covers \App\Validator\UserConstraintsValidator::validate
     * @covers \App\Validator\UserConstraintsValidator::checkNewPasswordSameOld
     * @covers \App\Validator\UserConstraintsValidator::checkRoleChangeNotAdmin
     * @covers \App\Validator\UserConstraintsValidator::checkPasswordChangeAnotherUser
     * @covers \App\Validator\UserConstraintsValidator::checkPasswordChangeAnotherAdministrator
     */
    public function testRoleChangeNotAdminRoleNotChangedNoViolations()
    {
        $this->role->setDenumire('ROLE_test');
        $this->user->setRole($this->role);

        $this->hasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->user, 'Admin_123')
            ->willReturn(false);

        $this->repo->method('find')->willReturn($this->user);

        $constraint = new UserConstraints();

        $this->validator->validate(
            [
                'editUserId' => 1,
                'loggedUserId' => 1,
                'role_name' => 'ROLE_test',
                'edit_profile_password' => 'Admin_123'
            ],
            $constraint
        );

        $this->assertNoViolation();
    }

    /**
     * @covers \App\Validator\UserConstraintsValidator::validate
     * @covers \App\Validator\UserConstraintsValidator::checkNewPasswordSameOld
     * @covers \App\Validator\UserConstraintsValidator::checkRoleChangeNotAdmin
     * @covers \App\Validator\UserConstraintsValidator::checkPasswordChangeAnotherUser
     * @covers \App\Validator\UserConstraintsValidator::checkPasswordChangeAnotherAdministrator
     */
    public function testRoleChangeNotAdminNoViolations()
    {
        $this->role->setDenumire('ROLE_Administrator');
        $this->user->setRole($this->role);

        $this->hasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->user, 'Admin_123')
            ->willReturn(false);

        $this->repo->method('find')->willReturn($this->user);

        $constraint = new UserConstraints();

        $this->validator->validate(
            [
                'editUserId' => 1,
                'loggedUserId' => 1,
                'role_name' => 'ROLE_test',
                'edit_profile_password' => 'Admin_123'
            ],
            $constraint
        );

        $this->assertNoViolation();
    }

    /**
     * @covers \App\Validator\UserConstraintsValidator::validate
     * @covers \App\Validator\UserConstraintsValidator::checkNewPasswordSameOld
     * @covers \App\Validator\UserConstraintsValidator::checkRoleChangeNotAdmin
     * @covers \App\Validator\UserConstraintsValidator::checkPasswordChangeAnotherUser
     * @covers \App\Validator\UserConstraintsValidator::checkPasswordChangeAnotherAdministrator
     */
    public function testRoleChangeNotAdminAddViolations()
    {
        $this->role->setDenumire('ROLE_Test');
        $this->user->setRole($this->role);

        $this->hasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->user, 'Admin_123')
            ->willReturn(false);

        $this->repo->method('find')->willReturn($this->user);

        $constraint = new UserConstraints();

        $this->validator->validate(
            [
                'editUserId' => 1,
                'loggedUserId' => 1,
                'role' => 'ROLE_test',
                'edit_profile_password' => 'Admin_123'
            ],
            $constraint
        );

        $this->buildViolation($constraint->messages['modificareRol'])->assertRaised();
    }

    /**
     * @covers \App\Validator\UserConstraintsValidator::validate
     * @covers \App\Validator\UserConstraintsValidator::checkNewPasswordSameOld
     * @covers \App\Validator\UserConstraintsValidator::checkRoleChangeNotAdmin
     * @covers \App\Validator\UserConstraintsValidator::checkPasswordChangeAnotherUser
     * @covers \App\Validator\UserConstraintsValidator::checkPasswordChangeAnotherAdministrator
     */
    public function testChangePasswordAnotherUserAddViolations()
    {
        $this->role->setDenumire('ROLE_Medic');
        $this->user->setRole($this->role);

        $this->hasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->user, 'Admin_123')
            ->willReturn(false);

        $this->repo->method('find')->willReturn($this->user);

        $constraint = new UserConstraints();

        $this->validator->validate(
            [
                'editUserId' => 1,
                'loggedUserId' => 2,
                'role_name' => 'ROLE_Medic',
                'edit_profile_password' => 'Admin_123'
            ],
            $constraint
        );

        $this->buildViolation($constraint->messages['modificareParolaAltUtilizator'])->assertRaised();
    }

    /**
     * @covers \App\Validator\UserConstraintsValidator::validate
     * @covers \App\Validator\UserConstraintsValidator::checkNewPasswordSameOld
     * @covers \App\Validator\UserConstraintsValidator::checkRoleChangeNotAdmin
     * @covers \App\Validator\UserConstraintsValidator::checkPasswordChangeAnotherUser
     * @covers \App\Validator\UserConstraintsValidator::checkPasswordChangeAnotherAdministrator
     */
    public function testChangePasswordAnotherAdminAddViolations()
    {
        $this->role->setDenumire('ROLE_Administrator');
        $this->user->setRole($this->role);

        $this->hasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->user, 'Admin_123')
            ->willReturn(false);

        $this->repo->method('find')->willReturn($this->user);

        $constraint = new UserConstraints();

        $this->validator->validate(
            [
                'editUserId' => 1,
                'loggedUserId' => 2,
                'role_name' => 'ROLE_Administrator',
                'edit_profile_password' => 'Admin_123'
            ],
            $constraint
        );

        $this->buildViolation($constraint->messages['modificareParolaAltAdministrator'])->assertRaised();
    }

    /**
     * @covers \App\Validator\UserConstraintsValidator::validate
     * @covers \App\Validator\UserConstraintsValidator::checkNewPasswordSameOld
     * @covers \App\Validator\UserConstraintsValidator::checkRoleChangeNotAdmin
     * @covers \App\Validator\UserConstraintsValidator::checkPasswordChangeAnotherUser
     * @covers \App\Validator\UserConstraintsValidator::checkPasswordChangeAnotherAdministrator
     * @covers \App\Validator\UserConstraintsValidator::checkPasswordStrength
     * @covers \App\Validator\UserConstraintsValidator::checkUniqueUsername
     * @covers \App\Validator\UserConstraintsValidator::checkUniqueEmail
     */
    public function testChangePasswordWeakPasswordAddViolations()
    {
        $this->role->setDenumire('ROLE_Administrator');
        $this->user->setRole($this->role);

        $this->hasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->user, 'Admin')
            ->willReturn(false);

        $this->repo->method('find')->willReturn($this->user);

        $constraint = new UserConstraints();

        $this->validator->validate(
            [
                'editUserId' => 1,
                'loggedUserId' => 1,
                'role_name' => 'ROLE_test',
                'edit_profile_password' => 'Admin'
            ],
            $constraint
        );

        $this->buildViolation($constraint->messages['parolaSlaba'])->assertRaised();
    }

    /**
     * @covers \App\Validator\UserConstraintsValidator::validate
     * @covers \App\Validator\UserConstraintsValidator::checkUniqueUsername
     */
    public function testUniqueUsernameWithViolations()
    {
        $this->role->setDenumire('ROLE_Medic');
        $this->user->setRole($this->role);

        $editedUser = $this->createMock(User::class);
        $editedUser->method('getId')->willReturn(2);
        $editedUser->method('getUsername')->willReturn('username_unique');

        $this->repo->method('find')->willReturn($this->user);
        $this->repo->method('findOneBy')
            ->willReturnCallback(function ($criteria) use ($editedUser) {
                if ($criteria === ['username' => 'username_unique']) {
                    return $editedUser;
                }

                if ($criteria === ['email' => 'email@test.com']) {
                    return null;
                }

                return null;
            });

        $this->hasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->user, 'Admin_123')
            ->willReturn(false);

        $constraint = new UserConstraints();

        $this->validator->validate(
            [
                'loggedUserId' => 1,
                'username' => 'username_unique',
                'email' => 'email@test.com',
                'editUserId' => 1,
                'edit_profile_password' => 'Admin_123',
                'role_name' => 'ROLE_Medic',
            ],
            $constraint);

        $this->buildViolation($constraint->messages['usernameUnic'])->assertRaised();
    }

    /**
     * @covers \App\Validator\UserConstraintsValidator::validate
     * @covers \App\Validator\UserConstraintsValidator::checkUniqueUsername
     */
    public function testUniqueUsernameForEditedWithNoViolations()
    {
        $this->role->setDenumire('ROLE_Medic');
        $this->user->setRole($this->role);

        $editedUser = $this->createMock(User::class);
        $editedUser->method('getId')->willReturn(1);
        $editedUser->method('getUsername')->willReturn('username_unique');

        $this->repo->method('find')->willReturn($this->user);
        $this->repo->method('findOneBy')
            ->willReturnCallback(function ($criteria) use ($editedUser) {
                if ($criteria === ['username' => 'username_unique']) {
                    return $editedUser;
                }

                if ($criteria === ['email' => 'email@test.com']) {
                    return null;
                }

                return null;
            });

        $this->hasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->user, 'Admin_123')
            ->willReturn(false);

        $constraint = new UserConstraints();

        $this->validator->validate(
            [
                'loggedUserId' => 1,
                'username' => 'username_unique',
                'email' => 'email@test.com',
                'editUserId' => 1,
                'edit_profile_password' => 'Admin_123',
                'role_name' => 'ROLE_Medic',
            ],
            $constraint);

        $this->assertNoViolation();
    }

    /**
     * @covers \App\Validator\UserConstraintsValidator::validate
     * @covers \App\Validator\UserConstraintsValidator::checkUniqueEmail
     */
    public function testUniqueEmailWithViolations()
    {
        $this->role->setDenumire('ROLE_Medic');
        $this->user->setRole($this->role);

        $editedUser = $this->createMock(User::class);
        $editedUser->method('getId')->willReturn(2);
        $editedUser->method('getUsername')->willReturn('username_unique');

        $this->repo->method('find')->willReturn($this->user);
        $this->repo->method('findOneBy')
            ->willReturnCallback(function ($criteria) use ($editedUser) {
                if ($criteria === ['username' => 'username_unique']) {
                    return null;
                }

                if ($criteria === ['email' => 'email@test.com']) {
                    return $editedUser;
                }

                return null;
            });

        $this->hasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->user, 'Admin_123')
            ->willReturn(false);

        $constraint = new UserConstraints();

        $this->validator->validate(
            [
                'loggedUserId' => 1,
                'username' => 'username_unique',
                'email' => 'email@test.com',
                'editUserId' => 1,
                'edit_profile_password' => 'Admin_123',
                'role_name' => 'ROLE_Medic',
            ],
            $constraint);

        $this->buildViolation($constraint->messages['emailUnic'])->assertRaised();
    }

    /**
     * @covers \App\Validator\UserConstraintsValidator::validate
     * @covers \App\Validator\UserConstraintsValidator::checkUniqueEmail
     */
    public function testUniqueEmailWithNoViolations()
    {
        $this->role->setDenumire('ROLE_Medic');
        $this->user->setRole($this->role);

        $editedUser = $this->createMock(User::class);
        $editedUser->method('getId')->willReturn(1);
        $editedUser->method('getEmail')->willReturn('email@test.com');

        $this->repo->method('find')->willReturn($this->user);
        $this->repo->method('findOneBy')
            ->willReturnCallback(function ($criteria) use ($editedUser) {
                if ($criteria === ['username' => 'username_unique']) {
                    return null;
                }

                if ($criteria === ['email' => 'email@test.com']) {
                    return $editedUser;
                }

                return null;
            });

        $this->hasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->user, 'Admin_123')
            ->willReturn(false);

        $constraint = new UserConstraints();

        $this->validator->validate(
            [
                'loggedUserId' => 1,
                'username' => 'username_unique',
                'email' => 'email@test.com',
                'editUserId' => 1,
                'edit_profile_password' => 'Admin_123',
                'role_name' => 'ROLE_Medic',
            ],
            $constraint);

        $this->assertNoViolation();
    }

    /**
     * @covers \App\Validator\UserConstraintsValidator::validate
     * @covers \App\Validator\UserConstraintsValidator::checkNewPasswordSameOld
     * @covers \App\Validator\UserConstraintsValidator::checkRoleChangeNotAdmin
     * @covers \App\Validator\UserConstraintsValidator::checkPasswordChangeAnotherUser
     * @covers \App\Validator\UserConstraintsValidator::checkPasswordChangeAnotherAdministrator
     * @covers \App\Validator\UserConstraintsValidator::checkPasswordStrength
     * @covers \App\Validator\UserConstraintsValidator::checkUniqueUsername
     * @covers \App\Validator\UserConstraintsValidator::checkUniqueEmail
     */
    public function testCheckCanAddNewUserWithNoViolations()
    {
        $constraint = new UserConstraints();

        $this->validator->validate(
            [
                'loggedUserId' => 1,
                'username' => 'username_unique',
                'email' => 'email@test.com',
                'editUserId' => null,
                'edit_profile_password' => 'Admin_123',
                'role_name' => 'ROLE_Medic',
            ],
            $constraint);

        $this->assertNoViolation();
    }
}
