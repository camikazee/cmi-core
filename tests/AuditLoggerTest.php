<?php

declare(strict_types=1);

namespace Core\Tests;

use Core\Audit\Contract\AuditActorProviderInterface;
use Core\Audit\Contract\AuditEventPublisherInterface;
use Core\Audit\Contract\AuditPersisterInterface;
use Core\Audit\Model\AuditLogRecord;
use Core\Audit\Service\AuditLogger;
use PHPUnit\Framework\TestCase;

final class AuditLoggerTest extends TestCase
{
    public function testItPersistsAndPublishesAuditRecord(): void
    {
        $persister = new InMemoryAuditPersister();
        $publisher = new InMemoryAuditPublisher();
        $logger = new AuditLogger($persister, $publisher, new FixedActorProvider('user-1'));

        $logger->log('asset.created', 'asset', 'asset-1', ['code' => 'AST-1'], level: 'success');

        self::assertCount(1, $persister->records);
        self::assertCount(1, $publisher->records);
        self::assertSame('asset.created', $persister->records[0]->getActionKey());
        self::assertSame('user-1', $persister->records[0]->getActorId());
        self::assertSame(['code' => 'AST-1'], $persister->records[0]->getPayload());
    }
}

final class FixedActorProvider implements AuditActorProviderInterface
{
    public function __construct(private readonly ?string $actorId) {}
    public function getCurrentActorId(): ?string { return $this->actorId; }
}

final class InMemoryAuditPersister implements AuditPersisterInterface
{
    /** @var list<AuditLogRecord> */
    public array $records = [];
    public function persist(AuditLogRecord $record): void { $this->records[] = $record; }
}

final class InMemoryAuditPublisher implements AuditEventPublisherInterface
{
    /** @var list<AuditLogRecord> */
    public array $records = [];
    public function publish(AuditLogRecord $record): void { $this->records[] = $record; }
}
