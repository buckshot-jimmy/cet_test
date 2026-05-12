<?php

namespace App\Security\Voter;

use App\Entity\Programari;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ProgramariVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['ADD', 'EDIT', 'CANCEL']) && ($subject instanceof Programari);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        $rol = $user->getRole()->getDenumire();

        if ($rol === 'ROLE_Test') {
            return false;
        }

        switch ($attribute) {
            case 'ADD':
                return true;
            case 'EDIT':
            case 'CANCEL':
                return $rol === 'ROLE_Administrator' || $user->getId() === $subject->getPret()->getMedic()->getId();
        }

        return false;
    }
}