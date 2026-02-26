<?php

namespace App\Core\Module;

use App\Core\Config\ConfigurationService;
use App\Core\CoreContext;
use App\Core\Event\EventBus;
use App\Core\Security\AuthService;
use App\Core\Security\PermissionService;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class ModuleLoader
{
    /**
     * @var ModuleInterface[]
     */
    private array $modules = [];
    /**
     * @var array<string, ModuleInterface>
     */
    private array $modulesByName = [];
    private bool $booted = false;
    /**
     * @var array<string, string>
     */
    private array $statuses = [];

    /**
     * @param iterable<ModuleInterface> $modules
     */
    public function __construct(
        #[AutowireIterator('app.module')] iterable $modules,
        private EventBus $eventBus,
        private ConfigurationService $configuration,
        private ModuleConfigManager $moduleConfig,
        private PermissionService $permissions,
        private AuthService $auth,
    ) {
        foreach ($modules as $module) {
            $this->modules[] = $module;
            $this->modulesByName[$module->getName()] = $module;
        }
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $context = new CoreContext(
            $this->eventBus,
            $this->configuration,
            $this->permissions,
            $this->auth,
        );

        $enabled = $this->resolveEnabledModules();
        $visiting = [];
        $visited = [];

        foreach ($this->modules as $module) {
            $name = $module->getName();
            if (!in_array($name, $enabled, true)) {
                $this->statuses[$name] = 'disabled';
                continue;
            }
            $this->bootModule($module, $context, $enabled, $visiting, $visited);
        }

        $this->booted = true;
    }

    /**
     * @return ModuleInfo[]
     */
    public function list(): array
    {
        $modules = [];
        $enabled = $this->resolveEnabledModules();
        $moduleConfig = $this->moduleConfig->getModulesConfig();
        if (!is_array($moduleConfig)) {
            $moduleConfig = [];
        }

        foreach ($this->modules as $module) {
            $name = $module->getName();
            $status = $this->statuses[$name] ?? ($this->booted ? 'registered' : 'registered');
            $configDependencies = [];
            $configRoles = [];
            $config = $moduleConfig[$name] ?? [];
            if (is_array($config)) {
                $configDependencies = $config['dependencies'] ?? [];
                if (!is_array($configDependencies)) {
                    $configDependencies = [];
                }
                $configRoles = $config['roles'] ?? [];
                if (!is_array($configRoles)) {
                    $configRoles = [];
                }
            }
            $modules[] = new ModuleInfo(
                $name,
                $module->getVersion(),
                $module->getDescription(),
                $status,
                in_array($name, $enabled, true),
                array_values(array_unique($configRoles)),
                array_values(array_unique(array_merge($module->getDependencies(), $configDependencies))),
            );
        }

        return $modules;
    }

    /**
     * @return string[]
     */
    private function resolveEnabledModules(): array
    {
        $moduleConfig = $this->moduleConfig->getModulesConfig();
        if (!is_array($moduleConfig)) {
            $moduleConfig = [];
        }

        $enabledFromConfig = [];
        foreach ($moduleConfig as $name => $config) {
            if (!is_array($config)) {
                continue;
            }
            if (($config['enabled'] ?? null) === true) {
                $enabledFromConfig[] = (string) $name;
            }
        }

        $enabled = $this->configuration->get('app.modules.enabled', []);
        $disabled = $this->configuration->get('app.modules.disabled', []);

        if (!is_array($enabled)) {
            $enabled = [];
        }
        if (!is_array($disabled)) {
            $disabled = [];
        }

        if (count($enabled) === 0 && count($enabledFromConfig) > 0) {
            $enabled = $enabledFromConfig;
        }

        if (count($enabled) === 0) {
            foreach ($this->modules as $module) {
                if ($module->isEnabledByDefault()) {
                    $enabled[] = $module->getName();
                }
            }
        }

        if (count($disabled) > 0) {
            $enabled = array_values(array_diff($enabled, $disabled));
        }

        return $enabled;
    }

    /**
     * @param string[] $enabled
     * @param array<string, bool> $visiting
     * @param array<string, bool> $visited
     */
    private function bootModule(
        ModuleInterface $module,
        CoreContext $context,
        array $enabled,
        array &$visiting,
        array &$visited,
    ): bool {
        $name = $module->getName();
        $moduleConfig = $this->moduleConfig->getModulesConfig();
        if (!is_array($moduleConfig)) {
            $moduleConfig = [];
        }
        $config = $moduleConfig[$name] ?? [];
        $configDependencies = [];
        if (is_array($config)) {
            $configDependencies = $config['dependencies'] ?? [];
            if (!is_array($configDependencies)) {
                $configDependencies = [];
            }
        }
        $dependencies = array_values(array_unique(array_merge($module->getDependencies(), $configDependencies)));

        if (isset($visited[$name])) {
            return $this->statuses[$name] === 'booted';
        }
        if (isset($visiting[$name])) {
            $this->statuses[$name] = 'circular_dependency';
            return false;
        }

        $visiting[$name] = true;
        $missing = [];

        foreach ($dependencies as $dependency) {
            $dependencyModule = $this->modulesByName[$dependency] ?? null;
            if ($dependencyModule === null) {
                $missing[] = $dependency;
                continue;
            }
            if (!in_array($dependency, $enabled, true)) {
                $missing[] = $dependency;
                continue;
            }
            if (!$this->bootModule($dependencyModule, $context, $enabled, $visiting, $visited)) {
                $missing[] = $dependency;
            }
        }

        if (count($missing) > 0) {
            $this->statuses[$name] = 'missing_dependencies';
            $visited[$name] = true;
            unset($visiting[$name]);
            return false;
        }

        $module->boot($context);
        $this->statuses[$name] = 'booted';
        $visited[$name] = true;
        unset($visiting[$name]);
        return true;
    }
}
