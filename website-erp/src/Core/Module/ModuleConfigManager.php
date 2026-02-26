<?php

namespace App\Core\Module;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Yaml\Yaml;

class ModuleConfigManager
{
    private string $configPath;
    /**
     * @var array<string, bool>
     */
    private array $availableModules = [];

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $projectDir,
        #[AutowireIterator('app.module')] iterable $modules,
    ) {
        $this->configPath = rtrim($projectDir, DIRECTORY_SEPARATOR) . '/config/packages/app_modules.yaml';
        foreach ($modules as $module) {
            if (!$module instanceof ModuleInterface) {
                continue;
            }
            $this->availableModules[$module->getName()] = $module->isEnabledByDefault();
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getModulesConfig(): array
    {
        if (!is_file($this->configPath)) {
            return $this->normalizeModules([]);
        }

        $data = Yaml::parseFile($this->configPath);
        if (!is_array($data)) {
            return $this->normalizeModules([]);
        }

        $parameters = $data['parameters'] ?? [];
        if (!is_array($parameters)) {
            return [];
        }

        $modules = $parameters['app.modules'] ?? [];
        if (!is_array($modules)) {
            return $this->normalizeModules([]);
        }

        return $this->normalizeModules($modules);
    }

    public function setEnabled(string $name, bool $enabled): void
    {
        $data = [];
        if (is_file($this->configPath)) {
            $parsed = Yaml::parseFile($this->configPath);
            if (is_array($parsed)) {
                $data = $parsed;
            }
        }

        $parameters = $data['parameters'] ?? [];
        if (!is_array($parameters)) {
            $parameters = [];
        }

        $modules = $parameters['app.modules'] ?? [];
        if (!is_array($modules)) {
            $modules = [];
        }

        $modules = $this->normalizeModules($modules);
        $module = $modules[$name] ?? [
            'enabled' => $enabled,
            'roles' => [],
            'dependencies' => [],
        ];
        if (!is_array($module)) {
            $module = [
                'enabled' => $enabled,
                'roles' => [],
                'dependencies' => [],
            ];
        }

        $module['enabled'] = $enabled;
        $module['roles'] = is_array($module['roles'] ?? null) ? $module['roles'] : [];
        $module['dependencies'] = is_array($module['dependencies'] ?? null) ? $module['dependencies'] : [];
        $modules[$name] = $module;
        $parameters['app.modules'] = $modules;
        $data['parameters'] = $parameters;

        $yaml = Yaml::dump($data, 4, 4);
        file_put_contents($this->configPath, $yaml);
    }

    /**
     * @param array<string, mixed> $modules
     * @return array<string, array<string, mixed>>
     */
    private function normalizeModules(array $modules): array
    {
        $normalized = [];

        foreach ($this->availableModules as $name => $enabledByDefault) {
            $current = $modules[$name] ?? [];
            if (!is_array($current)) {
                $current = [];
            }
            $current['enabled'] = $current['enabled'] ?? $enabledByDefault;
            $current['roles'] = is_array($current['roles'] ?? null) ? $current['roles'] : [];
            $current['dependencies'] = is_array($current['dependencies'] ?? null) ? $current['dependencies'] : [];
            $normalized[$name] = $current;
        }

        foreach ($modules as $name => $config) {
            if (isset($normalized[$name])) {
                continue;
            }
            $current = is_array($config) ? $config : [];
            $current['enabled'] = $current['enabled'] ?? true;
            $current['roles'] = is_array($current['roles'] ?? null) ? $current['roles'] : [];
            $current['dependencies'] = is_array($current['dependencies'] ?? null) ? $current['dependencies'] : [];
            $normalized[$name] = $current;
        }

        return $normalized;
    }
}
