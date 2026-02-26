<?php

namespace App\Core\Security;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

class AuthService
{
    public function __construct(private Security $security) {}

    public function getCurrentUser(): ?UserIdentity
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return null;
        }

        return new UserIdentity(
            $user->getId() ?? 0,
            $user->getUserIdentifier(),
            $user->getRoles(),
        );
    }
}
