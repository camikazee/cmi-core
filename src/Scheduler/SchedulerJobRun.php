<?php

declare(strict_types=1);

namespace Core\Scheduler;

/**
 * Outcome of one executed scheduled job, handed to run listeners.
 *
 * It carries only the exception class, never the message, so listeners can persist it safely.
 */
final readonly class SchedulerJobRun
{
    public function __construct(
        public string $jobName,
        public \DateTimeImmutable $scheduledAt,
        public string $status,
        public int $durationMs,
        public ?string $errorClass = null,
    ) {
    }

    public function failed(): bool
    {
        return $this->status === 'failed';
    }
}
