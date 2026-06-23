# Security

The package avoids owning authentication and authorization. It provides small utilities and expects the host app to configure Symfony Security, JWT/session auth, voters and access-control rules.

Recommended rules for integrations:

- Do not expose host user entities from Core.
- Depend on Symfony `UserInterface` or your own host contracts.
- Keep token issuing behind `TokenIssuerInterface`.
- Put provider-specific auth integrations in bridge packages.
