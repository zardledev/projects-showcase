<?php

namespace App\Module\Finance;

use App\Core\CoreContext;
use App\Core\Module\ModuleInterface;

class FinanceModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'Finance';
    }

    public function getVersion(): string
    {
        return '0.1.0';
    }

    public function getDescription(): string
    {
        return 'Facturation, comptabilite et suivi des paiements.';
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
