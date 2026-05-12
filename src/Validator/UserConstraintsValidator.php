<?php


namespace App\Validator;


use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UserConstraintsValidator extends ConstraintValidator
{
    const ROLE_ADMIN = 'ROLE_Administrator';
    public function __construct(private EntityManagerInterface $em, private UserPasswordHasherInterface $hasher) {}

    public function validate($value, Constraint $constraint)
    {
        $this->checkNewPasswordSameOld($value, $constraint);
        $this->checkRoleChangeNotAdmin($value, $constraint);
        $this->checkPasswordChangeAnotherUser($value, $constraint);
        $this->checkPasswordChangeAnotherAdministrator($value, $constraint);
        $this->checkPasswordStrength($value, $constraint);
        $this->checkUniqueUsername($value, $constraint);
        $this->checkUniqueEmail($value, $constraint);
    }

    private function checkUniqueUsername($data, $constraint)
    {
        if (empty($data['username'])) {
            return true;
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $data['username']]);

        if ($user && $user->getUsername() === $data['username'] && $user->getId() === intval($data['editUserId'])) {
            return true;
        }

        if ($user) {
            $this->context->buildViolation($constraint->messages['usernameUnic'])->addViolation();

            return false;
        }

        return true;
    }

    private function checkUniqueEmail($data, $constraint)
    {
        if (empty($data['email'])) {
            return true;
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        if ($user && $user->getEmail() === $data['email'] && $user->getId() === intval($data['editUserId'])) {
            return true;
        }

        if ($user) {
            $this->context->buildViolation($constraint->messages['emailUnic'])->addViolation();

            return false;
        }

        return true;
    }

    private function checkNewPasswordSameOld($formData, $constraint)
    {
        if (empty($formData['editUserId'])) {
            return true;
        }

        $editedUser = $this->em->getRepository(User::class)->find($formData['editUserId']);

        $password = $formData['edit_profile_password'] ?? $formData['password'];

        if ($this->hasher->isPasswordValid($editedUser, $password)) {
            $this->context->buildViolation($constraint->messages['parolaCurenta'])->addViolation();

            return false;
        }

        return true;
    }

    private function checkRoleChangeNotAdmin($formData, $constraint)
    {
        if (empty($formData['editUserId'])) {
            return true;
        }

        $loggedUser = $this->em->getRepository(User::class)->find($formData['loggedUserId']);

        if (isset($formData['role_name']) && $formData['role_name'] === $loggedUser->getRole()->getDenumire()) {
            return true;
        }

        if (self::ROLE_ADMIN !== $loggedUser->getRole()->getDenumire()) {
            $this->context->buildViolation($constraint->messages['modificareRol'])->addViolation();

            return false;
        }

        return true;
    }

    private function checkPasswordChangeAnotherUser($formData, $constraint)
    {
        if (empty($formData['editUserId'])) {
            return true;
        }

        $loggedUser = $this->em->getRepository(User::class)->find($formData['loggedUserId']);

        if ($formData['loggedUserId'] === $formData['editUserId']) {
            return true;
        }

        if (self::ROLE_ADMIN !== $loggedUser->getRole()->getDenumire()) {
            $this->context->buildViolation($constraint->messages['modificareParolaAltUtilizator'])->addViolation();

            return false;
        }

        return true;
    }

    private function checkPasswordChangeAnotherAdministrator($formData, $constraint)
    {
        if (empty($formData['editUserId'])) {
            return true;
        }

        $editedUser = $this->em->getRepository(User::class)->find($formData['editUserId']);

        if ($formData['loggedUserId'] === $formData['editUserId']) {
            return true;
        }

        if (self::ROLE_ADMIN === $editedUser->getRole()->getDenumire()) {
            $this->context->buildViolation($constraint->messages['modificareParolaAltAdministrator'])->addViolation();

            return false;
        }

        return true;
    }

    private function checkPasswordStrength($formData, $constraint)
    {
        $password = $formData['edit_profile_password'] ?? $formData['password'];

        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{6,}$/';

        if (!preg_match($pattern, $password)) {
            $this->context->buildViolation($constraint->messages['parolaSlaba'])->addViolation();

            return false;
        }

        return true;
    }
}
