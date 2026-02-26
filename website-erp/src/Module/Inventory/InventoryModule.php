<?php

namespace App\Module\Inventory;

use App\Core\CoreContext;
use App\Core\Module\ModuleInterface;

class InventoryModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'Stock';
    }

    public function getVersion(): string
    {
        return '0.1.0';
    }

    public function getDescription(): string
    {
        return 'Reservation et ajustement des niveaux de stock.';
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
