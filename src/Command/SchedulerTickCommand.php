<?php
declare(strict_types=1);

namespace Core\Command;

use Core\Scheduler\SchedulerRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'core:scheduler:tick', description: 'Runs all scheduled jobs that are due at current minute.')]
final class SchedulerTickCommand extends Command
{
    public function __construct(private readonly SchedulerRunner $runner)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('job', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Run only selected job name(s).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var array<int,string> $jobNames */
        $jobNames = (array) $input->getOption('job');
        $results = $this->runner->run(new \DateTimeImmutable(), $jobNames);

        if ($results === []) {
            $io->writeln('No jobs due.');
            return Command::SUCCESS;
        }

        foreach ($results as $row) {
            $io->writeln(sprintf(
                '[%s] %s (%dms) %s',
                strtoupper($row['status']),
                $row['name'],
                $row['durationMs'],
                $row['result'] === [] ? '' : json_encode($row['result'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ));
        }

        $failed = array_filter($results, static fn (array $row): bool => $row['status'] === 'failed');
        return $failed === [] ? Command::SUCCESS : Command::FAILURE;
    }
}
