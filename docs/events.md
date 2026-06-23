# Events

Core does not define an application event catalog. Use Symfony events in the host app for domain workflows.

For package-level extension points prefer explicit contracts:

- audit publisher,
- dictionary persister,
- notification template resolver,
- scheduler jobs.

This keeps the public API smaller and easier to version.
