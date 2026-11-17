# Changelog

All notable changes to this package will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project uses semantic versioning.

## [Unreleased]

### Added
- `Core\Observability\FailureDetails::from()` — safe structure of a failure for logs and error trackers
  (GlitchTip/Sentry): exception class, category (smtp_auth, dns, timeout, tls, database_schema, database,
  connection, recipient, template_or_payload, application), SQLSTATE or SMTP code, fingerprint, origin file
  and line, causes and stack frames without arguments. Never the exception message, SQL or request data.
- `app:deploy:check` (`Core\Command\DeploymentCheckCommand`) with `DeploymentCheckRunner`: read-only checks
  from host services implementing `DeploymentCheckProviderInterface` (autoconfigured, tag
  `core.deployment_check`), console report first, then one report mail through the host's
  `DeploymentReportMailerInterface`; `--no-email`, `--report-to`, `--json`. Opt-in: without a bound mailer the
  command is not registered. Ported from PokazSie's local copy (2026-11-06).

### Changed
- Scheduler failures log `Scheduler job failed [<job>]` (job in the title so trackers group per job) with
  `job`, `phase`, `run_id` and `FailureDetails`; the audit entry stores `error: scheduler_job_failed` and the
  run id instead of the exception message.

## [1.2.1] - 2026-11-13

### Fixed
- PayU webhook reports `paidMoney` from the signed notification (`order.totalAmount` + `currencyCode`), so the
  host can reject settlement when the paid amount or currency differs from the expected payment.
- PayU refund notifications (`refund.status = FINALIZED`) map to `Refunded` with the refunded amount; pending
  refunds stay `Pending`. Previously they were rejected as malformed and retried by PayU.

## [1.2.0] - 2026-09-25

### Added
- Entity-change audit: mark Doctrine entities with `#[Core\Audit\Attribute\Auditable]` and
  `EntityChangeAuditListener` records `<resource>.created|updated|deleted` events with a before/after diff
  through `AuditLogger`. Credential-like fields are always redacted, `redactedFields`/`ignoredFields` refine it
  per entity, relations are stored as identifiers. Recording happens after a successful flush and is best
  effort (a failing persister is logged, never breaking the business write). Enabled only when the host binds
  `AuditPersisterInterface`.
- `core.scheduler.audit_runs` option (default `true`) to stop writing a `scheduler.job.executed` audit event
  for every run.

## [1.1.1] - 2026-09-25

### Fixed
- Host services implementing `SchedulerRunListenerInterface` are now auto-tagged `core.scheduler.run_listener`
  (registered for autoconfiguration in `CoreExtension`); in 1.1.0 only the bundle's own services were tagged,
  so application listeners were never called.

## [1.1.0] - 2026-09-25

### Added
- `SchedulerRunListenerInterface` and `SchedulerJobRun`: optional hook called after every executed job
  (auto-tagged `core.scheduler.run_listener`). Consumers without listeners are unaffected; a failing
  listener is logged and does not stop the remaining jobs.
- `app:scheduler:tick` alias of `core:scheduler:tick` for projects migrating from embedded copies.
- Features needing a host adapter are opt-in (`OptionalHostContractsPass`): without bindings for
  `AuditPersisterInterface`, `DictionaryPersisterInterface` or `TokenIssuerInterface` the dependent services
  are removed instead of breaking container compilation; `SchedulerRunner` then runs without audit logging.

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
