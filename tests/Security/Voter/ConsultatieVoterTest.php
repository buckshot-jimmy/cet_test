<?php

namespace App\Tests\Security\Voter;

use App\Entity\Consultatie;
use App\Entity\Pret;
use App\Entity\Role;
use App\Entity\User;
use App\Security\Voter\ConsultatieVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ConsultatieVoterTest extends TestCase
{
    private $token;

    public function setUp(): void
    {
        $this->voter = new ConsultatieVoter();
        
        $this->token = $this->createMock(TokenInterface::class);

        $this->mock = $this->getMockBuilder(ConsultatieVoter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['supports'])
            ->getMock();
    }

    /**
     * @covers \App\Security\Voter\ConsultatieVoter::supports
     */
    public function testVoteOnAttributeWithNotSupports()
    {
        $result = $this->voter->vote($this->token, null, ['VIEW']);

        $this->assertSame(0, $result);
    }

    /**
     * @covers \App\Security\Voter\ConsultatieVoter::voteOnAttribute
     */
    public function testVoteOnAttributeWithRoleTest()
    {
        $user = $this->createMock(User::class);
        $role = $this->createMock(Role::class);
        $this->token->method('getUser')->willReturn($user);
        $user->method('getRole')->willReturn($role);
        $role->method('getDenumire')->willReturn('ROLE_Test');

        $result = $this->voter->vote($this->token, new Consultatie(), ['VIEW']);

        $this->assertSame(-1, $result);
    }

    /**
     * @covers \App\Security\Voter\ConsultatieVoter::voteOnAttribute
     */
    public function testVoteOnAttributeWithNotUser()
    {
        $this->token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($this->token, new Consultatie(), ['VIEW']);

        $this->assertSame(-1, $result);
    }

    /**
     * @covers \App\Security\Voter\ConsultatieVoter::voteOnAttribute
     * @covers \App\Security\Voter\ConsultatieVoter::permiteAdaugareEditareWithCondition
     * @dataProvider dataProvider
     */
    public function testVoteOnAttribute($attribute, $roleName, $granted)
    {
        $user = $this->createMock(User::class);
        $roleMock = $this->createMock(Role::class);
        $this->token->method('getUser')->willReturn($user);
        $user->method('getRole')->willReturn($roleMock);
        $roleMock->method('getDenumire')->willReturn($roleName);

        $result = $this->voter->vote($this->token, new Consultatie(), [$attribute]);

        $this->assertSame($granted, $result);
    }

    /**
     * @covers \App\Security\Voter\ConsultatieVoter::voteOnAttribute
     * @covers \App\Security\Voter\ConsultatieVoter::permiteAdaugareEditareWithCondition
     */
    public function testVoteOnAttributeWithConditionForAdmin()
    {
        $user = $this->createMock(User::class);
        $roleMock = $this->createMock(Role::class);
        $this->token->method('getUser')->willReturn($user);
        $user->method('getRole')->willReturn($roleMock);
        $roleMock->method('getDenumire')->willReturn('ROLE_Administrator');
        $consultatie = $this->createMock(Consultatie::class);
        $consultatie->method('getId')->willReturn(1);

        $result = $this->voter->vote($this->token, $consultatie, ['ADD_EDIT']);

        $this->assertSame(1, $result);
    }

    /**
     * @covers \App\Security\Voter\ConsultatieVoter::voteOnAttribute
     * @covers \App\Security\Voter\ConsultatieVoter::permiteAdaugareEditareWithCondition
     * @dataProvider dataProviderForVoteCondition
     */
    public function testVoteOnAttributeWithConditionForMedic($loggedUserId, $consultatieMedicId, $granted)
    {
        $user = $this->createMock(User::class);
        $medic = $this->createMock(User::class);
        $user->method('getId')->willReturn($loggedUserId);
        $roleMock = $this->createMock(Role::class);
        $this->token->method('getUser')->willReturn($user);
        $user->method('getRole')->willReturn($roleMock);
        $roleMock->method('getDenumire')->willReturn('ROLE_Medic');
        $consultatie = $this->createMock(Consultatie::class);
        $consultatie->method('getId')->willReturn(1);
        $pret = $this->createMock(Pret::class);
        $consultatie->method('getPret')->willReturn($pret);
        $pret->method('getMedic')->willReturn($medic);
        $medic->method('getId')->willReturn($consultatieMedicId);

        $result = $this->voter->vote($this->token, $consultatie, ['ADD_EDIT']);

        $this->assertSame($granted, $result);
    }

    protected function dataProviderForVoteCondition()
    {
        yield [1, 1, 1];
        yield [1, 2, -1];
    }

    /**
     * @covers \App\Security\Voter\ConsultatieVoter::voteOnAttribute
     */
    public function testVoteOnAttributeWithNotAttribute()
    {
        $user = $this->createMock(User::class);
        $roleMock = $this->createMock(Role::class);
        $this->token->method('getUser')->willReturn($user);
        $user->method('getRole')->willReturn($roleMock);
        $roleMock->method('getDenumire')->willReturn('ROLE_Medic');

        $this->token->method('getUser')->willReturn($this->createMock(User::class));
        $this->mock->method('supports')->with('None', new Consultatie())->willReturn(true);

        $result = $this->mock->vote($this->token, new Consultatie(), ['None']);

        $this->assertSame(-1, $result);
    }

    private function dataProvider()
    {
        yield ['VIEW', 'ROLE_Administrator', 1];
        yield ['VIEW', 'ROLE_Medic', 1];
        yield ['VIEW', 'ROLE_Psiholog', 1];
        yield ['VIEW', 'ROLE_Receptioner', -1];
        yield ['ADD_EDIT', 'ROLE_Administrator', 1];
        yield ['ADD_EDIT', 'ROLE_Medic', 1];
        yield ['ADD_EDIT', 'ROLE_Psiholog', 1];
        yield ['ADD_EDIT', 'ROLE_Receptioner', 1];
        yield ['DELETE', 'ROLE_Administrator', 1];
        yield ['DELETE', 'ROLE_Medic', -1];
        yield ['DELETE', 'ROLE_Psiholog', -1];
        yield ['DELETE', 'ROLE_Receptioner', -1];
        yield ['CLOSE_ALL', 'ROLE_Administrator', 1];
        yield ['CLOSE_ALL', 'ROLE_Medic', -1];
        yield ['CLOSE_ALL', 'ROLE_Psiholog', -1];
        yield ['CLOSE_ALL', 'ROLE_Receptioner', 1];
    }
}
