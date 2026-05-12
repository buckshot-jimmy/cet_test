<?php

namespace App\Tests\Security\Voter;

use App\Entity\RapoarteColaboratori;
use App\Entity\Role;
use App\Entity\User;
use App\Security\Voter\RapoarteVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class RapoarteVoterTest extends TestCase
{
    private $token;

    public function setUp(): void
    {
        $this->voter = new RapoarteVoter();

        $this->token = $this->createMock(TokenInterface::class);

        $this->mock = $this->getMockBuilder(RapoarteVoter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['supports'])
            ->getMock();
    }

    /**
     * @covers \App\Security\Voter\RapoarteVoter::supports
     */
    public function testVoteOnAttributeWithNotSupports()
    {
        $result = $this->voter->vote($this->token, null, ['VIEW']);

        $this->assertSame(0, $result);
    }

    /**
     * @covers \App\Security\Voter\RapoarteVoter::voteOnAttribute
     */
    public function testVoteOnAttributeWithRoleTest()
    {
        $user = $this->createMock(User::class);
        $role = $this->createMock(Role::class);
        $this->token->method('getUser')->willReturn($user);
        $user->method('getRole')->willReturn($role);
        $role->method('getDenumire')->willReturn('ROLE_Test');

        $result = $this->voter->vote($this->token, new RapoarteColaboratori(), ['ADD_EDIT']);

        $this->assertSame(-1, $result);
    }

    /**
     * @covers \App\Security\Voter\RapoarteVoter::voteOnAttribute
     */
    public function testVoteOnAttributeWithNotUser()
    {
        $this->token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($this->token, new RapoarteColaboratori(), ['ADD_EDIT']);

        $this->assertSame(-1, $result);
    }

    /**
     * @covers \App\Security\Voter\RapoarteVoter::voteOnAttribute
     * @dataProvider dataProvider
     */
    public function testVoteOnAttribute($attribute, $role, $granted = 1)
    {
        $user = $this->createMock(User::class);
        $roleMock = $this->createMock(Role::class);
        $this->token->method('getUser')->willReturn($user);
        $user->method('getRole')->willReturn($roleMock);
        $roleMock->method('getDenumire')->willReturn($role);

        $result = $this->voter->vote($this->token, new RapoarteColaboratori(), [$attribute]);

        $this->assertSame($granted, $result);
    }

    protected function dataProvider()
    {
        yield ['DELETE', 'ROLE_Administrator'];
        yield ['ADD_EDIT', 'ROLE_Administrator'];
        yield ['VIEW', 'ROLE_Administrator'];
        yield ['VIEW', 'ROLE_Medic'];
        yield ['VIEW', 'ROLE_Receptioner', -1];
    }

    /**
     * @covers \App\Security\Voter\RapoarteVoter::voteOnAttribute
     */
    public function testVoteOnAttributeWithNotAttribute()
    {
        $user = $this->createMock(User::class);
        $roleMock = $this->createMock(Role::class);
        $this->token->method('getUser')->willReturn($user);
        $user->method('getRole')->willReturn($roleMock);
        $roleMock->method('getDenumire')->willReturn('ROLE_Medic');

        $this->token->method('getUser')->willReturn($this->createMock(User::class));
        $this->mock->method('supports')->with('None', new RapoarteColaboratori())->willReturn(true);

        $result = $this->mock->vote($this->token, new RapoarteColaboratori(), ['None']);

        $this->assertSame(-1, $result);
    }
}
