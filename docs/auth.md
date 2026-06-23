# Auth Utilities

Core intentionally does not ship registration, login or password-reset flows. Those workflows depend on the host application's user entity, security policy and delivery channels.

Included neutral utilities:

- `TokenIssuerInterface` for host-provided token generation.
- `AuthTokenService` for a small `{token: string}` payload wrapper.
- `VerificationCodeGenerator` for six-digit verification codes.
- `AccountRolePolicy` with configurable allowed role names.

Full auth controllers and persistence adapters belong in the host application or a dedicated bridge package.
