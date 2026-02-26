<?php

namespace App\Command;

use App\Core\CoreAPI;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'erp:module:list',
    description: 'List ERP modules and their status'
)]
class ModuleListCommand extends Command
{
    public function __construct(private CoreAPI $core)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->core->bootModules();
        $modules = $this->core->modules();

        if (count($modules) === 0) {
            $output->writeln('<comment>No modules registered.</comment>');
            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(['Name', 'Version', 'Status', 'Enabled', 'Roles', 'Dependencies']);

        foreach ($modules as $module) {
            $roles = empty($module->roles) ? 'public' : implode(', ', $module->roles);
            $deps = empty($module->dependencies) ? 'none' : implode(', ', $module->dependencies);
            $table->addRow([
                $module->name,
                $module->version,
                $module->status,
                $module->enabled ? 'yes' : 'no',
                $roles,
                $deps,
            ]);
        }

        $table->render();
        return Command::SUCCESS;
    }
}
