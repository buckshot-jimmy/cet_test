<?php

namespace App\Tests\Security\Voter;

use App\Entity\User;
use App\Entity\Role;
use App\Security\Voter\UserVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserVoterTest extends TestCase
{
    private $token;

    public function setUp(): void
    {
        $this->voter = new UserVoter();

        $this->token = $this->createMock(TokenInterface::class);

        $this->mock = $this->getMockBuilder(UserVoter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['supports'])
            ->getMock();
    }

    /**
     * @covers \App\Security\Voter\UserVoter::supports
     */
    public function testVoteOnAttributeWithNotSupports()
    {
        $result = $this->voter->vote($this->token, null, ['VIEW']);

        $this->assertSame(0, $result);
    }

    /**
     * @covers \App\Security\Voter\UserVoter::voteOnAttribute
     */
    public function testVoteOnAttributeWithRoleTest()
    {
        $user = $this->createMock(User::class);
        $role = $this->createMock(Role::class);
        $this->token->method('getUser')->willReturn($user);
        $user->method('getRole')->willReturn($role);
        $role->method('getDenumire')->willReturn('ROLE_Test');

        $result = $this->voter->vote($this->token, new User(), ['ADD']);

        $this->assertSame(-1, $result);
    }

    /**
     * @covers \App\Security\Voter\UserVoter::voteOnAttribute
     */
    public function testVoteOnAttributeWithNotUser()
    {
        $this->token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($this->token, new User(), ['ADD']);

        $this->assertSame(-1, $result);
    }

    /**
     * @covers \App\Security\Voter\UserVoter::voteOnAttribute
     * @dataProvider dataProvider
     */
    public function testVoteOnAttribute($attribute, $role, $granted = 1)
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $roleMock = $this->createMock(Role::class);
        $this->token->method('getUser')->willReturn($user);
        $user->method('getRole')->willReturn($roleMock);
        $roleMock->method('getDenumire')->willReturn($role);

        $result = $this->voter->vote($this->token, $user, [$attribute]);

        $this->assertSame($granted, $result);
    }

    protected function dataProvider()
    {
        yield ['DELETE', 'ROLE_Administrator'];
        yield ['ADD', 'ROLE_Administrator'];
        yield ['EDIT', 'ROLE_Administrator'];
        yield ['VIEW', 'ROLE_Administrator'];
        yield ['VIEW', 'ROLE_Medic'];
    }

    /**
     * @covers \App\Security\Voter\UserVoter::voteOnAttribute
     */
    public function testVoteOnAttributeViewNotGrantedForReceptie()
    {
        $user = $this->createMock(User::class);
        $roleMock = $this->createMock(Role::class);
        $this->token->method('getUser')->willReturn($user);
        $user->method('getRole')->willReturn($roleMock);
        $roleMock->method('getDenumire')->willReturn('ROLE_Receptioner');

        $result = $this->voter->vote($this->token, new User(), ['VIEW']);

        $this->assertSame(-1, $result);
    }

    /**
     * @covers \App\Security\Voter\UserVoter::voteOnAttribute
     */
    public function testVoteOnAttributeEditWithCondition()
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $roleMock = $this->createMock(Role::class);
        $this->token->method('getUser')->willReturn($user);
        $user->method('getRole')->willReturn($roleMock);
        $roleMock->method('getDenumire')->willReturn('ROLE_Medic');
        $editedUser = $this->createMock(User::class);
        $editedUser->method('getId')->willReturn(1);

        $result = $this->voter->vote($this->token, $editedUser, ['EDIT']);

        $this->assertSame(1, $result);
    }

    /**
     * @covers \App\Security\Voter\UserVoter::voteOnAttribute
     */
    public function testVoteOnAttributeWithNotAttribute()
    {
        $user = $this->createMock(User::class);
        $roleMock = $this->createMock(Role::class);
        $this->token->method('getUser')->willReturn($user);
        $user->method('getRole')->willReturn($roleMock);
        $roleMock->method('getDenumire')->willReturn('ROLE_Medic');

        $this->token->method('getUser')->willReturn($this->createMock(User::class));
        $this->mock->method('supports')->with('None', new User())->willReturn(true);

        $result = $this->mock->vote($this->token, new User(), ['None']);

        $this->assertSame(-1, $result);
    }
}
