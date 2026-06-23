# Setup

## Install

```bash
composer require cmi/core
```

For local path development:

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

## Bundle

```php
// config/bundles.php
return [
    Core\CoreBundle::class => ['all' => true],
];
```

## Doctrine

Map Core entities only if you use database-backed scheduler configuration:

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

For a path repository use the matching local package path, for example `../cmi-core/src/Entity`.

## Service Bindings

The bundle auto-registers Core services and tags scheduler jobs / dictionary seeders. Your application still has to bind storage-specific adapters:

```yaml
services:
  Core\Audit\Contract\AuditActorProviderInterface: '@App\Audit\SecurityActorProvider'
  Core\Audit\Contract\AuditPersisterInterface: '@App\Audit\DoctrineAuditPersister'
  Core\Audit\Contract\AuditEventPublisherInterface: '@App\Audit\NullAuditPublisher'
  Core\Dictionary\DictionaryPersisterInterface: '@App\Dictionary\DoctrineDictionaryPersister'
```

## Public Package Rule

Do not put host-application classes inside this package. If a feature needs `App\*`, it belongs in the host app or a bridge package.
