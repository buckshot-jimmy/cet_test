<?php

namespace App\Validator;

use App\Entity\Programari;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ProgramareConstraintsValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $em) {}

    public function validate(mixed $value, Constraint $constraint)
    {
        $this->checkAvailability($value, $constraint);
        $this->checkPastTime($value, $constraint);
    }

    private function checkAvailability($value, $constraint)
    {
        $free = $this->em->getRepository(Programari::class)->checkAvailability($value);

        if (!$free) {
            $this->context->buildViolation($constraint->messages['unavailableTime'])->addViolation();

            return false;
        }

        return true;
    }

    private function checkPastTime($value, $constraint)
    {
        $date = $value['programare_data'];
        $time = $value['programare_ora'];
        $tz = new \DateTimeZone('Europe/Bucharest');
        $targetDateTime = \DateTime::createFromFormat('d-m-Y H:i', $date . ' ' . $time, $tz);
        $targetDateTime->modify('+5 minutes');

        if ($targetDateTime < new \DateTime()) {
            $this->context->buildViolation($constraint->messages['pastTime'])->addViolation();

            return false;
        }

        return true;
    }
}