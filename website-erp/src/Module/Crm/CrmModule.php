<?php

namespace App\Module\Crm;

use App\Core\CoreContext;
use App\Core\Module\ModuleInterface;

class CrmModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'CRM';
    }

    public function getVersion(): string
    {
        return '0.1.0';
    }

    public function getDescription(): string
    {
        return 'Clients, pipelines et interactions.';
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
