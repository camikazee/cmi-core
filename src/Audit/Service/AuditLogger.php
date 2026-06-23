<?php

declare(strict_types=1);

namespace Core\Audit\Service;

use Core\Audit\Contract\AuditActorProviderInterface;
use Core\Audit\Contract\AuditEventPublisherInterface;
use Core\Audit\Contract\AuditPersisterInterface;
use Core\Audit\Model\AuditLogRecord;

final class AuditLogger
{
    public function __construct(
        private readonly AuditPersisterInterface $persister,
        private readonly AuditEventPublisherInterface $eventPublisher,
        private readonly AuditActorProviderInterface $actorProvider,
    ) {
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function log(
        string $actionKey,
        ?string $targetEntity = null,
        ?string $targetId = null,
        array $payload = [],
        int $cost = 0,
        ?string $actorId = null,
        string $level = 'info'
    ): void {
        $record = new AuditLogRecord(
            actionKey: $actionKey,
            actorId: $actorId ?? $this->actorProvider->getCurrentActorId(),
            targetEntity: $targetEntity,
            targetId: $targetId,
            payload: $payload,
            cost: $cost,
            level: $level,
        );

        $this->persister->persist($record);
        $this->eventPublisher->publish($record);
    }
}

