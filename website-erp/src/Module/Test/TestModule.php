<?php

namespace App\Module\Test;

use App\Core\CoreContext;
use App\Core\Module\ModuleInterface;

class TestModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'Test';
    }

    public function getVersion(): string
    {
        return '0.1.0';
    }

    public function getDescription(): string
    {
        return 'Module Test';
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function isEnabledByDefault(): bool
    {
        return true;
    }

    public function boot(CoreContext $context): void
    {
        $context->eventBus;
        $context->configuration;
        $context->permissions;
        $context->auth;
    }
}