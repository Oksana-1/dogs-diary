# Testing quality plan

This directory describes the intended test strategy for Dogs Diary. It is a
planning document, not a claim that every tool or quality gate described below
is already implemented.

The strategy separates three concerns:

- [Frontend tests](frontend/README.md) protect JavaScript domain behavior, API
  contracts, composables, and Vue component behavior in a fast DOM-based test
  environment.
- [Backend tests](backend/README.md) protect PHP domain rules, application
  services, Symfony request handling, persistence, security, and media storage.
- [Integration and browser tests](integrations/README.md) protect a small set of
  complete user journeys in a real browser against a running application.

## Current baseline

As of 2026-09-23, the executable baseline is:

| Area | Current suite | Result | Measured coverage |
|---|---|---:|---:|
| Backend | PHPUnit | 87 tests, 752 assertions | Not configured |
| Frontend | Node test runner | 4 tests | Not configured project-wide |
| Browser journeys | None | Not applicable | Not applicable |

The backend suite already provides substantial functional coverage around
authentication, authorization, CSRF protection, ownership isolation, password
reset, private media delivery, and production error handling. The frontend
suite currently covers only the shared HTTP and session-expiry behavior. There
is no Playwright suite yet.

Do not use file counts, test counts, or coverage from only the modules loaded by
a single test as a project coverage percentage. A trustworthy percentage must
instrument all applicable production files.

## Quality principles

1. Test behavior and public contracts, not implementation details.
2. Put each behavior at the lowest level that can verify it honestly.
3. Keep the fast frontend and backend suites as the primary feedback loop.
4. Use browser tests only for high-value cross-layer journeys.
5. Make every test independent, deterministic, and safe to retry.
6. Treat security boundaries, ownership isolation, migrations, and private
   media as release-critical behavior.
7. Measure coverage to find blind spots; do not add low-value assertions merely
   to reach a percentage.

## Proposed delivery order

### Phase 1: establish honest measurement

- Add a PHP coverage driver to a dedicated CI step and publish a machine-readable
  report.
- Configure the frontend package toolchain, Vitest, Vue Test Utils, jsdom, and
  V8 coverage.
- Record the initial whole-project baselines without introducing an arbitrary
  high threshold.
- Exclude generated code, migrations, configuration, and templates only when
  the exclusion has a documented reason.

### Phase 2: close high-risk unit and component gaps

- Add direct backend tests for application services, business-date validators,
  entity invariants, and media cleanup behavior.
- Add frontend tests for repositories, entities, composables, dialogs, and the
  two main Vue islands.
- Add coverage gates only after the baseline is stable. Initially prevent the
  baseline from decreasing; raise targets gradually for changed code and
  critical modules.

### Phase 3: add a small Playwright suite

- Add browser infrastructure and deterministic test data.
- Implement the critical journeys listed in the integration plan.
- Run a small smoke subset on every pull request and the complete browser suite
  on `main` or on a scheduled workflow.
- Keep screenshots, traces, and videos only for failed CI runs, with an explicit
  retention period.

### Phase 4: make release evidence visible

- Publish test and coverage summaries in CI.
- Document ownership and a triage rule for flaky tests.
- Require the stable test jobs in branch protection.
- Keep deployment smoke checks read-only and separate from the destructive
  browser suite. See the [Railway deployment runbook](../RAILWAY_DEPLOYMENT.md).

## Definition of a healthy test system

The plan is successful when:

- a developer can run each suite using one documented command;
- failures identify a product behavior rather than a private implementation;
- frontend, backend, database, and browser jobs are isolated and reproducible;
- the coverage reports include all eligible production files;
- critical authentication, ownership, CRUD, and media journeys run in a real
  browser;
- no test relies on production data or modifies the production environment;
- flaky tests are fixed or quarantined with an owner and deadline, never hidden
  behind unconditional retries.

