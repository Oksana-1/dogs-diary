# Local API reference

Run the application with `APP_ENV=dev`, sign in through `/login`, then open
`/api/docs` on the same origin. The page lists all diary API operations with
request examples, response schemas, and `Try it out`. Requests use the current
session and automatic CSRF headers; writes change the local application's data.

Both `/api/docs` and `/api/docs/openapi.json` are registered only in `dev`.
Preview and production use `prod`, where these routes are absent.

The page loads pinned Swagger UI 5.33.1 scripts/styles from jsDelivr with integrity
checks, so internet access is required. It disables the external spec validator
and restricts requests to the current origin. No Node build or Symfony bundle is
needed. The raw JSON is also linked from the page.

Maintain [openapi.yaml](openapi.yaml) alongside API changes. Its source is the
controllers, request DTOs, and response views. A test compares documented
operations with registered diary routes to catch missing or stale endpoints;
schema details still require review when payloads or responses change.

Local storage supports multipart uploads directly. R2's ticket flow is documented
as well; its signed PUT is a separate storage request, outside this page's
same-origin `Try it out`.
