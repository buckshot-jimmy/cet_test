<?php

namespace App\Tests\Validator;

use App\Entity\Facturi;
use App\Entity\Pacienti;
use App\Repository\FacturiRepository;
use App\Validator\FacturaConstraints;
use App\Validator\FacturaConstraintsValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class FacturaConstraintsValidatorTest extends ConstraintValidatorTestCase
{
    private $repo;

    protected function createValidator()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->repo = $this->getMockBuilder(FacturiRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
            ->getMock();
        $em->method('getRepository')->willReturn($this->repo);

        return new FacturaConstraintsValidator($em);
    }

    /**
     * @covers \App\Validator\FacturaConstraintsValidator::__construct
     */
    public function testItCanBuildConstraintValidator()
    {
        $this->assertInstanceOf(FacturaConstraintsValidator::class, $this->createValidator());
    }

    /**
     * @covers \App\Validator\FacturaConstraintsValidator::validate
     * @covers \App\Validator\FacturaConstraintsValidator::facturaUnica
     */
    public function testItBuildsViolationWithFacturaUnica()
    {
        $factura = $this->createMock(Facturi::class);
        $pacient = $this->createMock(Pacienti::class);
        $factura->method('getData')->willReturn(new \DateTimeImmutable('2024-01-01'));
        $factura->method('getPacient')->willReturn($pacient);
        $this->repo->method('findOneBy')->willReturn($factura);

        $constraint = new FacturaConstraints();

        $this->validator->validate($factura, $constraint);

        $this->buildViolation($constraint->messages['facturaUnica'])->assertRaised();
    }

    /**
     * @covers \App\Validator\FacturaConstraintsValidator::validate
     * @covers \App\Validator\FacturaConstraintsValidator::facturaUnica
     */
    public function testItBuildsNoViolationWithFacturaUnica()
    {
        $factura = $this->createMock(Facturi::class);
        $pacient = $this->createMock(Pacienti::class);
        $factura->method('getData')->willReturn(new \DateTimeImmutable('2024-01-01'));
        $factura->method('getPacient')->willReturn($pacient);
        $this->repo->method('findOneBy')->willReturn(null);

        $constraint = new FacturaConstraints();

        $this->validator->validate($factura, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @covers \App\Validator\FacturaConstraintsValidator::validate
     * @covers \App\Validator\FacturaConstraintsValidator::noClient
     */
    public function testItBuildsViolationWithNoClient()
    {
        $factura = $this->createMock(Facturi::class);
        $factura->method('getData')->willReturn(new \DateTimeImmutable('2024-01-01'));
        $factura->method('getPacient')->willReturn(null);
        $this->repo->method('findOneBy')->willReturn(null);

        $constraint = new FacturaConstraints();

        $this->validator->validate($factura, $constraint);

        $this->buildViolation($constraint->messages['noClient'])->assertRaised();
    }

    /**
     * @covers \App\Validator\FacturaConstraintsValidator::validate
     * @covers \App\Validator\FacturaConstraintsValidator::noClient
     */
    public function testItBuildsNoViolationWithNoClient()
    {
        $factura = $this->createMock(Facturi::class);
        $pacient = $this->createMock(Pacienti::class);
        $factura->method('getData')->willReturn(new \DateTimeImmutable('2024-01-01'));
        $factura->method('getPacient')->willReturn($pacient);
        $this->repo->method('findOneBy')->willReturn(null);

        $constraint = new FacturaConstraints();

        $this->validator->validate($factura, $constraint);

        $this->assertNoViolation();
    }
}
