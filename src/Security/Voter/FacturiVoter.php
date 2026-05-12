<?php

namespace App\Security\Voter;

use App\Entity\Facturi;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class FacturiVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['VIEW', 'ADD']) && $subject instanceof Facturi;
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
                return $rol === 'ROLE_Administrator';
            case 'ADD':
                return in_array($rol, ['ROLE_Administrator', 'ROLE_Medic', 'ROLE_Psiholog', 'ROLE_Receptioner']);
        }

        return false;
    }
}