# Migrating Consumers to `cmi/core`

`cmi/core` is intended to be consumed as a standalone Composer package with semver tags.

## Local Path Repository

For local development before a public package release:

```json
{
  "repositories": [
    { "type": "path", "url": "../cmi-core", "options": { "symlink": true } }
  ],
  "require": { "cmi/core": "dev-main" }
}
```

Adjust `../cmi-core` to match your local checkout. Do not commit machine-specific absolute paths.

## Public VCS or Packagist

For CI and public consumers, use Packagist or a public VCS repository:

```json
{
  "repositories": [
    { "type": "vcs", "url": "https://github.com/camikazee/cmi-core.git" }
  ],
  "require": { "cmi/core": "^1.0" }
}
```

Package versions should come from Git tags such as `v1.0.0`; do not hard-code a `version` field in `composer.json`.

## Consumer Checklist

- Replace private path repositories with a public VCS/Packagist source.
- Run `composer update cmi/core`.
- Verify `composer.lock` does not contain local paths.
- Run the consumer application's test suite.
