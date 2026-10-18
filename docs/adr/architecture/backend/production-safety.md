# Production safety

Current runtime/security requirements, updated for Vercel and R2 on 16 October 2026.

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
- Session and remember-me cookies are HTTP-only and SameSite `Lax`. Production
  session cookies are always Secure; remember-me cookies follow the request
  scheme seen through the hosting edge's trusted proxy.

## Uploads

- Production media uses private R2 storage with direct staged uploads and
  validated finalization. Local development uses `/app/var/uploads`, outside
  the public web root. See [R2 storage](../../tooling/devops/r2-media.md) for the storage boundary.
- Media downloads require owner-scoped authorization. Local files are streamed;
  R2 access uses short-lived signed URLs. Foreign and mismatched IDs return `404`.
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
- Secure production responses also set one-year HSTS. The hosting edge must serve the
  application exclusively over HTTPS before production traffic is enabled.
- Expected 404 and 405 responses are excluded from error-triggered log flushing;
  server errors remain logged.

## Verification

Run the configured quality gate, including PHPUnit and Symfony linters:

```bash
bin/check
```

After runtime/security changes, manually verify in an isolated production build that a
foreign dog/media URL returns 404, invalid logout and API tokens return 403,
an unknown web URL shows the custom 404 page, and response headers include the
hardening values above.
