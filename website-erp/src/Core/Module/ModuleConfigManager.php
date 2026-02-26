<?php

namespace App\Core\Module;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;

class ModuleConfigManager
{
    private string $configPath;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $projectDir,
    ) {
        $this->configPath = rtrim($projectDir, DIRECTORY_SEPARATOR) . '/config/packages/app_modules.yaml';
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getModulesConfig(): array
    {
        if (!is_file($this->configPath)) {
            return [];
        }

        $data = Yaml::parseFile($this->configPath);
        if (!is_array($data)) {
            return [];
        }

        $parameters = $data['parameters'] ?? [];
        if (!is_array($parameters)) {
            return [];
        }

        $modules = $parameters['app.modules'] ?? [];
        if (!is_array($modules)) {
            return [];
        }

        return $modules;
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

        $module = $modules[$name] ?? [];
        if (!is_array($module)) {
            $module = [];
        }

        $module['enabled'] = $enabled;
        $module['roles'] = $module['roles'] ?? [];
        $module['dependencies'] = $module['dependencies'] ?? [];

        $modules[$name] = $module;
        $parameters['app.modules'] = $modules;
        $data['parameters'] = $parameters;

        $yaml = Yaml::dump($data, 4, 4);
        file_put_contents($this->configPath, $yaml);
    }
}
