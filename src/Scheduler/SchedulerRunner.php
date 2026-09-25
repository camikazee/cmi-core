<?php
declare(strict_types=1);

namespace Core\Scheduler;

use Core\Audit\Service\AuditLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;

final class SchedulerRunner
{
    /**
     * @param iterable<SchedulerRunListenerInterface> $runListeners
     */
    public function __construct(
        private readonly SchedulerRegistry $registry,
        private readonly SchedulerJobConfigService $configService,
        private readonly LoggerInterface $logger,
        private readonly ?AuditLogger $auditLogger = null,
        private readonly ?LockFactory $lockFactory = null,
        private readonly iterable $runListeners = [],
        private readonly bool $auditRuns = true,
    ) {
    }

    /**
     * @param string[] $onlyJobs
     * @return array<int,array{name:string,status:string,durationMs:int,result:array<string,mixed>}>
     */
    public function run(\DateTimeImmutable $now, array $onlyJobs = []): array
    {
        $results = [];
        $selected = array_map(static fn (string $name): string => trim($name), $onlyJobs);
        $selected = array_values(array_filter($selected, static fn (string $name): bool => $name !== ''));

        foreach ($this->registry->all() as $job) {
            $name = $job->getName();
            if ($selected !== [] && !in_array($name, $selected, true)) {
                continue;
            }

            $resolved = $this->configService->resolve($name, $job->getEveryMinutes());
            if (!($resolved['enabled'] ?? true)) {
                $results[] = [
                    'name' => $name,
                    'status' => 'skipped_disabled',
                    'durationMs' => 0,
                    'result' => [],
                ];
                $this->logRun($name, 'skipped_disabled', 0, [], $now, $selected !== []);
                continue;
            }

            $every = max(1, (int) ($resolved['everyMinutes'] ?? $job->getEveryMinutes()));
            $minuteOfDay = ((int) $now->format('G') * 60) + (int) $now->format('i');
            if ($selected === [] && ($minuteOfDay % $every) !== 0) {
                continue;
            }

            $started = microtime(true);
            $status = 'ok';
            $result = [];
            $errorClass = null;

            $lock = null;
            if ($this->lockFactory !== null) {
                $lock = $this->lockFactory->createLock('scheduler.job.' . $name, 300.0);
                if (!$lock->acquire()) {
                    $results[] = [
                        'name' => $name,
                        'status' => 'skipped_locked',
                        'durationMs' => 0,
                        'result' => [],
                    ];
                    $this->logRun($name, 'skipped_locked', 0, [], $now, $selected !== []);
                    continue;
                }
            }

            try {
                $result = $job->run($now);
            } catch (\Throwable $e) {
                $status = 'failed';
                $errorClass = $e::class;
                $result = ['error' => $e->getMessage()];
                $this->logger->error('Scheduler job failed', [
                    'job' => $name,
                    'exception' => $e::class,
                ]);
            } finally {
                $lock?->release();
            }

            $duration = (int) round((microtime(true) - $started) * 1000);
            $results[] = [
                'name' => $name,
                'status' => $status,
                'durationMs' => $duration,
                'result' => $result,
            ];
            $this->notifyListeners(new SchedulerJobRun($name, $now, $status, $duration, $errorClass));
            $this->logRun($name, $status, $duration, $result, $now, $selected !== []);
        }

        return $results;
    }

    private function notifyListeners(SchedulerJobRun $run): void
    {
        foreach ($this->runListeners as $listener) {
            try {
                $listener->onJobRun($run);
            } catch (\Throwable $e) {
                $this->logger->error('Scheduler run listener failed', [
                    'job' => $run->jobName,
                    'status' => $run->status,
                    'listener' => $listener::class,
                    'exception' => $e::class,
                ]);
            }
        }
    }

    /**
     * @param array<string,mixed> $result
     */
    private function logRun(string $name, string $status, int $durationMs, array $result, \DateTimeImmutable $now, bool $manual): void
    {
        if ($this->auditLogger === null || !$this->auditRuns) {
            return;
        }

        $emailsSent = is_numeric($result['emailsSent'] ?? null)
            ? (int) $result['emailsSent']
            : 0;
        if ($emailsSent === 0) {
            foreach (['instantSent', 'weeklySent', 'sent'] as $key) {
                $value = $result[$key] ?? null;
                if (is_numeric($value)) {
                    $emailsSent += (int) $value;
                }
            }
        }

        try {
            $this->auditLogger->log(
                actionKey: 'scheduler.job.executed',
                targetEntity: 'scheduler_job',
                targetId: $name,
                payload: [
                    'job' => $name,
                    'status' => $status,
                    'durationMs' => $durationMs,
                    'manual' => $manual,
                    'scheduledAt' => $now->format(\DateTimeInterface::ATOM),
                    'emailsSent' => $emailsSent,
                    'result' => $result,
                ],
                level: $this->mapLevel($status),
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to persist scheduler audit log', [
                'job' => $name,
                'status' => $status,
                'exception' => $e::class,
            ]);
        }
    }

    private function mapLevel(string $status): string
    {
        return match ($status) {
            'ok' => 'success',
            'failed' => 'error',
            'skipped_locked' => 'warning',
            default => 'info',
        };
    }
}
