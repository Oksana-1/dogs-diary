# Integration and browser testing plan

## Goal

Use Playwright to verify a small set of complete Dogs Diary journeys in a real
browser against a running Symfony application. These tests should prove that
Twig pages, Vue islands, ImportMap modules, JSON APIs, CSRF protection,
authentication, the database, and private media work together.

Playwright tests are end-to-end browser tests in this plan. They complement the
existing Symfony functional tests and the planned frontend component tests; they
do not replace either suite.

## Why browser tests are valuable here

Several important behaviors cross boundaries that jsdom and Symfony's browser
client cannot fully exercise:

- Vue islands mounting into server-rendered Twig pages;
- ImportMap module loading in an actual browser;
- authenticated navigation and session-expiry redirects;
- native form, dialog, focus, and keyboard behavior;
- multipart file selection and media preview or playback;
- end-to-end CSRF token propagation from HTML to API mutations.

## Infrastructure work

1. Add Playwright to the frontend package toolchain and commit its lockfile.
2. Add a Playwright configuration with one canonical local command and one CI
   command.
3. Start the application through a deterministic test-only web server or Docker
   Compose profile and wait for `/healthz` before running tests.
4. Use an isolated test database. Apply the complete migration chain before the
   suite and load only explicit test data.
5. Use isolated temporary upload storage and clean it after the suite.
6. Provide test-only data setup through fixtures, factories, or a controlled CLI
   helper. Do not create a publicly reachable test-reset HTTP endpoint.
7. Use Mailpit or another captured test transport for password-reset journeys;
   never call the production email provider.
8. Run Chromium first. Add Firefox or WebKit only when there is a supported
   compatibility requirement or evidence of browser-specific risk.
9. Retain traces, screenshots, and video for failures. Avoid retaining uploaded
   personal test data longer than necessary.

## Initial critical journeys

### Smoke suite for every pull request

1. **Authentication and session**
   - Register a unique user.
   - Confirm the authenticated landing page loads.
   - Log out and verify protected navigation returns to login.

2. **Dog lifecycle**
   - Create a dog with required and representative optional fields.
   - Open the detail page and verify the saved values.
   - Edit the dog and verify the visible update.
   - Delete it through the confirmation flow and verify it disappears.

3. **Treatment lifecycle**
   - Create a dog and add a treatment.
   - Verify treatment type, product, dates, and note.
   - Edit and delete the treatment through the visible UI.

4. **Client-side and server-side failure behavior**
   - Submit an invalid form and verify accessible validation feedback.
   - Simulate or arrange an API failure and verify the form remains recoverable
     without losing meaningful user input.

### Full browser suite on `main` or a schedule

5. **Dog media**
   - Upload a small committed image fixture.
   - Select it as thumbnail and profile media.
   - Reload the page and verify both selections persist.
   - Delete it and verify the UI and private download route no longer expose it.

6. **Treatment media**
   - Upload an image for a treatment.
   - Replace it and verify the new image is visible.
   - Remove it and verify the empty state.

7. **Ownership isolation**
   - Create data for two users.
   - Verify the second user cannot navigate to, fetch, mutate, or download the
     first user's resources.
   - Keep detailed authorization permutations in the faster backend suite; the
     browser test needs only representative end-to-end proof.

8. **Password reset**
   - Request a reset for a known user through the UI.
   - Read the message from the captured test mailbox.
   - Reset the password, verify the token cannot be reused, and log in with the
     new password.

9. **Expired session**
   - Expire or invalidate the authenticated session through controlled test
     setup.
   - Trigger an API operation and verify one safe redirect to the login page.

## Selector and assertion policy

- Prefer role, accessible name, associated label, and visible user-facing text.
- Use `data-test` only where semantic selectors are ambiguous or unstable.
- Assert outcomes visible to the user or observable at a public HTTP boundary.
- Do not couple tests to CSS classes, generated IDs, internal Vue state, or DOM
  nesting.
- Wait for explicit UI or network outcomes, not arbitrary timeouts.
- Keep assertions focused enough that a failure explains which product behavior
  broke.

## Reliability rules

- Generate unique user identities and never share mutable records across tests.
- Prefer API or CLI setup for prerequisites that are not the behavior under
  test; exercise the UI for the actual journey being verified.
- Avoid unconditional retries. A retry may collect diagnostics temporarily, but
  a flaky test needs an owner, root-cause issue, and resolution deadline.
- Keep fixtures small and license-safe. Include at least one valid image; add a
  tiny video only when video playback or range behavior is tested in-browser.
- Control the clock and timezone for date-sensitive scenarios.
- Make tests parallel-safe before enabling parallel workers.
- Never run state-changing browser tests against staging or production unless
  that environment is explicitly disposable and isolated.

## CI shape

The proposed jobs are:

| Job | Trigger | Scope |
|---|---|---|
| Browser smoke | Pull requests and pushes | Chromium, critical journeys |
| Browser full | `main`, manual, or nightly | Chromium, all journeys |
| Deployment smoke | After an authorized deployment | Read-only health and page-load checks |

Keep deployment smoke checks separate from Playwright's state-changing suite.
For Railway, a production smoke check should verify `/healthz`, TLS, the public
authentication page, and static/module loading without registering users or
changing application data. Operational release steps remain in the
[Railway deployment runbook](../../RAILWAY_DEPLOYMENT.md).

## Completion criteria

- One documented command starts the isolated application and runs Playwright.
- The pull-request smoke suite is deterministic and has an agreed runtime
  budget.
- Authentication, dog CRUD, treatment CRUD, representative media handling, and
  ownership isolation are covered in a real browser.
- CI publishes useful failure artifacts without exposing secrets.
- Browser tests use isolated database and upload storage and are safe to rerun.
- Production deployment checks remain read-only.

