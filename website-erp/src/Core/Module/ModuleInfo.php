<?php

namespace App\Core\Module;

class ModuleInfo
{
    public function __construct(
        public readonly string $name,
        public readonly string $version,
        public readonly string $description,
        public readonly string $status,
        public readonly bool $enabled,
        public readonly array $roles = [],
        public readonly array $dependencies = [],
    ) {}
}
