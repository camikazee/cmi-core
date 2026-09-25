# Changelog

All notable changes to this package will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project uses semantic versioning.

## [Unreleased]

## [1.1.0] - 2026-09-25

### Added
- `SchedulerRunListenerInterface` and `SchedulerJobRun`: optional hook called after every executed job
  (auto-tagged `core.scheduler.run_listener`). Consumers without listeners are unaffected; a failing
  listener is logged and does not stop the remaining jobs.
- `app:scheduler:tick` alias of `core:scheduler:tick` for projects migrating from embedded copies.

### Changed
- Scheduler logs no longer include exception messages, only the exception class, so user data from
  failing jobs does not leak into logs.

### Fixed
- `DeletedFilter` imports the correctly cased `ClassMetadata` and compares with a boolean literal, so it
  works on PostgreSQL.


### Added
- Public package shape for reusable Symfony core utilities.
- Audit logger contracts and DTO model.
- Lightweight scheduler runner with optional locks and audit logging.
- Dictionary seeder contracts and runner.
- File-based notification template loading and Twig rendering helpers.
- Metadata traits for Doctrine/API Platform entities.

### Removed
- Application-specific auth flows coupled to `App\\*` classes.
- Refresh-token entity tied to Gesdinet JWT refresh-token bundle.
- Concrete payment gateway integrations.
- Legacy test helpers coupled to a host application.
