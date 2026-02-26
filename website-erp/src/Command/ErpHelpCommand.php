<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'erp:help',
    description: 'Show ERP command reference'
)]
class ErpHelpCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            '<info>ERP Commands</info>',
            'erp:help',
            '  Show this help.',
            'erp:module:add <name> [version]',
            '  Create a new ERP module skeleton.',
            '  Example: erp:module:add Stock 0.1.0',
            'erp:seed:admin [email] [password]',
            '  Create or update the admin account.',
            '  Defaults: admin@admin.fr / admin',
            '  Example: erp:seed:admin admin@example.com secret',
            'erp:user:add <email> <password> [--role=ROLE_X]',
            '  Create a user account.',
            '  Example: erp:user:add user@example.com secret --role=ROLE_USER --role=ROLE_MANAGER',
        ]);

        return Command::SUCCESS;
    }
}
