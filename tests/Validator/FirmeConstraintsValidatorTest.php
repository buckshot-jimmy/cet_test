<?php

namespace App\Tests\Validator;

use App\Entity\Owner;
use App\Repository\OwnerRepository;
use App\Validator\FirmeConstraints;
use App\Validator\FirmeConstraintsValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class FirmeConstraintsValidatorTest extends ConstraintValidatorTestCase
{
    private $repo;

    /**
     * @covers \App\Validator\FirmeConstraintsValidator::__construct
     */
    public function testItCanBuildConstraintValidator()
    {
        $this->assertInstanceOf(FirmeConstraintsValidator::class, $this->createValidator());
    }

    protected function createValidator()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->repo = $this->getMockBuilder(OwnerRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
            ->getMock();
        $em->method('getRepository')->willReturn($this->repo);

        return new FirmeConstraintsValidator($em);
    }

    /**
     * @covers \App\Validator\FirmeConstraintsValidator::validate
     * @covers \App\Validator\FirmeConstraintsValidator::cuiUnic
     */
    public function testInvalidCuiUnicAddsViolation()
    {
        $owner = $this->createMock(Owner::class);

        $this->repo->method('findOneBy')->willReturn($owner);
        $owner->method('getId')->willReturn(1);

        $constraint = new FirmeConstraints();

        $this->validator->validate(['cui' => '42475870'], $constraint);

        $this->buildViolation($constraint->messages['cuiUnic'])->assertRaised();
    }

    /**
     * @covers \App\Validator\FirmeConstraintsValidator::validate
     * @covers \App\Validator\FirmeConstraintsValidator::cuiUnic
     */
    public function testValidCuiUnicDoesNotAddViolation()
    {
        $constraint = new FirmeConstraints();

        $this->validator->validate(['cui' => '29799772'], $constraint);

        $this->assertNoViolation();
    }

    /**
     * @covers \App\Validator\FirmeConstraintsValidator::validate
     * @covers \App\Validator\FirmeConstraintsValidator::ibanUnic
     * @covers \App\Validator\FirmeConstraintsValidator::regCom
     */
    public function testInvalidIbanUnicAddsViolation()
    {
        $owner = $this->createMock(Owner::class);

        $this->repo->method('findOneBy')->willReturn($owner);
        $owner->method('getId')->willReturn(1);

        $constraint = new FirmeConstraints();

        $this->validator->validate(['cont' => 'RO49AAAA1B31007593840000', 'regCom' => ''], $constraint);

        $this->buildViolation($constraint->messages['ibanUnic'])->assertRaised();
    }

    /**
     * @covers \App\Validator\FirmeConstraintsValidator::validate
     * @covers \App\Validator\FirmeConstraintsValidator::iban
     * @covers \App\Validator\FirmeConstraintsValidator::regCom
     * @dataProvider invalidIbanProvider
     */
    public function testInvalidIbanAddsViolation($cont)
    {
        $constraint = new FirmeConstraints();

        $this->validator->validate(['cont' => $cont, 'regCom' => 'J12/1234/2020'], $constraint);

        $this->buildViolation($constraint->messages['iban'])->assertRaised();
    }

    private function invalidIbanProvider()
    {
        yield ['cont' => 'RO49AAAA1B31007593840001'];
        yield ['cont' => '31007593840001'];
    }

    /**
     * @covers \App\Validator\FirmeConstraintsValidator::validate
     * @covers \App\Validator\FirmeConstraintsValidator::ibanUnic
     * @covers \App\Validator\FirmeConstraintsValidator::iban
     * @covers \App\Validator\FirmeConstraintsValidator::cui
     * @dataProvider ibanProvider
     */
    public function testValidIbanUnicDoesNotAddViolation($cont)
    {
        $constraint = new FirmeConstraints();

        $this->validator->validate(['cont' => $cont, 'cui' => '000000051'], $constraint);

        $this->assertNoViolation();
    }

    private function ibanProvider()
    {
        yield ['cont' => 'RO49AAAA1B31007593840000'];
        yield ['cont' => ''];
    }

    /**
     * @covers \App\Validator\FirmeConstraintsValidator::validate
     * @covers \App\Validator\FirmeConstraintsValidator::denumireUnica
     */
    public function testInvalidDenumireUnicaAddsViolation()
    {
        $owner = $this->createMock(Owner::class);

        $this->repo->method('findOneBy')->willReturn($owner);
        $owner->method('getId')->willReturn(1);

        $constraint = new FirmeConstraints();

        $this->validator->validate(['denumire' => 'abc'], $constraint);

        $this->buildViolation($constraint->messages['denumireUnica'])->assertRaised();
    }

    /**
     * @covers \App\Validator\FirmeConstraintsValidator::validate
     * @covers \App\Validator\FirmeConstraintsValidator::denumireUnica
     */
    public function testValidDenumireUnicaDoesNotAddViolation()
    {
        $constraint = new FirmeConstraints();

        $this->validator->validate(['denumire' => 'xyz'], $constraint);

        $this->assertNoViolation();
    }

    /**
     * @covers \App\Validator\FirmeConstraintsValidator::validate
     * @covers \App\Validator\FirmeConstraintsValidator::cui
     * @dataProvider dataProvider
     */
    public function testInvalidCuiInvalidAddsViolation($cui)
    {
        $constraint = new FirmeConstraints();

        $this->validator->validate(['cui' => $cui], $constraint);

        $this->buildViolation($constraint->messages['cui'])->assertRaised();
    }

    /**
     * @covers \App\Validator\FirmeConstraintsValidator::validate
     * @covers \App\Validator\FirmeConstraintsValidator::regCom
     */
    public function testInvalidNrRegComInvalidAddsViolation()
    {
        $constraint = new FirmeConstraints();

        $this->validator->validate(['regCom' => 'Abc123'], $constraint);

        $this->buildViolation($constraint->messages['regCom'])->assertRaised();
    }

    /**
     * @covers \App\Validator\FirmeConstraintsValidator::validate
     */
    public function testWithNonExistentProperty()
    {
        $constraint = new FirmeConstraints();

        $this->validator->validate(['none' => '42475870'], $constraint);

        $this->assertNoViolation();
    }

    protected function dataProvider()
    {
        yield ['cui' => 'RO123'];
        yield ['cui' => 'RORO'];
        yield ['cui' => '158457895'];
        yield ['cui' => '000000066'];
    }
}
