# Notification Templates

Core supports file-based notification templates and Twig rendering. Delivery is intentionally outside the package.

Lookup order:

1. project override directory,
2. package default directory,
3. `default.yaml` fallback locale.

A template key like `auth.welcome` maps to:

```text
email/auth/welcome/pl.yaml
```

Example:

```yaml
subject: 'Welcome {{ userName }}'
bodyText: 'Hello {{ userName }}'
bodyHtml: '<p>Hello {{ userName }}</p>'
```
