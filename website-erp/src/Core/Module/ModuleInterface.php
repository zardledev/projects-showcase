<?php

namespace App\Core\Module;

use App\Core\CoreContext;

interface ModuleInterface
{
    public function getName(): string;

    public function getVersion(): string;

    public function getDescription(): string;

    /**
     * @return string[]
     */
    public function getDependencies(): array;

    public function isEnabledByDefault(): bool;

    public function boot(CoreContext $context): void;
}
