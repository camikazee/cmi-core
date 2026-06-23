<?php

declare(strict_types=1);

namespace Core\Command;

use Core\Dictionary\DictionarySeederRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'core:dictionary:seed',
    description: 'Seed dictionary options. Upserts entries by default; use --replace to wipe and re-seed a group.',
)]
final class SeedDictionariesCommand extends Command
{
    public function __construct(private readonly DictionarySeederRunner $runner)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('group', 'g', InputOption::VALUE_REQUIRED, 'Seed only this group (omit = all groups)')
            ->addOption('replace', null, InputOption::VALUE_NONE, 'Delete existing entries before seeding')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'List registered groups and exit');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('list')) {
            $groups = $this->runner->getRegisteredGroups();
            $io->title('Registered dictionary groups');
            $io->listing($groups);
            return Command::SUCCESS;
        }

        $group = $input->getOption('group');
        $replace = (bool) $input->getOption('replace');

        if ($replace && $group === null) {
            $io->error('--replace requires --group to avoid accidentally wiping all dictionaries.');
            return Command::FAILURE;
        }

        $results = $this->runner->run($group, $replace);

        if (empty($results)) {
            $io->warning($group ? "No seeder found for group: {$group}" : 'No seeders registered.');
            return Command::SUCCESS;
        }

        $io->title('Dictionary seeding complete');
        $rows = [];
        foreach ($results as $g => $count) {
            $rows[] = [$g, $count];
        }
        $io->table(['Group', 'Entries upserted'], $rows);

        return Command::SUCCESS;
    }
}
