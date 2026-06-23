# Contributing

Contributions are welcome once the package is published as a standalone repository.

## Local Setup

```bash
composer install
vendor/bin/phpunit
```

## Rules

- Keep `src/` free from `App\\*` imports.
- Prefer interfaces for host-application persistence and delivery adapters.
- Do not add provider-specific integrations to the core package unless they are optional and isolated.
- Keep Symfony service wiring minimal and overridable.
- Add tests for each public service or contract behavior.
