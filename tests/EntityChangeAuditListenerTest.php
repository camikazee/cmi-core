<?php

declare(strict_types=1);

namespace Core\Tests;

use Core\Audit\Attribute\Auditable;
use Core\Audit\Doctrine\EntityChangeAuditListener;
use Core\Audit\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;

final class EntityChangeAuditListenerTest extends TestCase
{
    public function testRecordsDiffForAuditableEntitiesWithRedactionAfterFlush(): void
    {
        $persister = new InMemoryAuditPersister();
        $listener = $this->listener($persister);
        $lesson = new AuditedLesson('lesson-1');
        $related = new AuditedLesson('lesson-2');

        $this->flush($listener, updates: [$lesson], changeSets: [
            spl_object_id($lesson) => [
                'status' => [AuditedStatus::Booked, AuditedStatus::Cancelled],
                'startsAt' => [new \DateTimeImmutable('2026-09-25T10:00:00+00:00'), new \DateTimeImmutable('2026-09-26T10:00:00+00:00')],
                'notePrivate' => ['old secret note', 'new secret note'],
                'passwordHash' => ['a', 'b'],
                'updatedAt' => ['x', 'y'],
                'previous' => [null, $related],
            ],
        ]);

        self::assertCount(1, $persister->records);
        $record = $persister->records[0];
        self::assertSame('lesson.updated', $record->getActionKey());
        self::assertSame('lesson', $record->getTargetEntity());
        self::assertSame('lesson-1', $record->getTargetId());
        self::assertSame([
            'status' => ['booked', 'cancelled'],
            'startsAt' => ['2026-09-25T10:00:00+00:00', '2026-09-26T10:00:00+00:00'],
            'notePrivate' => ['[redacted]', '[redacted]'],
            'passwordHash' => ['[redacted]', '[redacted]'],
            'previous' => [null, 'lesson-2'],
        ], $record->getPayload()['changes']);
        self::assertStringNotContainsString('secret note', json_encode($record->getPayload(), JSON_THROW_ON_ERROR));
    }

    public function testIgnoresEntitiesWithoutTheAttributeAndEmptyUpdates(): void
    {
        $persister = new InMemoryAuditPersister();
        $listener = $this->listener($persister);
        $plain = new \ArrayObject();
        $lesson = new AuditedLesson('lesson-1');

        $this->flush($listener, updates: [$plain, $lesson], changeSets: [
            spl_object_id($plain) => ['anything' => [1, 2]],
            spl_object_id($lesson) => ['updatedAt' => ['x', 'y']],
        ]);

        self::assertSame([], $persister->records);
    }

    public function testRecordsCreationAndDeletion(): void
    {
        $persister = new InMemoryAuditPersister();
        $listener = $this->listener($persister);
        $created = new AuditedLesson('lesson-new');
        $deleted = new AuditedLesson('lesson-old');

        $this->flush($listener, insertions: [$created], deletions: [$deleted], changeSets: [
            spl_object_id($created) => ['status' => [null, AuditedStatus::Booked]],
        ]);

        self::assertSame(['lesson.created', 'lesson.deleted'], array_map(static fn ($r) => $r->getActionKey(), $persister->records));
        self::assertSame([], $persister->records[1]->getPayload()['changes']);
    }

    public function testFailingPersisterDoesNotBreakTheFlush(): void
    {
        $logger = new RecordingLogger();
        $listener = new EntityChangeAuditListener(
            new AuditLogger(new FailingAuditPersister(), new InMemoryAuditPublisher(), new FixedActorProvider(null)),
            $logger,
        );
        $lesson = new AuditedLesson('lesson-1');

        $this->flush($listener, insertions: [$lesson], changeSets: [spl_object_id($lesson) => ['status' => [null, AuditedStatus::Booked]]]);

        self::assertSame('Entity change audit could not be persisted', $logger->records[0]['message']);
    }

    private function listener(InMemoryAuditPersister $persister): EntityChangeAuditListener
    {
        return new EntityChangeAuditListener(
            new AuditLogger($persister, new InMemoryAuditPublisher(), new FixedActorProvider('user-1')),
            new RecordingLogger(),
        );
    }

    /**
     * @param list<object> $insertions
     * @param list<object> $updates
     * @param list<object> $deletions
     * @param array<int, array<string, array{0: mixed, 1: mixed}>> $changeSets
     */
    private function flush(EntityChangeAuditListener $listener, array $insertions = [], array $updates = [], array $deletions = [], array $changeSets = []): void
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->method('getScheduledEntityInsertions')->willReturn($insertions);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn($updates);
        $unitOfWork->method('getScheduledEntityDeletions')->willReturn($deletions);
        $unitOfWork->method('getEntityChangeSet')->willReturnCallback(static fn (object $entity): array => $changeSets[spl_object_id($entity)] ?? []);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getUnitOfWork')->willReturn($unitOfWork);

        $listener->onFlush(new OnFlushEventArgs($entityManager));
        $listener->postFlush(new PostFlushEventArgs($entityManager));
    }
}

enum AuditedStatus: string
{
    case Booked = 'booked';
    case Cancelled = 'cancelled';
}

#[Auditable(resourceType: 'lesson', redactedFields: ['notePrivate'], ignoredFields: ['updatedAt'])]
final class AuditedLesson
{
    public function __construct(private readonly string $id) {}
    public function getId(): string { return $this->id; }
}

final class FailingAuditPersister implements \Core\Audit\Contract\AuditPersisterInterface
{
    public function persist(\Core\Audit\Model\AuditLogRecord $record): void
    {
        throw new \RuntimeException('database down');
    }
}
