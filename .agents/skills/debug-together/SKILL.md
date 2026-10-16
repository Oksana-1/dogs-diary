---
name: debug-together
description: Diagnose Dogs Diary application, environment, deployment, and integration failures with short evidence-based checks and timely user questions. Use for bugs, failed checks, or configuration uncertainty; agree on the technical fix before implementation.
---

# Debug together

Narrow the cause using evidence and the user's context. Ask early when an inaccessible fact could determine the next step. The user chooses the technical fix; a diagnosis request alone does not authorize implementation.

Proactively review the user's proposed technical fixes and offer improvements when useful, even without an explicit review request. Explain concerns with diagnostic evidence and tradeoffs, distinguish correctness issues from preferences, and recommend alternatives. The user retains the final decision. Do not silently substitute a fix or repeatedly reopen a settled choice without new evidence.

## Establish the failure and environment

Start with the observed symptom, expected behavior, affected environment or deployment, and recent relevant changes. Reuse information already provided. Inspect the smallest relevant code, configuration, or error output through available, authorized access.

Separate observations from hypotheses and unknowns. Local, test, Preview, and Production can differ. Missing local settings do not prove missing hosted settings, and a successful local check does not establish hosted success. Follow repository rules for isolated tests and data access; do not inspect production data without authorization.

When relevant facts are inaccessible, ask 1–3 direct questions rather than building on guesses. Explain why the answer matters. For example, when a Vercel database connection failure makes configuration relevant:

> Does this fail in Preview or Production? Have you set DATABASE_URL in Vercel for that environment? Please confirm its presence and scope without sharing the value.

Ask a follow-up about when configuration changed relative to the affected deployment if that distinction matters. This is an example, not a mandatory Vercel checklist for unrelated failures. Do not request credentials or secret-bearing logs in chat.

## Run bounded diagnostic cycles

Choose the most useful hypothesis from the available evidence. Before a check, identify what it tests and how the possible results affect the next step. Use the smallest safe check that can distinguish the likely causes. Ordinary, relevant read-only diagnostics do not need separate user approval.

After a result, state what it establishes, what remains uncertain, and the next useful action. A negative result is progress if it rules out a cause. Repeating a check with the same inputs is not a new investigation.

- After two unsuccessful attempts to test the same hypothesis without new evidence, stop that line of investigation and explain the missing fact or access needed. Request user input when it is needed; do not rename a hypothesis to reset the limit.
- If 10–15 minutes pass without meaningful progress, stop and return the known facts, checks already attempted, remaining unknowns, and the smallest question or proposed check that would unblock the work.
- If a required answer, access, or usage reset blocks progress, hand back the task clearly. Do not sleep or poll for hours. These are workflow checkpoints, not a claim of enforced token or runtime limits.

While a question is pending, do only relevant work independent of its answer. Do not infer an answer from silence. Do not replace an unverified setting with an architectural workaround, add dependencies, or change providers as a diagnostic shortcut. Do not delegate unless the user has requested agents or another applicable instruction explicitly authorizes delegation.

## Agree on the fix and verify it

When evidence supports a cause, describe the cause and its supporting observations. Present the concrete proposed fix, meaningful alternatives if any, tradeoffs, and a recommendation. Use shape-task if substantial behavior or approach decisions remain. Wait for the user's technical choice before implementing dependent changes; reuse a previously selected approach when it still applies.

For an authorized fix, change only the agreed scope and run the relevant configured checks in the repository's isolated test environment. Report exact commands and observed results, separating passed, failed, and not run. After a change, rerun affected checks; do not repeat successful unrelated suites without a reason.

Finish with the diagnosed cause or explicit uncertainty, changes if any, verification evidence and limits, and any specific action needed from the user. A local fix with passing tests is not evidence that an inaccessible deployment has recovered.
