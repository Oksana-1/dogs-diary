# Share session authentication between pages and API

Status: Accepted

## Context

Twig pages and Vue islands belong to one browser application and need the same
authenticated identity.

## Decision

Use Symfony Security form login with email, hashed passwords, server-side
sessions, and optional remember-me cookies. Throttle login attempts and protect
login, logout, and password-reset forms with CSRF tokens. API writes require
the session's CSRF token in `X-CSRF-TOKEN`.

Password recovery uses emailed, expiring, single-use reset tokens. Anonymous
requests to protected pages redirect to login; API requests receive JSON `401`.

## Consequences

Pages and API share one login lifecycle. Cookie authentication requires CSRF
protection; production instances require [shared security state](../db/shared-security-state.md).
Password recovery also depends on email delivery.

## References

- [Security configuration](../../../../config/packages/security.yaml)
- [Authentication responses](../../../../src/Security/RequestAwareSecurityHandler.php)
- [API CSRF protection](../../../../src/EventSubscriber/ApiCsrfProtectionSubscriber.php)
- [Password recovery](../../../../src/Controller/Web/PasswordResetController.php)
