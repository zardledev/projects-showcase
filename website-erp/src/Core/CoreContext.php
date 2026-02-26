<?php

namespace App\Core;

use App\Core\Config\ConfigurationService;
use App\Core\Event\EventBus;
use App\Core\Security\AuthService;
use App\Core\Security\PermissionService;

class CoreContext
{
    public function __construct(
        public readonly EventBus $eventBus,
        public readonly ConfigurationService $configuration,
        public readonly PermissionService $permissions,
        public readonly AuthService $auth,
    ) {}
}
