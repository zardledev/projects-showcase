<?php

namespace App\Core\Security;

class UserIdentity
{
    /**
     * @param string[] $roles
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly array $roles = [],
    ) {}
}
