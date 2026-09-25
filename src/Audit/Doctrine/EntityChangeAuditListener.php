<?php

declare(strict_types=1);

namespace Core\Audit\Doctrine;

use Core\Audit\Attribute\Auditable;
use Core\Audit\Service\AuditLogger;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

/**
 * Records create/update/delete of entities marked with #[Auditable] as "<resource>.<verb>" audit events
 * with a before/after diff of changed fields.
 *
 * Changes are collected on flush and written after a successful flush. Audit is best effort: a failing
 * persister is logged and never breaks the business write that already happened. Credentials-like fields
 * are always redacted, relations are stored as identifiers only.
 */
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
final class EntityChangeAuditListener
{
    public const REDACTED = '[redacted]';

    private const ALWAYS_REDACTED = [
        'password', 'passwordHash', 'plainPassword', 'secret', 'secretHash',
        'token', 'tokenHash', 'refreshToken', 'codeHash', 'apiKey',
    ];

    /** @var array<class-string, Auditable|false> */
    private array $metadata = [];

    /** @var list<array{action: string, resourceType: string, id: ?string, changes: array<string, array{0: mixed, 1: mixed}>}> */
    private array $pending = [];

    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $this->collect($entity, 'created', $unitOfWork->getEntityChangeSet($entity));
        }
        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            $this->collect($entity, 'updated', $unitOfWork->getEntityChangeSet($entity));
        }
        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            $this->collect($entity, 'deleted', []);
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->pending === []) {
            return;
        }

        $pending = $this->pending;
        $this->pending = [];
        foreach ($pending as $record) {
            try {
                $this->auditLogger->log(
                    actionKey: sprintf('%s.%s', $record['resourceType'], $record['action']),
                    targetEntity: $record['resourceType'],
                    targetId: $record['id'],
                    payload: ['changes' => $record['changes']],
                );
            } catch (\Throwable $exception) {
                $this->logger->error('Entity change audit could not be persisted', [
                    'resource' => $record['resourceType'],
                    'action' => $record['action'],
                    'exception' => $exception::class,
                ]);
            }
        }
    }

    /**
     * @param array<string, array{0: mixed, 1: mixed}> $changeSet
     */
    private function collect(object $entity, string $action, array $changeSet): void
    {
        $auditable = $this->auditable($entity);
        if ($auditable === null) {
            return;
        }

        $changes = [];
        foreach ($changeSet as $field => [$old, $new]) {
            if (in_array($field, $auditable->ignoredFields, true)) {
                continue;
            }
            if (in_array($field, self::ALWAYS_REDACTED, true) || in_array($field, $auditable->redactedFields, true)) {
                $changes[$field] = [self::REDACTED, self::REDACTED];
                continue;
            }
            $changes[$field] = [$this->normalize($old), $this->normalize($new)];
        }

        if ($action === 'updated' && $changes === []) {
            return;
        }

        $this->pending[] = [
            'action' => $action,
            'resourceType' => $auditable->resourceType,
            'id' => $this->identifier($entity),
            'changes' => $changes,
        ];
    }

    private function auditable(object $entity): ?Auditable
    {
        $class = $entity::class;
        if (!array_key_exists($class, $this->metadata)) {
            $attributes = (new \ReflectionClass($entity))->getAttributes(Auditable::class);
            $this->metadata[$class] = $attributes !== [] ? $attributes[0]->newInstance() : false;
        }

        return $this->metadata[$class] ?: null;
    }

    private function identifier(object $entity): ?string
    {
        if (!method_exists($entity, 'getId')) {
            return null;
        }
        $id = $entity->getId();

        return $id === null ? null : (is_scalar($id) || $id instanceof \Stringable ? (string) $id : null);
    }

    private function normalize(mixed $value): mixed
    {
        return match (true) {
            $value === null, is_scalar($value) => $value,
            $value instanceof \BackedEnum => $value->value,
            $value instanceof \UnitEnum => $value->name,
            $value instanceof \DateTimeInterface => $value->format(\DateTimeInterface::ATOM),
            $value instanceof \Stringable => (string) $value,
            is_array($value) => array_map($this->normalize(...), $value),
            is_object($value) => $this->identifier($value) ?? get_debug_type($value),
            default => get_debug_type($value),
        };
    }
}
