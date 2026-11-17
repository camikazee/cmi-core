<?php

declare(strict_types=1);

namespace Core\Deployment;

use Core\Observability\FailureDetails;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class DeploymentCheckRunner
{
    /** @param iterable<DeploymentCheckProviderInterface> $providers */
    public function __construct(#[AutowireIterator('core.deployment_check')] private readonly iterable $providers)
    {
    }

    /** @return array<string,mixed> */
    public function run(): array
    {
        $rows = [];
        foreach ($this->providers as $provider) {
            try {
                foreach ($provider->checks() as $name => $probe) {
                    $started = hrtime(true);
                    try {
                        if (isset($rows[$name])) throw new \LogicException('Duplicate deployment check name.');
                        $row = $probe();
                        if (!in_array($row['status'], ['pass', 'fail', 'warn', 'skipped'], true)) throw new \LogicException('Invalid deployment status.');
                    } catch (\Throwable $error) {
                        $row = ['status' => 'fail', 'detail' => 'Probe failed; inspect safe diagnostic fields.', 'data' => FailureDetails::from($error)];
                    }
                    $rows[$name] = $row + ['duration_ms' => (int) round((hrtime(true) - $started) / 1_000_000)];
                }
            } catch (\Throwable $error) {
                $rows['provider.'.count($rows)] = ['status' => 'fail', 'detail' => 'Provider unavailable.', 'data' => FailureDetails::from($error)];
            }
        }
        if ($rows === []) $rows['configuration'] = ['status' => 'fail', 'detail' => 'No deployment check providers registered.'];
        $failed = (bool) array_filter($rows, static fn (array $row): bool => $row['status'] === 'fail');
        $partial = (bool) array_filter($rows, static fn (array $row): bool => in_array($row['status'], ['warn', 'skipped'], true));
        return ['schema_version' => 1, 'run_id' => bin2hex(random_bytes(16)), 'checked_at' => gmdate(DATE_ATOM),
            'ok' => !$failed, 'status' => $failed ? 'fail' : ($partial ? 'warn' : 'pass'), 'checks' => $rows];
    }
}
