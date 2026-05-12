<?php

namespace App\Tests\Security\Voter;

use App\Entity\Programari;
use App\Entity\Role;
use App\Entity\User;
use App\Security\Voter\ProgramariVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProgramariVoterTest extends TestCase
{
    private $token;

    public function setUp(): void
    {
        $this->voter = new ProgramariVoter();

        $this->token = $this->createMock(TokenInterface::class);

        $this->mock = $this->getMockBuilder(ProgramariVoter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['supports'])
            ->getMock();
    }

    /**
     * @covers \App\Security\Voter\ProgramariVoter::supports
     */
    public function testVoteOnAttributeWithNotSupports()
    {
        $result = $this->voter->vote($this->token, null, ['VIEW']);

        $this->assertSame(0, $result);
    }

    /**
     * @covers \App\Security\Voter\ProgramariVoter::voteOnAttribute
     */
    public function testVoteOnAttributeWithRoleTest()
    {
        $user = $this->createMock(User::class);
        $role = $this->createMock(Role::class);
        $this->token->method('getUser')->willReturn($user);
        $user->method('getRole')->willReturn($role);
        $role->method('getDenumire')->willReturn('ROLE_Test');

        $result = $this->voter->vote($this->token, new Programari(), ['ADD']);

        $this->assertSame(-1, $result);
    }

    /**
     * @covers \App\Security\Voter\ProgramariVoter::voteOnAttribute
     */
    public function testVoteOnAttributeWithNotUser()
    {
        $this->token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($this->token, new Programari(), ['ADD']);

        $this->assertSame(-1, $result);
    }

    /**
     * @covers \App\Security\Voter\ProgramariVoter::voteOnAttribute
     * @dataProvider dataProvider
     */
    public function testVoteOnAttribute($attribute)
    {
        $user = $this->createMock(User::class);
        $roleMock = $this->createMock(Role::class);
        $this->token->method('getUser')->willReturn($user);
        $user->method('getRole')->willReturn($roleMock);
        $roleMock->method('getDenumire')->willReturn('ROLE_Administrator');

        $result = $this->voter->vote($this->token, new Programari(), [$attribute]);

        $this->assertSame(1, $result);
    }

    protected function dataProvider()
    {
        yield ['CANCEL'];
        yield ['ADD'];
    }

    /**
     * @covers \App\Security\Voter\ProgramariVoter::voteOnAttribute
     */
    public function testVoteOnAttributeWithNotAttribute()
    {
        $user = $this->createMock(User::class);
        $roleMock = $this->createMock(Role::class);
        $this->token->method('getUser')->willReturn($user);
        $user->method('getRole')->willReturn($roleMock);
        $roleMock->method('getDenumire')->willReturn('ROLE_Medic');

        $this->token->method('getUser')->willReturn($this->createMock(User::class));
        $this->mock->method('supports')->with('None', new Programari())->willReturn(true);

        $result = $this->mock->vote($this->token, new Programari(), ['None']);

        $this->assertSame(-1, $result);
    }
}
