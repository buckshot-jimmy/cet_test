<?php

namespace App\Security\Voter;

use App\Entity\Pacienti;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class PacientiVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, ['ADD_EDIT', 'DELETE']) && $subject instanceof Pacienti;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        $rol = $user->getRole()->getDenumire();

        if ('ROLE_Test' === $rol) {
            return false;
        }

        switch ($attribute) {
            case 'DELETE':
                return 'ROLE_Administrator' === $rol;
            case 'ADD_EDIT':
                return in_array($rol, ['ROLE_Administrator', 'ROLE_Medic', 'ROLE_Psiholog', 'ROLE_Receptioner',
                    'ROLE_Asistent']
                );
        }

        return false;
    }
}
