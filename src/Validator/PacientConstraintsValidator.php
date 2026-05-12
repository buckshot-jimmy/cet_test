<?php


namespace App\Validator;


use App\Entity\Pacienti;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PacientConstraintsValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $em) {}

    public function validate($pacient, Constraint $constraint)
    {
        foreach ($pacient as $property => $value) {
            switch ($property) {
                case "cnp":
                    $this->cnpIdUnic($value, $constraint, $pacient->id);
                    $this->cnp($value, $constraint, $pacient->tara);
                    break;
                default:
                    break;
            }
        }
    }

    private function cnpIdUnic($value, $constraint, $id)
    {
        $pacient = $this->em->getRepository(Pacienti::class)->findOneBy(['cnp' => $value]);

        if ($pacient && $pacient->getId() !== (int) $id) {
            $this->context->buildViolation($constraint->messages['cnpIdUnic'])->addViolation();

            return false;
        }

        return true;
    }

    private function cnp($pCnp, $constraint, $tara)
    {
        if ($tara !== 'Romania') {
            return true;
        }

        if (strlen($pCnp) != 13) {
            $this->context->buildViolation($constraint->messages['cnp'])->addViolation();

            return false;
        }

        $cnp = str_split($pCnp);

        unset($pCnp);

        $hashTable  = [2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9];

        $hashResult = 0;

        for ($i = 0; $i < 13; $i++) {
            if (!is_numeric($cnp[$i])) {
                $this->context->buildViolation($constraint->messages['cnp'])->addViolation();

                return false;
            }

            $cnp[$i] = (int) $cnp[$i];

            if ($i < 12) {
                $hashResult += $cnp[$i] * $hashTable[$i];
            }
        }

        unset($hashTable, $i);

        $hashResult = $hashResult % 11;

        if ($hashResult == 10) {
            $hashResult = 1;
        }

        $year = ($cnp[1] * 10) + $cnp[2];

        switch ($cnp[0]) {
            case 1:
            case 2 :
                $year += 1900;
                break; // cetateni romani nascuti intre 1 ian 1900 si 31 dec 1999
            case 3:
            case 4 :
                $year += 1800;
                break; // cetateni romani nascuti intre 1 ian 1800 si 31 dec 1899
            case 5:
            case 6 :
                $year += 2000;
                break; // cetateni romani nascuti intre 1 ian 2000 si 31 dec 2099
            case 7:
            case 8 :
            case 9 : // rezidenti si Cetateni Straini
                $year += 2000;
                if ($year > (int) date('Y') - 14) {
                    $year -= 100;
                }
                break;
            default : {
                $this->context->buildViolation($constraint->messages['cnp'])->addViolation();
                $hashResult = 999;
                break;
            }
        }

        return ($year > 1800 && $year < 2099 && $cnp[12] == $hashResult);
    }
}