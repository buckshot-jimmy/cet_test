<?php

namespace App\Security\Voter;

use App\Entity\Owner;
use App\Entity\Pret;
use App\Entity\Serviciu;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class PretVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, ['ADD_EDIT', 'DELETE'])
            && ($subject instanceof Pret || $subject instanceof Serviciu || $subject instanceof Owner);
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
                return $rol === 'ROLE_Administrator';
        }

        return false;
    }
}
