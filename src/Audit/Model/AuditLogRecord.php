<?php

declare(strict_types=1);

namespace Core\Audit\Model;

final class AuditLogRecord
{
    /**
     * @param array<string,mixed> $payload
     */
    public function __construct(
        private readonly string $actionKey,
        private readonly ?string $actorId,
        private readonly ?string $targetEntity,
        private readonly ?string $targetId,
        private readonly array $payload,
        private readonly int $cost,
        private readonly string $level,
        private readonly ?\DateTimeImmutable $occurredAt = null,
    ) {
    }

    public function getActionKey(): string
    {
        return $this->actionKey;
    }

    public function getActorId(): ?string
    {
        return $this->actorId;
    }

    public function getTargetEntity(): ?string
    {
        return $this->targetEntity;
    }

    public function getTargetId(): ?string
    {
        return $this->targetId;
    }

    /**
     * @return array<string,mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getCost(): int
    {
        return $this->cost;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt ?? new \DateTimeImmutable();
    }
}

