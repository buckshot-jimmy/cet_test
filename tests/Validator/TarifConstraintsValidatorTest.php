<?php

namespace App\Tests\Validator;

use App\Entity\Preturi;
use App\Repository\PreturiRepository;
use App\Validator\TarifConstraints;
use App\Validator\TarifConstraintsValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class TarifConstraintsValidatorTest extends ConstraintValidatorTestCase
{
    private $repo;

    /**
     * @covers \App\Validator\TarifConstraintsValidator::__construct
     */
    public function testItCanBuildConstraintValidator()
    {
        $this->assertInstanceOf(TarifConstraintsValidator::class, $this->createValidator());
    }

    protected function createValidator()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->repo = $this->getMockBuilder(PreturiRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
            ->getMock();
        $em->method('getRepository')->willReturn($this->repo);

        return new TarifConstraintsValidator($em);
    }

    /**
     * @covers \App\Validator\TarifConstraintsValidator::validate
     * @covers \App\Validator\TarifConstraintsValidator::medicServiciuOwnerUnic
     * @covers \App\Validator\TarifConstraintsValidator::tarifNegativ
     *
     */
    public function testInvalidMedicServiciuOwnerUnicAddsViolation()
    {
        $pret = $this->createMock(Preturi::class);
        $this->repo->method('findOneBy')->willReturn($pret);
        $pret->method('getPret')->willReturn($pret);

        $constraint = new TarifConstraints();

        $this->validator->validate(['pret_medic' => 1, 'pret_serviciu' => 1, 'pret_owner' => 1], $constraint);

        $this->buildViolation($constraint->messages['medicServiciuOwner'])->assertRaised();
    }

    /**
     * @covers \App\Validator\TarifConstraintsValidator::validate
     * @covers \App\Validator\TarifConstraintsValidator::medicServiciuOwnerUnic
     * @covers \App\Validator\TarifConstraintsValidator::tarifNegativ
     *
     */
    public function testWithPretNotAddsViolation()
    {
        $constraint = new TarifConstraints();

        $this->validator->validate([
            'pret_id' => 1,
            'pret_pret' => 10,
            'pret_procentaj_medic' => 10
        ], $constraint);

        $this->assertNoViolation();
    }

    /**
     * @covers \App\Validator\TarifConstraintsValidator::validate
     * @covers \App\Validator\TarifConstraintsValidator::medicServiciuOwnerUnic
     * @covers \App\Validator\TarifConstraintsValidator::tarifNegativ
     */
    public function testWithPretIdAndNoTariffNotAddsViolations()
    {
        $this->repo->method('findOneBy')->willReturn(null);

        $constraint = new TarifConstraints();

        $this->validator->validate(['pret_id' => 1, 'pret_pret' => 10, 'pret_procentaj_medic' => 10], $constraint);

        $this->assertNoViolation();
    }

    /**
     * @covers \App\Validator\TarifConstraintsValidator::validate
     * @covers \App\Validator\TarifConstraintsValidator::medicServiciuOwnerUnic
     * @covers \App\Validator\TarifConstraintsValidator::tarifNegativ
     */
    public function testWithPretIdNotAddsViolations()
    {
        $this->repo->method('findOneBy')->willReturn(null);

        $constraint = new TarifConstraints();

        $this->validator->validate(['pret_id' => '', 'pret_pret' => 10, 'pret_procentaj_medic' => 10], $constraint);

        $this->assertNoViolation();
    }

    /**
     * @covers \App\Validator\TarifConstraintsValidator::validate
     * @covers \App\Validator\TarifConstraintsValidator::medicServiciuOwnerUnic
     * @covers \App\Validator\TarifConstraintsValidator::tarifNegativ
     */
    public function testWithPretIdAndNegativeAddsViolations()
    {
        $this->repo->method('findOneBy')->willReturn(null);

        $constraint = new TarifConstraints();

        $this->validator->validate(['pret_id' => '1', 'pret_pret' => -10, 'pret_procentaj_medic' => -10], $constraint);

        $this->buildViolation($constraint->messages['tarifNegativ'])->assertRaised();
    }

    /**
     * @covers \App\Validator\TarifConstraintsValidator::validate
     * @covers \App\Validator\TarifConstraintsValidator::medicServiciuOwnerUnic
     * @covers \App\Validator\TarifConstraintsValidator::tarifNegativ
     */
    public function testWithoutPretIdAndNegativeNotAddsViolations()
    {
        $this->repo->method('findOneBy')->willReturn(null);

        $constraint = new TarifConstraints();

        $this->validator->validate(['pret_id' => '', 'pret_pret' => -10, 'pret_procentaj_medic' => -10], $constraint);

        $this->assertNoViolation();
    }

    /**
     * @covers \App\Validator\PacientConstraintsValidator::validate
     * @covers \App\Validator\PacientConstraintsValidator::medicServiciuOwnerUnic
     * @covers \App\Validator\TarifConstraintsValidator::tarifNegativ
     * @dataProvider validDataProvider
     */
    public function testValidMedicServiciuOwnerUnicDoesNotAddViolation($pretId, $pret = 10, $procentajMedic = 10)
    {
        $constraint = new TarifConstraints();

        $this->validator->validate([
            'pret_id' => $pretId,
            'pret_pret' => $pret,
            'pret_procentaj_medic' => $procentajMedic
        ], $constraint);

        $this->assertNoViolation();
    }

    protected function validDataProvider()
    {
        yield ['pret_id' => ''];
        yield ['pret_id' => '1'];
    }
}
