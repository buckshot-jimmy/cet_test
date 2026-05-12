<?php

namespace App\Tests\Validator;

use App\Repository\ProgramariRepository;
use App\Validator\ProgramareConstraints;
use App\Validator\ProgramareConstraintsValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProgramareConstraintsValidatorTest extends ConstraintValidatorTestCase
{
    private $repo;

    /**
     * @covers \App\Validator\ProgramareConstraintsValidator::__construct
     */
    public function testItCanBuildConstraintValidator()
    {
        $this->assertInstanceOf(ProgramareConstraintsValidator::class, $this->createValidator());
    }

    protected function createValidator()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->repo = $this->createMock(ProgramariRepository::class);
        $em->method('getRepository')->willReturn($this->repo);

        return new ProgramareConstraintsValidator($em);
    }

    /**
     * @covers \App\Validator\ProgramareConstraintsValidator::validate
     * @covers \App\Validator\ProgramareConstraintsValidator::checkAvailability
     */
    public function testUnavailableTimeAddsViolation()
    {
        $this->repo->method('checkAvailability')->willReturn(false);

        $constraint = new ProgramareConstraints();

        $this->validator->validate([
            'programare_data' => '01-01-2086', 'programare_ora' => '09:00', 'programare_medic' => 1], $constraint);

        $this->buildViolation($constraint->messages['unavailableTime'])->assertRaised();
    }

    /**
     * @covers \App\Validator\ProgramareConstraintsValidator::validate
     * @covers \App\Validator\ProgramareConstraintsValidator::checkAvailability
     */
    public function testUnavailableTimeNoViolation()
    {
        $this->repo->method('checkAvailability')->willReturn(true);

        $constraint = new ProgramareConstraints();

        $this->validator->validate([
            'programare_data' => '01-01-2086', 'programare_ora' => '09:00', 'programare_medic' => 1], $constraint);

        $this->assertNoViolation();
    }

    /**
     * @covers \App\Validator\ProgramareConstraintsValidator::validate
     * @covers \App\Validator\ProgramareConstraintsValidator::checkPastTime
     */
    public function testPastTimeAddsViolation()
    {
        $this->repo->method('checkAvailability')->willReturn(true);

        $constraint = new ProgramareConstraints();

        $this->validator->validate([
            'programare_data' => '01-01-2026', 'programare_ora' => '09:00', 'programare_medic' => 1], $constraint);

        $this->buildViolation($constraint->messages['pastTime'])->assertRaised();
    }

    /**
     * @covers \App\Validator\ProgramareConstraintsValidator::validate
     * @covers \App\Validator\ProgramareConstraintsValidator::checkPastTime
     */
    public function testPastTimeNoViolation()
    {
        $this->repo->method('checkAvailability')->willReturn(true);

        $constraint = new ProgramareConstraints();

        $this->validator->validate([
            'programare_data' => '01-01-2096', 'programare_ora' => '09:00', 'programare_medic' => 1], $constraint);

        $this->assertNoViolation();
    }
}
