# Scheduler

The scheduler is intentionally simple: cron calls one Symfony command, Core decides which registered jobs should run.

```bash
* * * * * php /app/bin/console core:scheduler:tick
```

A job implements `ScheduledJobInterface`:

```php
final class CleanupJob implements ScheduledJobInterface
{
    public function getName(): string { return 'cleanup'; }
    public function getEveryMinutes(): int { return 60; }
    public function run(\DateTimeImmutable $now): array { return ['deleted' => 10]; }
}
```

Jobs are auto-tagged as `core.scheduler.job`. `SchedulerRunner` supports optional Symfony locks and optional audit logging.
