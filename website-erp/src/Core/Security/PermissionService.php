<?php

namespace App\Core\Security;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class PermissionService
{
    public function __construct(private AuthorizationCheckerInterface $checker) {}

    public function isGranted(string $permission, ?UserInterface $user = null): bool
    {
        return $this->checker->isGranted($permission, $user);
    }
}
