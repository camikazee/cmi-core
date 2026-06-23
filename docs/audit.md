# Audit

Audit is built around three host-provided contracts:

- `AuditActorProviderInterface` resolves the current actor ID.
- `AuditPersisterInterface` stores the record.
- `AuditEventPublisherInterface` optionally publishes the record to another system.

```php
$auditLogger->log(
    actionKey: 'inventory.asset.created',
    targetEntity: 'asset',
    targetId: $assetId,
    payload: ['code' => 'AST-0001'],
    level: 'success',
);
```

`AuditLogRecord` is an immutable value object. The package does not force a database schema for audit logs.
