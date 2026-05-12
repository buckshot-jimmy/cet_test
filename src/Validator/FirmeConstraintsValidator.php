<?php


namespace App\Validator;


use App\Entity\Owner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class FirmeConstraintsValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $em) {}

    public function validate($owner, Constraint $constraint)
    {
        foreach ($owner as $property => $value) {
            switch ($property) {
                case "cui":
                    $this->cui($value, $constraint);
                    $this->cuiUnic($value, $constraint, $owner['owner_id']);
                    break;
                case "denumire":
                    $this->denumireUnica($value, $constraint, $owner['owner_id']);
                    break;
                case "cont":
                    $this->iban($value, $constraint);
                    $this->ibanUnic($value, $constraint, $owner['owner_id']);
                    break;
                case "regCom":
                    $this->regCom($value, $constraint);
                    break;
                default:
                    break;
            }
        }
    }

    private function regCom($nrRegCom, $constraint)
    {
        if (!$nrRegCom) {
            return true;
        }

        if (preg_match('/^[JFC]\d{2}\/\d{1,6}\/\d{4}$/', $nrRegCom) !== 1) {
            $this->context->buildViolation($constraint->messages['regCom'])->addViolation();

            return false;
        }

        return true;
    }

    private function iban($iban, $constraint)
    {
        if (!$iban) {
            return false;
        }

        $iban = strtoupper(str_replace(' ', '', $iban));

        if (strlen($iban) < 15 || strlen($iban) > 34) {
            $this->context->buildViolation($constraint->messages['iban'])->addViolation();

            return false;
        }

        $rearranged = substr($iban, 4) . substr($iban, 0, 4);

        $numericIBAN = '';
        for ($i = 0; $i < strlen($rearranged); $i++) {
            $char = $rearranged[$i];
            if (ctype_alpha($char)) {
                $numericIBAN .= (ord($char) - 55);
            } else {
                $numericIBAN .= $char;
            }
        }

        $remainder = 0;
        for ($i = 0; $i < strlen($numericIBAN); $i++) {
            $remainder = ($remainder * 10 + intval($numericIBAN[$i])) % 97;
        }

        if ($remainder !== 1) {
            $this->context->buildViolation($constraint->messages['iban'])->addViolation();

            return false;
        }

        return true;
    }

    private function denumireUnica($value, $constraint, $id)
    {
        $owner = $this->em->getRepository(Owner::class)->findOneBy(['denumire' => $value]);

        if ($owner && $owner->getId() !== (int) $id) {
            $this->context->buildViolation($constraint->messages['denumireUnica'])->addViolation();

            return false;
        }

        return true;
    }

    private function ibanUnic($value, $constraint, $id)
    {
        if (!$value) {
            return true;
        }

        $owner = $this->em->getRepository(Owner::class)->findOneBy(['contBancar' => $value]);

        if ($owner && $owner->getId() !== (int) $id) {
            $this->context->buildViolation($constraint->messages['ibanUnic'])->addViolation();

            return false;
        }

        return true;
    }

    private function cuiUnic($value, $constraint, $id)
    {
        $owner = $this->em->getRepository(Owner::class)->findOneBy(['cui' => $value]);

        if ($owner && $owner->getId() !== (int) $id) {
            $this->context->buildViolation($constraint->messages['cuiUnic'])->addViolation();

            return false;
        }

        return true;
    }

    private function cui($cui, $constraint)
    {
        if (strpos($cui, 'RO') !== false) {
            $cui = str_replace('RO', '', $cui);
        }

        if (!is_numeric($cui)) {
            $this->context->buildViolation($constraint->messages['cui'])->addViolation();

            return false;
        }

        if (strlen($cui) < 4 || strlen($cui) > 10) {
            $this->context->buildViolation($constraint->messages['cui'])->addViolation();

            return false;
        }

        $cifraControl = substr($cui, -1);
        $cui = substr($cui, 0, -1);

        while (strlen($cui) != 9) {
            $cui = '0' . $cui;
        }

        $cuiSuma = $cui[0] * 7 + $cui[1] * 5 + $cui[2] * 3 + $cui[3] * 2 + $cui[4] * 1 + $cui[5] * 7 + $cui[6] * 5
            + $cui[7] * 3 + $cui[8] * 2;
        $suma = $cuiSuma * 10;
        $rest = fmod($suma, 11);

        if ($rest == 10) {
            $rest = 0;
        }

        if ($rest == $cifraControl) {
            return true;
        } else {
            $this->context->buildViolation($constraint->messages['cui'])->addViolation();

            return false;
        }
    }
}