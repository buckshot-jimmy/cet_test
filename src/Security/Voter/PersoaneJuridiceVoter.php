<?php

namespace App\Security\Voter;

use App\Entity\Owner;
use App\Entity\PersoaneJuridice;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class PersoaneJuridiceVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['ADD_EDIT', 'VIEW', 'DELETE']) && $subject instanceof PersoaneJuridice;
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
            case 'VIEW':
            case 'ADD_EDIT':
            case 'DELETE':
                return $rol === 'ROLE_Administrator';
        }

        return false;
    }
}