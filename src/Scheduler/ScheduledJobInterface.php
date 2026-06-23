<?php
declare(strict_types=1);

namespace Core\Scheduler;

interface ScheduledJobInterface
{
    public function getName(): string;

    /**
     * Run frequency in minutes.
     */
    public function getEveryMinutes(): int;

    /**
     * @return array<string,mixed>
     */
    public function run(\DateTimeImmutable $now): array;
}
