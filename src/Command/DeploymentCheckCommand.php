<?php

declare(strict_types=1);

namespace Core\Command;

use Core\Deployment\DeploymentCheckRunner;
use Core\Deployment\DeploymentReportMailerInterface;
use Core\Observability\FailureDetails;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:deploy:check', description: 'Check deployment services, print report first, then send one report email unless --no-email.')]
final class DeploymentCheckCommand extends Command
{
    public function __construct(private readonly DeploymentCheckRunner $runner, private readonly DeploymentReportMailerInterface $mailer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('json', null, InputOption::VALUE_NONE, 'Emit JSON Lines: report first, then delivery outcome.')
            ->addOption('no-email', null, InputOption::VALUE_NONE, 'Read-only checks; do not send the report.')
            ->addOption('report-to', null, InputOption::VALUE_REQUIRED, 'Override the application report recipient.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $recipient = $input->getOption('report-to');
        if ($recipient !== null && (!is_string($recipient) || !filter_var($recipient, FILTER_VALIDATE_EMAIL))) {
            $output->writeln('Invalid --report-to address.');
            return Command::INVALID;
        }
        $report = $this->runner->run();
        // Flush before external mail I/O: even a timeout/killed process leaves the diagnostic report.
        if ($input->getOption('json')) {
            $output->writeln(json_encode(['type' => 'deployment_report'] + $report, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        } else {
            $output->writeln('Deployment report '.$report['run_id'].' '.$report['checked_at']);
            foreach ($report['checks'] as $name => $row) {
                $output->writeln(sprintf('[%s] %s: %s', strtoupper($row['status']), $name, $row['detail']));
                if (isset($row['data'])) $output->writeln(json_encode($row['data'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
            }
        }
        $delivery = ['type' => 'report_delivery', 'run_id' => $report['run_id'], 'status' => 'skipped',
            'detail' => 'Email disabled; outbound delivery was not verified.'];
        if (!$input->getOption('no-email')) {
            try {
                $this->mailer->send($report, $recipient);
                $delivery['status'] = 'pass';
                $delivery['detail'] = 'Report accepted by configured transport; inbox receipt is NOT confirmed.';
            } catch (\Throwable $error) {
                $delivery['status'] = 'fail';
                $delivery['detail'] = 'Report email failed; the complete console report above remains available.';
                $delivery['data'] = FailureDetails::from($error);
            }
        }
        if ($input->getOption('json')) {
            $output->writeln(json_encode($delivery, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        } else {
            $output->writeln(sprintf('[%s] report_delivery: %s', strtoupper($delivery['status']), $delivery['detail']));
            if (isset($delivery['data'])) $output->writeln(json_encode($delivery['data'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        }
        return $report['ok'] && $delivery['status'] !== 'fail' ? Command::SUCCESS : Command::FAILURE;
    }
}
