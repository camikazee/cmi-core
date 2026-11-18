<?php
declare(strict_types=1);

namespace Core\Scheduler;

use Core\Audit\Service\AuditLogger;
use Core\Observability\FailureDetails;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;

final class SchedulerRunner
{
    /** Correlates every log line, error report and audit entry of one tick. */
    private string $runId = '';

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
        $this->runId = bin2hex(random_bytes(16));
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
                try {
                    // Released explicitly below; without auto-release a store failure cannot resurface from a destructor.
                    $lock = $this->lockFactory->createLock('scheduler.job.' . $name, 300.0, false);
                    $acquired = $lock->acquire();
                } catch (\Throwable $e) {
                    // The lock store is down (Redis refusing, unreachable): this job does not run
                    // unguarded, but the tick goes on — the next jobs and the next tick try again.
                    $this->logFailure('Scheduler lock unavailable', $name, 'lock_acquire', $e);
                    $results[] = [
                        'name' => $name,
                        'status' => 'skipped_lock_unavailable',
                        'durationMs' => 0,
                        'result' => [],
                    ];
                    $this->logRun($name, 'skipped_lock_unavailable', 0, [], $now, $selected !== []);
                    continue;
                }
                if (!$acquired) {
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
                // The audit keeps a code, not the exception text: messages may carry addresses or SQL.
                $result = ['error' => 'scheduler_job_failed'];
                $this->logFailure('Scheduler job failed', $name, 'job_run', $e);
            } finally {
                if ($lock !== null) {
                    try {
                        $lock->release();
                    } catch (\Throwable $e) {
                        // The job's work is done; a lost release only lets the lock expire by TTL.
                        $this->logFailure('Scheduler lock release failed', $name, 'lock_release', $e);
                    }
                }
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
                $this->logFailure('Scheduler run listener failed', $run->jobName, 'run_listener', $e, ['status' => $run->status, 'listener' => $listener::class]);
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
                    'run_id' => $this->runId,
                ],
                level: $this->mapLevel($status),
            );
        } catch (\Throwable $e) {
            $this->logFailure('Failed to persist scheduler audit log', $name, 'audit_write', $e, ['status' => $status]);
        }
    }

    /**
     * One error line per failure: the job in the title (so error trackers group by job), run id and
     * phase, and the exception's safe structure — never its message, arguments or SQL.
     *
     * @param array<string, scalar> $extra
     */
    private function logFailure(string $title, string $job, string $phase, \Throwable $error, array $extra = []): void
    {
        $this->logger->error($title.' ['.$job.']', [
            'job' => $job,
            'phase' => $phase,
            'run_id' => $this->runId,
            'exception' => $error::class,
        ] + $extra + FailureDetails::from($error));
    }

    private function mapLevel(string $status): string
    {
        return match ($status) {
            'ok' => 'success',
            'failed' => 'error',
            'skipped_locked' => 'warning',
            'skipped_lock_unavailable' => 'error',
            default => 'info',
        };
    }
}
