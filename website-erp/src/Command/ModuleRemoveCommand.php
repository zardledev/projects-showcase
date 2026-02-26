<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'erp:module:remove',
    description: 'Remove an ERP module and its configuration'
)]
class ModuleRemoveCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Module name (e.g. Stock)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rawName = (string) $input->getArgument('name');
        $className = $this->normalizeClassName($rawName);
        if ($className === '') {
            $output->writeln('<error>Invalid module name.</error>');
            return Command::FAILURE;
        }

        $slug = $this->slugify($className);
        $filesystem = new Filesystem();
        $moduleDir = sprintf('src/Module/%s', $className);
        $templatePath = sprintf('templates/module/%s.html.twig', $slug);

        $removed = false;
        if ($filesystem->exists($moduleDir)) {
            $filesystem->remove($moduleDir);
            $removed = true;
        }
        if ($filesystem->exists($templatePath)) {
            $filesystem->remove($templatePath);
            $removed = true;
        }

        $this->removeModuleConfig($className);
        $this->refreshCache($output);

        if ($removed) {
            $output->writeln('<info>Module removed:</info> ' . $className);
        } else {
            $output->writeln('<comment>No module files found for:</comment> ' . $className);
        }

        return Command::SUCCESS;
    }

    private function normalizeClassName(string $name): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9]+/', ' ', $name);
        $clean = trim((string) $clean);
        if ($clean === '') {
            return '';
        }
        $parts = preg_split('/\s+/', $clean);
        $parts = array_map(fn (string $part) => ucfirst(strtolower($part)), $parts);
        return implode('', $parts);
    }

    private function slugify(string $name): string
    {
        $slug = preg_replace('/([a-z])([A-Z])/', '$1-$2', $name);
        $slug = strtolower((string) $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', (string) $slug);
        return trim((string) $slug, '-');
    }

    private function removeModuleConfig(string $className): void
    {
        $path = 'config/packages/app_modules.yaml';
        if (!is_file($path)) {
            return;
        }

        $data = Yaml::parseFile($path);
        if (!is_array($data)) {
            return;
        }

        $parameters = $data['parameters'] ?? [];
        if (!is_array($parameters)) {
            $parameters = [];
        }

        $modules = $parameters['app.modules'] ?? [];
        if (is_array($modules) && array_key_exists($className, $modules)) {
            unset($modules[$className]);
            $parameters['app.modules'] = $modules;
        }

        foreach (['app.modules.enabled', 'app.modules.disabled'] as $key) {
            $list = $parameters[$key] ?? [];
            if (!is_array($list)) {
                continue;
            }
            $filtered = array_values(array_diff($list, [$className]));
            $parameters[$key] = $filtered;
        }

        $data['parameters'] = $parameters;
        $yaml = Yaml::dump($data, 4, 4);
        file_put_contents($path, $yaml);
    }

    private function refreshCache(OutputInterface $output): void
    {
        $projectDir = dirname(__DIR__, 2);
        $console = $projectDir . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console';
        if (!is_file($console)) {
            $output->writeln('<comment>cache:clear skipped (bin/console not found).</comment>');
            return;
        }

        $clear = new Process([PHP_BINARY, $console, 'cache:clear', '--no-warmup', '--no-interaction']);
        $clear->setTimeout(300);
        $clear->run();
        if (!$clear->isSuccessful()) {
            $output->writeln('<comment>cache:clear failed; module may persist until cache is cleared.</comment>');
            return;
        }

        $warmup = new Process([PHP_BINARY, $console, 'cache:warmup', '--no-interaction']);
        $warmup->setTimeout(300);
        $warmup->run();
        if (!$warmup->isSuccessful()) {
            $output->writeln('<comment>cache:warmup failed; module may persist until cache is warmed.</comment>');
        }
    }
}
