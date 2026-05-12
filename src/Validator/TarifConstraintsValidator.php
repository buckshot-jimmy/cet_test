<?php


namespace App\Validator;


use App\Entity\Preturi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class TarifConstraintsValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $em) {}

    public function validate($tarif, Constraint $constraint)
    {
        $this->medicServiciuOwnerUnic($tarif, $constraint);
        $this->tarifNegativ($tarif, $constraint);
    }

    private function medicServiciuOwnerUnic($tarif, $constraint)
    {
        if (isset($tarif['pret_id']) && !empty($tarif['pret_id'])) {
            return true;
        }

        $tarif = $this->em->getRepository(Preturi::class)
            ->findOneBy(['medic' => $tarif['pret_medic'], 'serviciu' => $tarif['pret_serviciu'],
                'owner' => $tarif['pret_owner']]);

        if ($tarif) {
            $this->context->buildViolation($constraint->messages['medicServiciuOwner'])->addViolation();

            return false;
        }

        return true;
    }

    private function tarifNegativ($tarif, $constraint)
    {
        if (isset($tarif['pret_id']) && !empty($tarif['pret_id'])
            && ($tarif['pret_pret'] <= 0 || $tarif['pret_procentaj_medic'] <= 0)) {
            $this->context->buildViolation($constraint->messages['tarifNegativ'])->addViolation();

            return false;
        }

        return true;
    }
}