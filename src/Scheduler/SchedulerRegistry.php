<?php
declare(strict_types=1);

namespace Core\Scheduler;

final class SchedulerRegistry
{
    /**
     * @param iterable<int,ScheduledJobInterface> $jobs
     */
    public function __construct(private readonly iterable $jobs)
    {
    }

    /**
     * @return array<string,ScheduledJobInterface>
     */
    public function all(): array
    {
        $items = [];
        foreach ($this->jobs as $job) {
            $items[$job->getName()] = $job;
        }
        ksort($items);
        return $items;
    }

    public function find(string $name): ?ScheduledJobInterface
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }
        $all = $this->all();
        return $all[$name] ?? null;
    }
}
