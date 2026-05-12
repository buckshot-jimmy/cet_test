<?php

namespace App\Security\Voter;

use App\Entity\RapoarteColaboratori;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class RapoarteVoter extends Voter
{
    const ROL_ADMIN = 'ROLE_Administrator';

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, ['ADD_EDIT', 'DELETE', 'VIEW']) && $subject instanceof RapoarteColaboratori;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
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
            case 'DELETE':
            case 'ADD_EDIT':
                return $rol === self::ROL_ADMIN;
            case 'VIEW':
                return $rol === self::ROL_ADMIN || $rol === 'ROLE_Medic';
        }

        return false;
    }
}
