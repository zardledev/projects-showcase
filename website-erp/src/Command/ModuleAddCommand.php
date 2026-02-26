<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'erp:module:add',
    description: 'Create a new ERP module skeleton'
)]
class ModuleAddCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Module name (e.g. Stock)')
            ->addArgument('version', InputArgument::OPTIONAL, 'Module version', '0.1.0')
            ->addOption('role', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Roles allowed to access this module', [])
            ->addOption('dependency', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Module dependencies', []);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rawName = (string) $input->getArgument('name');
        $version = (string) $input->getArgument('version');
        $roles = array_values(array_unique(array_filter((array) $input->getOption('role'))));
        $dependencies = array_values(array_unique(array_filter((array) $input->getOption('dependency'))));

        $className = $this->normalizeClassName($rawName);
        if ($className === '') {
            $output->writeln('<error>Invalid module name.</error>');
            return Command::FAILURE;
        }

        $slug = $this->slugify($className);
        $moduleDir = sprintf('src/Module/%s', $className);
        $filesystem = new Filesystem();

        if ($filesystem->exists($moduleDir)) {
            $output->writeln('<error>Module already exists.</error>');
            return Command::FAILURE;
        }

        $filesystem->mkdir([
            $moduleDir,
            $moduleDir . '/Controller',
            $moduleDir . '/Service',
            $moduleDir . '/EventSubscriber',
        ]);

        $moduleClass = $this->moduleClass($className, $version);
        $controllerClass = $this->controllerClass($className, $slug);
        $template = $this->template($className);

        $filesystem->dumpFile($moduleDir . '/' . $className . 'Module.php', $moduleClass);
        $filesystem->dumpFile($moduleDir . '/Controller/' . $className . 'Controller.php', $controllerClass);
        $filesystem->dumpFile('templates/module/' . $slug . '.html.twig', $template);
        $this->updateModuleConfig($className, $roles, $dependencies);
        $this->refreshCache($output);

        $output->writeln('<info>Module created:</info> ' . $className);
        $output->writeln('Route: /modules/' . $slug);
        $output->writeln('Template: templates/module/' . $slug . '.html.twig');

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

    private function moduleClass(string $className, string $version): string
    {
        return <<<PHP
<?php

namespace App\\Module\\$className;

use App\\Core\\CoreContext;
use App\\Core\\Module\\ModuleInterface;

class {$className}Module implements ModuleInterface
{
    public function getName(): string
    {
        return '$className';
    }

    public function getVersion(): string
    {
        return '$version';
    }

    public function getDescription(): string
    {
        return 'Module $className';
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function isEnabledByDefault(): bool
    {
        return true;
    }

    public function boot(CoreContext \$context): void
    {
        \$context->eventBus;
        \$context->configuration;
        \$context->permissions;
        \$context->auth;
    }
}
PHP;
    }

    private function controllerClass(string $className, string $slug): string
    {
        return <<<PHP
<?php

namespace App\\Module\\$className\\Controller;

use App\\Core\\CoreAPI;
use Symfony\\Bundle\\FrameworkBundle\\Controller\\AbstractController;
use Symfony\\Component\\HttpFoundation\\Response;
use Symfony\\Component\\Routing\\Attribute\\Route;

class {$className}Controller extends AbstractController
{
    #[Route('/modules/$slug', name: 'module_$slug')]
    public function index(CoreAPI \$core): Response
    {
        \$core->bootModules();
        \$module = null;

        foreach (\$core->modules() as \$item) {
            if (\$item->name === '$className') {
                \$module = \$item;
                break;
            }
        }

        return \$this->render('module/$slug.html.twig', [
            'module' => \$module,
        ]);
    }
}
PHP;
    }

    private function template(string $className): string
    {
        return <<<TWIG
{% extends 'base.html.twig' %}

{% block title %}Module $className{% endblock %}

{% block body %}
<main class="page module-page">
    <section class="module-hero">
        <p class="module-hero__kicker">Module</p>
        <h1>$className</h1>
        <p class="module-hero__subtitle">Description du module $className.</p>
    </section>

    <section class="module-card">
        <h2>Etat du module</h2>
        {% if module %}
            <div class="module-chip">
                <span>{{ module.name }}</span>
                <span>v{{ module.version }}</span>
                <span>{{ module.status }}</span>
            </div>
        {% else %}
            <p>Module non charge.</p>
        {% endif %}
    </section>
</main>
{% endblock %}
TWIG;
    }

    private function updateModuleConfig(string $className, array $roles, array $dependencies): void
    {
        $path = 'config/packages/app_modules.yaml';
        if (!file_exists($path)) {
            return;
        }

        $contents = (string) file_get_contents($path);
        if (str_contains($contents, "\n        {$className}:\n")) {
            return;
        }

        $rolesYaml = $this->yamlInlineList($roles);
        $dependenciesYaml = $this->yamlInlineList($dependencies);
        $block = "        {$className}:\n            enabled: true\n            roles: {$rolesYaml}\n            dependencies: {$dependenciesYaml}\n";
        if (preg_match('/app\.modules:\s*\{\s*\}/', $contents) === 1) {
            $contents = preg_replace('/app\.modules:\s*\{\s*\}/', "app.modules:\n{$block}", $contents, 1);
        } elseif (str_contains($contents, "app.modules:\n")) {
            $contents = preg_replace('/app\.modules:\n/', "app.modules:\n{$block}", $contents, 1);
        } elseif (str_contains($contents, "parameters:\n")) {
            $contents = preg_replace('/parameters:\n/', "parameters:\n    app.modules:\n{$block}", $contents, 1);
        } else {
            $contents .= "\nparameters:\n    app.modules:\n{$block}";
        }

        file_put_contents($path, $contents);
    }

    private function yamlInlineList(array $items): string
    {
        if (count($items) === 0) {
            return '[]';
        }

        $escaped = array_map(
            fn (string $item) => "'" . str_replace("'", "''", $item) . "'",
            array_map('strval', $items),
        );

        return '[' . implode(', ', $escaped) . ']';
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
            $output->writeln('<comment>cache:clear failed; module may not be visible until cache is cleared.</comment>');
            return;
        }

        $warmup = new Process([PHP_BINARY, $console, 'cache:warmup', '--no-interaction']);
        $warmup->setTimeout(300);
        $warmup->run();
        if (!$warmup->isSuccessful()) {
            $output->writeln('<comment>cache:warmup failed; module may not be visible until cache is warmed.</comment>');
        }
    }
}
