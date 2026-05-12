<?php

namespace App\Security\Voter;

use App\Entity\Owner;
use App\Entity\Preturi;
use App\Entity\Servicii;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class PreturiVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, ['ADD_EDIT', 'DELETE'])
            && ($subject instanceof Preturi || $subject instanceof Servicii || $subject instanceof Owner);
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
