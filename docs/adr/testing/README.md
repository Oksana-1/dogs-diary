# Testing quality plan

This directory describes the intended test strategy for Dogs Diary. It is a
planning document, not a claim that every tool or quality gate described below
is already implemented.

The strategy separates three concerns:

- [Frontend tests](frontend/README.md) protect JavaScript domain behavior, API
  contracts, composables, and Vue component behavior. The configured suite uses
  QUnit in Chrome, with Vue Test Utils for component tests.
- [Backend tests](backend/README.md) protect PHP domain rules, application
  services, Symfony request handling, persistence, security, and media storage.
- [Integration and browser tests](integrations/README.md) protect a small set of
  complete user journeys in a real browser against a running application.

## Historical baseline

On 2026-09-23, the executable baseline was:

| Area | Suite at that date | Result | Measured coverage |
|---|---|---:|---:|
| Backend | PHPUnit | 87 tests, 752 assertions | Not configured |
| Frontend | Node test runner | 4 tests | Not configured project-wide |
| Browser journeys | None | Not applicable | Not applicable |

The backend suite already provided substantial functional coverage around
authentication, authorization, CSRF protection, ownership isolation, password
reset, private media delivery, and production error handling. The frontend
suite then covered only the shared HTTP and session-expiry behavior. There
was no Playwright suite.

Do not use file counts, test counts, or coverage from only the modules loaded by
a single test as a project coverage percentage. A trustworthy percentage must
instrument all applicable production files.

## Current tooling

The approved frontend setup uses QUnit and Vue Test Utils through an isolated
Symfony kernel and ImportMap, replacing the earlier Vitest/jsdom proposal.
The CI workflow and `bin/check frontend` are configured to run transport
and component tests together in QUnit, including the migrated Node scenarios. Commands, prerequisites, watch mode, and single-file
selection are canonical in the [frontend testing guide](frontend/testing-guide.md).
See [CI and local checks](../tooling/devops/ci.md) for the complete quality gate.
Configured CI behavior does not establish that a hosted run has passed.

Whole-project coverage reporting and complete Playwright journeys remain
planned work. Component tests run in Chrome but do not exercise the complete
application, database, authentication, or media-storage workflows.

## Quality principles

1. Test behavior and public contracts, not implementation details.
2. Put each behavior at the lowest level that can verify it honestly.
3. Keep the fast frontend and backend suites as the primary feedback loop.
4. Reserve end-to-end browser tests for high-value cross-layer journeys;
   browser-executed component tests remain unit tests.
5. Make every test independent, deterministic, and safe to retry.
6. Treat security boundaries, ownership isolation, migrations, and private
   media as release-critical behavior.
7. Measure coverage to find blind spots; do not add low-value assertions merely
   to reach a percentage.

## Proposed delivery order

### Phase 1: establish honest measurement

- Add a PHP coverage driver to a dedicated CI step and publish a machine-readable
  report.
- Select and configure whole-project frontend coverage reporting for the
  existing QUnit suite; no coverage tool or threshold is configured yet.
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
  browser suite. Hosted Preview tests require isolated data and storage, with
  credentials separate from production.

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
