<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class UserVoter extends Voter
{
    const ROL_ADMIN = 'ROLE_Administrator';
    const ROL_RECEPTIONER = 'ROLE_Receptioner';

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, ['ADD', 'EDIT', 'DELETE', 'VIEW']) && $subject instanceof User;
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
            case 'ADD':
                return $rol === self::ROL_ADMIN;
            case 'EDIT':
                return $user->getId() === $subject->getId() || $rol === self::ROL_ADMIN;
            case 'VIEW':
                return $rol !== self::ROL_RECEPTIONER;
        }

        return false;
    }
}
