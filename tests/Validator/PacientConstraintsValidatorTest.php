<?php

namespace App\Tests\Validator;

use App\DTO\PacientiDTO;
use App\Entity\Pacienti;
use App\Repository\PacientiRepository;
use App\Validator\PacientConstraints;
use App\Validator\PacientConstraintsValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class PacientConstraintsValidatorTest extends ConstraintValidatorTestCase
{
    private $repo;
    private $dto;

    /**
     * @covers \App\Validator\PacientConstraintsValidator::__construct
     */
    public function testItCanBuildConstraintValidator()
    {
        $this->assertInstanceOf(PacientConstraintsValidator::class, $this->createValidator());
    }

    protected function createValidator()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->repo = $this->getMockBuilder(PacientiRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
            ->getMock();
        $em->method('getRepository')->willReturn($this->repo);

        $this->dto = new PacientiDTO(1, 'n', 'p', '1790630060774', '0745545689' ,
            '', 'ciprianmarta.cm@gmail.com', 'a', 'Alba', 'Baciu', 'Romania',
            'M', '30-06-1979', 'l', 'o', '2026-01-01', false,
            'o', 1, 1, [], []
        );

        return new PacientConstraintsValidator($em);
    }

    /**
     * @covers \App\Validator\PacientConstraintsValidator::validate
     * @covers \App\Validator\PacientConstraintsValidator::cnpIdUnic
     */
    public function testInvalidCnpUnicAddsViolation()
    {
        $pacient = $this->createMock(Pacienti::class);
        $this->repo->method('findOneBy')->willReturn($pacient);

        $constraint = new PacientConstraints();

        $this->validator->validate(['cnp' => '1212232354589'], $constraint);

        $this->buildViolation($constraint->messages['cnpIdUnic'])
            ->assertRaised();
    }

    /**
     * @covers \App\Validator\PacientConstraintsValidator::validate
     * @covers \App\Validator\PacientConstraintsValidator::cnpIdUnic
     */
    public function testValidCnpUnicDoesNotAddViolation()
    {
        $constraint = new PacientConstraints();

        $this->validator->validate(['cnp' => '1790630060774', 'tara' => 'Romania'], $constraint);

        $this->assertNoViolation();
    }

    /**
     * @covers \App\Validator\PacientConstraintsValidator::validate
     * @covers \App\Validator\PacientConstraintsValidator::cnpIdUnic
     */
    public function testValidCnpUnicSameCnpDoesNotAddViolation()
    {
        $pacient = $this->createMock(Pacienti::class);
        $this->repo->method('findOneBy')->willReturn($pacient);
        $pacient->method('getCnp')->willReturn('1212232354589');
        $pacient->method('getId')->willReturn(1);
        $this->dto->id = 1;
        $this->dto->cnp = '1212232354589';
        $this->dto->tara = 'Romania';

        $constraint = new PacientConstraints();

        $this->validator->validate($this->dto, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @covers \App\Validator\PacientConstraintsValidator::validate
     * @covers \App\Validator\PacientConstraintsValidator::cnp
     * @dataProvider invalidDataProvider
     */
    public function testInvalidCnpInvalidAddsViolation($cnp, $tara)
    {
        $constraint = new PacientConstraints();

        $this->dto->cnp = $cnp;
        $this->dto->tara = $tara;
        $this->validator->validate($this->dto, $constraint);

        $this->buildViolation($constraint->messages['cnp'])->assertRaised();
    }

    /**
     * @covers \App\Validator\PacientConstraintsValidator::validate
     * @covers \App\Validator\PacientConstraintsValidator::cnp
     * @dataProvider validDataProvider
     */
    public function testValidCnpDoesNotAddViolation($cnp, $tara)
    {
        $constraint = new PacientConstraints();

        $this->dto->cnp = $cnp;
        $this->dto->tara = $tara;
        $this->validator->validate($this->dto, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @covers \App\Validator\PacientConstraintsValidator::validate
     */
    public function testWithNonExistentProperty()
    {
        $constraint = new PacientConstraints();

        $this->validator->validate(['none' => '42475870'], $constraint);

        $this->assertNoViolation();
    }

    protected function invalidDataProvider()
    {
        yield ['cnp' => '0234568978452', 'tara' => 'Romania'];
        yield ['cnp' => '92345689784521', 'tara' => 'Romania'];
        yield ['cnp' => 'a345689784521', 'tara' => 'Romania'];
    }

    protected function validDataProvider()
    {
        yield ['cnp' => '1790630060774', 'tara' => 'Romania'];
        yield ['cnp' => '5021027428362', 'tara' => 'Romania'];
        yield ['cnp' => '7021027424852', 'tara' => 'Romania'];
        yield ['cnp' => '1790630060774', 'tara' => 'Japonia'];
    }
}
