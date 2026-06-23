# Changelog

All notable changes to this package will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project uses semantic versioning.

## [Unreleased]

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
