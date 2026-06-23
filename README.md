# CMI Symfony Core

Reusable Symfony utilities for projects that need a clean application foundation without copying the same audit, scheduler, dictionary and notification-template code into every codebase.

This package is intentionally small. It is not a user system and not an admin panel. It gives you composable building blocks that a Symfony application can wire to its own entities, persistence and security model.

## What Is Included

- **Audit** - `AuditLogger` plus persister, actor and publisher contracts.
- **Scheduler** - lightweight job registry and runner for cron-driven background work.
- **Dictionary** - seed reusable option dictionaries from code.
- **Notification templates** - load YAML templates from project overrides or package defaults and render them with Twig.
- **Metadata traits** - small Doctrine/API Platform traits for created, updated and deleted timestamps.
- **Auth utilities** - token issuing contract, role policy and verification-code generator. Full registration/login flows belong in the host app.
- **Payment ports and adapters** - experimental contracts plus Manual, Przelewy24, PayU and PayPal providers. Treat these APIs as unstable until a tagged stable release documents them explicitly.

## Requirements

- PHP 8.2+
- Symfony 7.4 components
- Doctrine ORM 3.x if you use the Doctrine entities/metadata helpers
- API Platform 4.x if you use API metadata traits

## Installation

```bash
composer require cmi/core
```

For local development from a path repository:

```json
{
  "repositories": [
    {"type": "path", "url": "../cmi-core", "options": {"symlink": true}}
  ],
  "require": {
    "cmi/core": "dev-main"
  }
}
```

Register the bundle when Symfony Flex does not do it for you:

```php
// config/bundles.php
return [
    Core\CoreBundle::class => ['all' => true],
];
```

## Doctrine Mapping

The core package ships only a small scheduler configuration entity. Map it explicitly if you use database-configurable scheduler jobs:

```yaml
doctrine:
  orm:
    mappings:
      Core:
        is_bundle: false
        type: attribute
        dir: '%kernel.project_dir%/vendor/cmi/core/src/Entity'
        prefix: 'Core\\Entity'
        alias: Core
```

If you install through a path repository, point `dir` to the matching local package path, for example `../cmi-core/src/Entity`.

## Audit

Core audit is adapter-first. Your application decides where logs are stored and how the current actor is resolved.

```php
use Core\Audit\Contract\AuditActorProviderInterface;
use Core\Audit\Contract\AuditEventPublisherInterface;
use Core\Audit\Contract\AuditPersisterInterface;
use Core\Audit\Model\AuditLogRecord;

final class NullAuditPublisher implements AuditEventPublisherInterface
{
    public function publish(AuditLogRecord $record): void
    {
    }
}
```

```yaml
services:
  Core\Audit\Contract\AuditActorProviderInterface: '@App\Audit\SecurityActorProvider'
  Core\Audit\Contract\AuditPersisterInterface: '@App\Audit\DoctrineAuditPersister'
  Core\Audit\Contract\AuditEventPublisherInterface: '@App\Audit\NullAuditPublisher'
```

```php
$auditLogger->log(
    actionKey: 'asset.created',
    targetEntity: 'asset',
    targetId: $assetId,
    payload: ['code' => 'AST-0001'],
    level: 'success',
);
```

## Scheduler

Jobs implement `ScheduledJobInterface` and are auto-tagged as `core.scheduler.job` by the bundle service config.

```php
use Core\Scheduler\ScheduledJobInterface;

final class CleanupExpiredInvitesJob implements ScheduledJobInterface
{
    public function getName(): string
    {
        return 'cleanup_expired_invites';
    }

    public function getEveryMinutes(): int
    {
        return 60;
    }

    public function run(\DateTimeImmutable $now): array
    {
        return ['deleted' => 12];
    }
}
```

Run from cron every minute:

```bash
* * * * * php /app/bin/console core:scheduler:tick
```

Or force a specific job:

```bash
php bin/console core:scheduler:tick --job=cleanup_expired_invites
```

## Dictionary Seeders

```php
use Core\Dictionary\DictionarySeederInterface;

final class AssetStatusSeeder implements DictionarySeederInterface
{
    public function getGroup(): string
    {
        return 'asset_status';
    }

    public function getEntries(): array
    {
        return [
            ['value' => 'available', 'labelPl' => 'Dostepny', 'labelEn' => 'Available'],
            ['value' => 'missing', 'labelPl' => 'Brak', 'labelEn' => 'Missing'],
        ];
    }
}
```

Your app provides `DictionaryPersisterInterface`, so the same runner works with Doctrine, Redis, files or any other storage.

## Notification Templates

Templates are resolved in this order:

1. project override directory,
2. package defaults,
3. `default.yaml` locale fallback.

Example path:

```text
templates/email/auth/welcome/pl.yaml
```

Example YAML:

```yaml
subject: 'Welcome {{ userName }}'
bodyText: 'Hello {{ userName }}'
bodyHtml: '<p>Hello {{ userName }}</p>'
```

## Design Rules

- `src/` must not import `App\\*` classes.
- Host applications own users, auth controllers and persistence adapters.
- Core services expose contracts and small models, not full product workflows.
- Provider-specific integrations should stay isolated behind contracts and may be moved to bridge packages before a stable release.

## Versioning

Use semantic versioning. Until `1.0.0`, treat public APIs as experimental.

## License

MIT
