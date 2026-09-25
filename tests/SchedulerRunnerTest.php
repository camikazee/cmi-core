<?php

declare(strict_types=1);

namespace Core\Tests;

use Core\Scheduler\ScheduledJobInterface;
use Core\Scheduler\SchedulerJobConfigService;
use Core\Scheduler\SchedulerJobRun;
use Core\Scheduler\SchedulerRegistry;
use Core\Scheduler\SchedulerRunListenerInterface;
use Core\Scheduler\SchedulerRunner;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

final class SchedulerRunnerTest extends TestCase
{
    public function testListenersReceiveEveryExecutedJobWithoutExceptionMessages(): void
    {
        $listener = new RecordingRunListener();
        $runner = $this->runner([new FixedJob('ok.job'), new FixedJob('failing.job', new \RuntimeException('secret@example.test'))], [$listener]);

        $results = $runner->run(new \DateTimeImmutable('2026-09-25 12:00:00'), ['ok.job', 'failing.job']);

        self::assertSame(['failing.job' => 'failed', 'ok.job' => 'ok'], $this->statuses($results));
        self::assertCount(2, $listener->runs);
        $runs = [];
        foreach ($listener->runs as $run) {
            $runs[$run->jobName] = $run;
        }
        self::assertFalse($runs['ok.job']->failed());
        self::assertNull($runs['ok.job']->errorClass);
        self::assertTrue($runs['failing.job']->failed());
        self::assertSame(\RuntimeException::class, $runs['failing.job']->errorClass);
    }

    public function testFailingListenerIsLoggedAndDoesNotStopRemainingJobs(): void
    {
        $logger = new RecordingLogger();
        $recorder = new RecordingRunListener();
        $runner = $this->runner(
            [new FixedJob('first.job'), new FixedJob('second.job')],
            [new ThrowingRunListener(), $recorder],
            $logger,
        );

        $results = $runner->run(new \DateTimeImmutable('2026-09-25 12:00:00'), ['first.job', 'second.job']);

        self::assertSame(['first.job' => 'ok', 'second.job' => 'ok'], $this->statuses($results));
        self::assertCount(2, $recorder->runs);
        self::assertContains('Scheduler run listener failed', array_column($logger->records, 'message'));
        foreach ($logger->records as $record) {
            self::assertArrayNotHasKey('message', $record['context']);
        }
    }

    public function testFailedJobLogOmitsTheExceptionMessage(): void
    {
        $logger = new RecordingLogger();
        $runner = $this->runner([new FixedJob('failing.job', new \RuntimeException('secret@example.test'))], [], $logger);

        $runner->run(new \DateTimeImmutable('2026-09-25 12:00:00'), ['failing.job']);

        self::assertSame('Scheduler job failed', $logger->records[0]['message']);
        self::assertSame(\RuntimeException::class, $logger->records[0]['context']['exception']);
        self::assertStringNotContainsString('secret@example.test', json_encode($logger->records, JSON_THROW_ON_ERROR));
    }

    public function testRunAuditCanBeDisabled(): void
    {
        $persister = new InMemoryAuditPersister();
        $auditLogger = new \Core\Audit\Service\AuditLogger($persister, new InMemoryAuditPublisher(), new FixedActorProvider(null));
        $repository = $this->createMock(EntityRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);
        $configService = new SchedulerJobConfigService($entityManager);

        (new SchedulerRunner(new SchedulerRegistry([new FixedJob('quiet.job')]), $configService, new RecordingLogger(), $auditLogger, auditRuns: false))
            ->run(new \DateTimeImmutable('2026-09-25 12:00:00'), ['quiet.job']);
        self::assertSame([], $persister->records);

        (new SchedulerRunner(new SchedulerRegistry([new FixedJob('loud.job')]), $configService, new RecordingLogger(), $auditLogger))
            ->run(new \DateTimeImmutable('2026-09-25 12:00:00'), ['loud.job']);
        self::assertSame('scheduler.job.executed', $persister->records[0]->getActionKey());
    }

    /**
     * @param array<int, array{name: string, status: string}> $results
     *
     * @return array<string, string>
     */
    private function statuses(array $results): array
    {
        $statuses = array_column($results, 'status', 'name');
        ksort($statuses);

        return $statuses;
    }

    /**
     * @param list<ScheduledJobInterface> $jobs
     * @param list<SchedulerRunListenerInterface> $listeners
     */
    private function runner(array $jobs, array $listeners, ?RecordingLogger $logger = null): SchedulerRunner
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn(null);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);

        return new SchedulerRunner(
            new SchedulerRegistry($jobs),
            new SchedulerJobConfigService($entityManager),
            $logger ?? new RecordingLogger(),
            runListeners: $listeners,
        );
    }
}

final class FixedJob implements ScheduledJobInterface
{
    public function __construct(private readonly string $name, private readonly ?\Throwable $failure = null) {}
    public function getName(): string { return $this->name; }
    public function getEveryMinutes(): int { return 1; }
    public function run(\DateTimeImmutable $now): array
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }

        return ['done' => true];
    }
}

final class RecordingRunListener implements SchedulerRunListenerInterface
{
    /** @var list<SchedulerJobRun> */
    public array $runs = [];
    public function onJobRun(SchedulerJobRun $run): void { $this->runs[] = $run; }
}

final class ThrowingRunListener implements SchedulerRunListenerInterface
{
    public function onJobRun(SchedulerJobRun $run): void { throw new \LogicException('listener broke'); }
}

final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{message: string, context: array<string, mixed>}> */
    public array $records = [];

    /** @param array<string, mixed> $context */
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->records[] = ['message' => (string) $message, 'context' => $context];
    }
}
