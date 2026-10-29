# Release 2 — week of 26 October–1 November 2026

The [Release 2 milestone](https://github.com/Oksana-1/dogs-diary/milestone/1)
remains the source of tasks and acceptance criteria. This document allocates
the remaining week; it does not set a release date or authorize publishing,
merging, deployment, or implementation choices that still need agreement.

## Starting point on 27 October

- [#10: mobile layouts](https://github.com/Oksana-1/dogs-diary/issues/10) is closed
  in GitHub. That status is not a new verification of every checkbox in its body.
- [#13: component tests and CI](https://github.com/Oksana-1/dogs-diary/issues/13)
  is implemented locally and remains open in GitHub. The approved stack is
  QUnit/Vue Test Utils through the isolated Symfony kernel and ImportMap.
  Automatic discovery, single-file/watch commands, and CI integration exist.
- The latest local full check passed 130 PHP tests, 84 browser-executed unit
  tests, 8 transport tests, and 7 operations tests. Single-file selection,
  watch failure/recovery, and failure propagation were also checked. Hosted CI
  and PostgreSQL migration/schema results remain unverified in this session.
- [#12: dialog keyboard and focus behavior](https://github.com/Oksana-1/dogs-diary/issues/12)
  and [#15: persistent language selection](https://github.com/Oksana-1/dogs-diary/issues/15)
  remain open. The old issue text still references the former test-guide path
  and, for #13, the superseded Vitest proposal. Use the current
  [frontend testing guide](../../testing/frontend/testing-guide.md) for tooling;
  reconcile issue wording when an issue update is authorized.

## Capacity and objective

Assuming the previous availability still applies:

- Tuesday–Friday: 2–3 hours per day.
- Saturday–Sunday: 4–5 hours per day.
- Remaining total: 16–22 hours. Schedule 16 hours and retain up to 6 as reserve.

Monday is elapsed and has no retrospective allocation. The budget includes
inspection, decisions, implementation, tests, browser checks, review, and docs.

First finish acceptance of #13, then make #12 the main implementation task.
Completing #12 is the target, not a promise before its approach and browser
checks are agreed. #15 is optional follow-on work. Keep one implementation
issue active at a time.

## Daily plan

| Day | Planned time | Work | Expected result |
| --- | --- | --- | --- |
| Tuesday, 27 October | 2 hours | #13: review the final diff and documentation; reconcile acceptance criteria with the approved QUnit approach. Complete the authorized PR/CI handoff or record the external action still needed. | A precise acceptance status for #13, including hosted CI and migration-check gaps. |
| Wednesday, 28 October | 2 hours | After #13 acceptance, inspect #12 in actual dialog flows. Compare completing the existing modal with native `<dialog>`; agree the approach and supported browser checks. | Recorded choice and observable scenarios for focus, dismissal, background isolation, and cleanup. |
| Thursday, 29 October | 2 hours | #12: implement the agreed initial-focus, focus-containment, and restoration behavior, with relevant behavioral tests. | Verified progress through public interactions; remaining cases recorded. |
| Friday, 30 October | 2 hours | #12: address background interaction, scroll locking, pending/disabled behavior, and close/unmount cleanup within the selected approach. | Focused tests pass; browser validation checklist is ready. |
| Saturday, 31 October | 4 hours | #12: exercise dog forms, treatment forms, confirmations, and photo dialogs in a real browser. Check repeated opens, errors/retries, disabled controls, keyboard navigation, and available assistive technology. Repair confirmed regressions. | Acceptance evidence with browser/device details and explicit unrun checks. |
| Sunday, 1 November | 4 hours | Finish #12 verification, review, and documentation. If accepted with time remaining, inspect #15 and agree locale storage, precedence, fallback, and anonymous/password-reset behavior. | #12 is ready for acceptance or has a bounded carryover list. #15 has an agreed next step only if capacity allows. |

## Transition and fallback rules

- Do not start #12 implementation merely because #13 code exists. Confirm the
  agreed acceptance/PR handoff and distinguish local checks from hosted CI.
- If an external action blocks #13, record the blocker and explicitly return it
  to the queue before making another implementation issue active.
- Create a dedicated issue branch before #12, using an agreed base. Do not carry
  unfinished #13 work into it. Apply the same rule before any #15 implementation.
- If #12 exceeds its allocation, use the reserve and Sunday for #12; defer #15.
- Do not select native `<dialog>`, add a modal dependency, or choose locale
  persistence implicitly through this schedule. Those choices belong to their
  respective issue discussions.
- Parent [#11](https://github.com/Oksana-1/dogs-diary/issues/11) requires both
  child work and verification of the complete feature; #10 being closed alone
  does not establish its completion.

## Verification and end-of-week record

Follow the [CI and isolated-test guide](../devops/ci.md) and the
[frontend testing guide](../../testing/frontend/testing-guide.md).
Run relevant configured checks after implementation changes, and the full
`bin/check` or `bin/check --local` for the final code state when prerequisites
are available. `bin/check frontend` now includes transport and component tests;
it does not replace real-browser focus and complete-flow checks for #12.

Record passed, failed, and unrun checks separately. Keep PostgreSQL migration
verification isolated from development and production data. Required hosted
jobs remain part of acceptance; local success is not evidence of a hosted pass.

At week end, record accepted scope, remaining issues, actual time, unresolved
decisions, and the first next step. Translation issues #16/#17, measurements,
graphs, passkeys, decorative work, and full release verification remain backlog
work unless the weekly scope is explicitly revised.

Updated 27 October 2026 from the local implementation and read-only GitHub issue
status checks. Creating this plan did not run application tests or change issues.
