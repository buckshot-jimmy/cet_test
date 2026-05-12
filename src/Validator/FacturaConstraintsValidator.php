<?php

namespace App\Validator;

use App\Entity\Facturi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class FacturaConstraintsValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $em) {}

    public function validate(mixed $value, Constraint $constraint)
    {
        $this->facturaUnica($value, $constraint);
        $this->noClient($value, $constraint);
    }

    private function noClient($value, $constraint)
    {
        if (!$value->getPacient() && !$value->getClientPj()) {
            $this->context->buildViolation($constraint->messages['noClient'])->addViolation();
            return false;
        }

        return true;
    }

    private function facturaUnica($value, $constraint)
    {
        $factura = $this->em->getRepository(Facturi::class)
            ->findOneBy([
                'serie' => $value->getSerie(),
                'numar' => $value->getNumar(),
                'data' => new \DateTime($value->getData()->format("Y-m-d"))
            ]);

        if ($factura) {
            $this->context->buildViolation($constraint->messages['facturaUnica'])->addViolation();
            return false;
        }

        return true;
    }
}