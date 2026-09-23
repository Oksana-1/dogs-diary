# Frontend testing plan

## Goal

Provide fast, behavior-focused protection for the JavaScript under `assets/`
without changing the application's AssetMapper and ImportMap runtime model. A
Node package toolchain is needed for tests, but it does not need to become a
production asset build step.

The canonical frontend testing rules remain in
[`tests/JavaScript/TESTING.md`](../../../tests/JavaScript/TESTING.md). Update that
guide and the package scripts together when the toolchain is introduced.

## Current state

- There are 37 JavaScript source files under `assets/js`.
- `tests/JavaScript/FetchClient.test.mjs` is the only frontend test file.
- It contains four passing tests using Node's built-in test runner.
- The tested path covers credentials, CSRF behavior, structured API errors, and
  shared expired-session handling.
- Vitest, Vue Test Utils, jsdom, package scripts, and whole-project frontend
  coverage are not configured.
- Vue components, composables, repositories, and domain entities do not yet
  have dedicated automated tests.

The built-in Node coverage report for the current test is useful only for the
few modules that test imports. It must not be presented as coverage for all
frontend code.

## Tooling work

1. Add a committed package manifest and lockfile using one package manager.
2. Add Vitest, Vue Test Utils for Vue 3, jsdom, and the V8 coverage provider.
3. Define canonical scripts for:
   - the complete frontend suite;
   - watch mode;
   - one test file;
   - coverage with all eligible source files included.
4. Add a shared `createComponent` helper following the contract in the canonical
   testing guide.
5. Decide whether to migrate `FetchClient.test.mjs` to Vitest. Until migration
   is intentional and complete, keep its current Node command working and avoid
   duplicate versions of the same tests.
6. Configure stable aliases or import resolution for the same module paths used
   by the browser application.
7. Add the canonical commands to CI and update `docs/CI.md`.

## Coverage priorities

### Priority 1: shared infrastructure

- Complete `FetchClient` coverage for cancellation, empty responses, malformed
  responses, server errors, validation errors, and session expiry.
- Test `SessionExpiryRedirect` for repeated notifications and safe redirect
  construction.
- Test every repository's URL, HTTP method, payload or `FormData`, response
  mapping, error propagation, and cancellation behavior.

### Priority 2: domain objects and composables

- Verify normalization and derived behavior in `Dog`, `Treatment`, and media
  entities.
- Test collection replacement, lookup, ordering, and mutation behavior.
- Test composable loading, empty, success, validation, API failure, retry,
  cancellation, and stale-response states where applicable.
- Use real domain entities when their behavior matters and mock repositories at
  the architectural boundary.

### Priority 3: reusable UI

- Test modal accessibility, focus behavior, dismissal, confirmation, and pending
  states through rendered behavior and emitted events.
- Test form validation and preservation of user input after a failed request.
- Test accessible labels, disabled states, and keyboard interaction.
- Test the birthday celebration only where date-dependent visible behavior is
  meaningful; control the clock rather than relying on the current date.

### Priority 4: page islands

- Verify the dog list's loading, empty, success, create, edit, delete, and error
  states with repository boundaries mocked.
- Verify the dog detail page's profile, treatment, and media collaboration
  without full-mounting unrelated large subtrees.
- Verify parent-child contracts through props and emitted events rather than
  component internals.

## Coverage policy

The initial coverage run must include all eligible files under `assets/js`, not
only files imported during the tests. Record lines, branches, and functions.

Do not choose a target until the baseline report exists. A reasonable rollout
is:

1. publish the report without a blocking threshold;
2. fail CI when total coverage decreases materially;
3. require strong coverage for new or materially changed modules;
4. raise global thresholds as the priority areas are completed.

Coverage is not sufficient evidence for modal accessibility, browser
navigation, native dialog behavior, file selection, or complete authenticated
workflows. Those belong in the browser plan.

## Quality checklist

- Tests assert rendered output, accessible state, events, or collaborator calls.
- Tests never call or mutate component internals through `wrapper.vm`.
- Selectors prefer role, accessible name, label, or deliberate `data-test`.
- Async work is awaited without arbitrary sleeps.
- Replaced globals, timers, object URLs, listeners, and mounted wrappers are
  cleaned up.
- Each test arranges its own data and does not depend on execution order.
- Production code is not expanded solely to expose private test hooks.
- The relevant test command is run after every frontend implementation or test
  change.

## Completion criteria

- The toolchain and commands are documented and reproducible locally and in CI.
- All repositories and shared HTTP behavior have contract tests.
- Critical composables cover success, empty, pending, failure, and cancellation
  behavior where applicable.
- The main user-facing states of both Vue islands have component coverage.
- Whole-project coverage is published and protected against regression.

