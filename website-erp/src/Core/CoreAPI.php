<?php

namespace App\Core;

use App\Core\Config\ConfigurationService;
use App\Core\Event\EventBus;
use App\Core\Module\ModuleLoader;
use App\Core\Security\AuthService;
use App\Core\Security\PermissionService;

class CoreAPI
{
    public function __construct(
        private EventBus $eventBus,
        private ModuleLoader $moduleLoader,
        private ConfigurationService $configuration,
        private PermissionService $permissions,
        private AuthService $auth,
    ) {}

    public function emit(object $event): void
    {
        $this->eventBus->dispatch($event);
    }

    public function bootModules(): void
    {
        $this->moduleLoader->boot();
    }

    public function modules(): array
    {
        return $this->moduleLoader->list();
    }

    public function config(): ConfigurationService
    {
        return $this->configuration;
    }

    public function permissions(): PermissionService
    {
        return $this->permissions;
    }

    public function auth(): AuthService
    {
        return $this->auth;
    }
}
