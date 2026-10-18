---
name: adr-init
description: Create concise Dogs Diary ADRs for architecture, domain, testing, or tooling decisions, with a shared index. Use only when explicitly requested.
---

# Create decision records

Create initial or additional ADRs for significant project decisions. Write documentation in concise English. Invoke only on request.

## Select decisions

An ADR captures a lasting rule, constraint, or choice with meaningful consequences, not every implementation detail. Review the index and related records before proposing new ones; link to an existing explanation instead of duplicating it. Domain records describe business rules; architecture records describe how the system enforces or supports them.

Keep one decision per ADR. Consider separate records when choices can change independently, but keep supporting mechanisms together when they only explain the main decision.

## Agree before writing

Inspect existing documentation and the relevant code. Propose a short list of decisions to document, the evidence for each, and any unanswered questions. Ask in rounds of 1–3 questions and wait before dependent edits. Carry forward choices already approved in the conversation.

Code establishes current behavior, not the historical reason for a choice. Describe evident responsibility boundaries directly; do not ask the user to justify routine separation of concerns. Discuss missing project-specific rationale or meaningful tradeoffs before writing dependent records. Do not invent historical motivations or rejected alternatives, or treat a proposal as an accepted decision.

## Verify evidence

Research deeply within the agreed scope; keep the record short.

- Establish the source: current implementation, a specific diff, or an agreed plan. For a diff, establish the intended base and revision range rather than assuming a branch. Distinguish planned behavior from implemented behavior; neither a plan nor code in a branch alone establishes acceptance.
- Trace the decision through the relevant layers and boundaries, not just one class or configuration file.
- Check actual wiring, callers, and relevant tests, not only definitions. Reading a test establishes what it covers, not that it passes.
- Look for exceptions and limitations, but include only those that materially affect the decision's meaning or consequences. Do not turn the ADR into an implementation catalog.

## Files and content

Use `docs/README.md` as the project overview and complete documentation index. Keep it the only file at the root of `docs`; put all other documents under `docs/adr/`. Use descriptive kebab-case filenames such as `shared-security-state.md`, without numeric prefixes or numbered titles.

Under `docs/adr/`, prefer `architecture`, `domain`, `testing`, and `tooling` as top-level categories. Technical areas can use `backend`, `frontend`, `db`, or `devops` subfolders. Group domain records by business concept (`dogs`, `treatments`, `media`), keeping shared rules at the domain root. Treat this as flexible navigation, not a mandatory architecture model. These conventions guide placement; they do not authorize reorganizing existing documentation outside the agreed scope.

Use this compact structure, omitting optional detail that adds no value:

- Title: the decision, without a number.
- Status: proposed, accepted, or superseded; confirm acceptance when unclear.
- Context: the concrete constraint or problem.
- Decision: the chosen approach and affected modules or layers.
- Consequences: meaningful benefits and costs; mention alternatives only when known.
- References: a few relative links to relevant code or related ADRs. Keep records self-contained; do not link to legacy README files or other legacy documentation slated for removal.

Aim for 100–200 words excluding References, as a guideline rather than a limit. Explain the choice rather than cataloguing classes or restating implementation details. Avoid repeating information across Context, Decision, and Consequences; consequences should add concrete effects or tradeoffs, not praise the choice. Cover only decisions relevant to the agreed scope.

The index links to every other file under `docs`, including guides and plans. For ADRs, include linked title, status, and affected modules or layers. Keep it navigable without repeating the documents' explanations; do not label guides or plans as accepted decisions.

Preserve accepted records as decision history. A changed decision gets a successor ADR, with supersession links and an updated index. Correct factual inaccuracies, wording, or references in place when the decision itself is unchanged. A code–ADR mismatch alone does not establish a new decision; clarify whether it is documentation error, implementation drift, or an approved change before revising the record.

## Finish

Check filename conventions, status consistency, and relative links. Verify implementation claims against the inspected code. Report created or changed files, validation performed, and unresolved rationale. This task changes documentation only; application changes require a separate request.
