# Production safety

This document records the production safety pass completed on 2026-09-10.

## Authentication and authorization

- Every application route except `/healthz`, login, registration, password
  reset, and compiled assets requires `ROLE_USER` through `access_control`.
- Anonymous web requests redirect to `/login`; anonymous API requests receive a
  stable JSON `401` response.
- Dog, treatment, and media queries are scoped to the current user. Requests for
  another user's resource deliberately return `404` so resource existence is
  not disclosed.
- Login is throttled to five attempts per minute and returns the same public
  message for every authentication failure.

## CSRF and sessions

- Symfony CSRF protection is enabled globally.
- Login, logout, registration, password-reset request, and password-reset
  confirmation forms use purpose-specific tokens.
- Every authenticated `POST`, `PUT`, `PATCH`, and `DELETE` request below `/api`
  requires the session-bound `X-CSRF-TOKEN` value exposed by the authenticated
  page. Missing or invalid tokens receive JSON `403`.
- Session and remember-me cookies are HTTP-only and SameSite `Lax`; their Secure
  flag follows the request scheme seen through Railway's trusted proxy.

## Uploads

- Uploaded media is stored below `/app/var/uploads`, outside the public web
  root. Production must mount the Railway Volume at exactly that path.
- Media is served only through owner-scoped controllers, with private,
  `no-store`, `nosniff` responses. Foreign and mismatched IDs return `404`.
- The server determines MIME type from file contents, accepts only JPEG, PNG,
  WebP, MP4, and WebM, validates image/container signatures, generates random
  storage names, and enforces 10 MB image and 100 MB video limits.
- Never expose `var/uploads` through Caddy or copy it under `public/`.

## Errors and response hardening

- Production web responses use custom 403, 404, and 500 pages. They contain no
  exception message, stack trace, path, environment value, or secret.
- API errors use a stable JSON envelope. Unexpected errors return only
  `internal_error` plus a generic message; full exceptions go to structured
  stderr logs.
- Responses set `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, a
  restrictive camera/geolocation/microphone `Permissions-Policy`, and
  `Referrer-Policy: strict-origin-when-cross-origin`.
- Secure production responses also set one-year HSTS. Railway must serve the
  application exclusively over HTTPS before production traffic is enabled.
- Expected 404 and 405 responses are excluded from error-triggered log flushing;
  server errors remain logged.

## Thursday verification

Run the complete PHPUnit suite plus Symfony's Twig, YAML, and container linters:

```bash
vendor/bin/phpunit --testsuite "Project Test Suite"
php bin/console lint:twig templates
php bin/console lint:yaml config
php bin/console lint:container
```

Before Saturday's deployment, manually verify in a production build that a
foreign dog/media URL returns 404, invalid logout and API tokens return 403,
an unknown web URL shows the custom 404 page, and response headers include the
hardening values above.
