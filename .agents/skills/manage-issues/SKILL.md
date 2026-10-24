---
name: manage-issues
description: Create and maintain the Dogs Diary GitHub backlog, including individual issues, parent features, sub-issues, labels, milestones, and progress. Use for requested issue management or release-plan conversion, not application implementation or general GitHub questions.
---

# Manage Dogs Diary issues

Turn agreed work into a concise, current GitHub backlog. GitHub holds tasks and progress; the repository holds durable documentation and accepted technical decisions.

## Project conventions

- Write all GitHub content and repository documentation in English. Converse in Ukrainian unless the user requests otherwise.
- One implementation issue may be active across the project at a time. Parent features aggregate progress and do not count as a second active task.
- Use `feature`, `bug`, and `maintenance` according to the work. A parent feature can contain children of different types.
- Group release work with a milestone and related implementation tasks with actual GitHub parent/sub-issue relationships. A Markdown link alone is not that relationship.
- Keep each implementation issue focused on one observable result. Avoid separate backend/frontend issues when one small vertical slice is easier to verify; split when size, dependencies, or security review justify it.

## Establish the requested scope

Use current conversation decisions and the repository's `AGENTS.md`. Identify whether the user wants a draft, one published issue, a grouped backlog, or an update. A creation request authorizes publishing that issue and its agreed tracking relationships; do not request the same permission again. It does not authorize implementing code, starting work, creating unrelated issues, closing work, or opening a PR.

For “one more,” use the agreed queue and state which item you are creating. Create only that item, plus necessary agreed labels/milestone/link updates. Leave future children explicitly pending rather than silently expanding the batch.

Confirm the target repository from the current checkout's Git remote; the established project is `Oksana-1/dogs-diary`. If the checkout points elsewhere, resolve the mismatch before writing. Inspect only code and documentation relevant to the task. Separate observed defects from audit targets; plans can be stale.

Search existing open and closed issues, including pagination where needed, before creation. GitHub's issues endpoint also returns PRs: distinguish them. Check existing labels, milestone, and parent relationships. Reuse suitable existing items; do not duplicate an old feature issue merely because it has a sparse description. Agree a material scope change before repurposing an existing issue.

## Write an actionable issue

Scale the body to the task. Usually include:

- The concrete problem and expected user-visible result.
- Scope and meaningful exclusions.
- A short checklist of observable acceptance criteria, including relevant failure/access cases.
- Unresolved decisions and genuine dependencies.
- Relevant verification and parent/milestone context.

Preserve settled choices. Record open technical choices as open; creating an issue does not approve an implementation approach. Use the project's shaping process before dependent implementation, without blocking backlog creation on every future decision.

Use `docs/README.md` to locate applicable decisions and `docs/adr/tooling/devops/ci.md` for configured checks. Read `tests/JavaScript/TESTING.md` before prescribing frontend test tooling changes. Do not invent executable commands, claim tests were run, or imply transport tests establish UI correctness. Tests and API/documentation updates belong in the relevant implementation issue's completion criteria.

Do not copy local credentials, private environment values, or machine-specific temporary paths into GitHub. Link repository files using repository-relative paths or GitHub links.

## Parent features and progress

A parent describes the complete user outcome, expected child scope, feature-level criteria, and closure condition. Create/link only the children requested in the current scope. Identify not-yet-created children explicitly so the current sub-issue count is not mistaken for total planned scope.

After creating a child, add the native sub-issue relationship and update parent/child placeholders to actual issue references. Do not replace another parent without resolving that change with the user. Fetch the latest body before edits and preserve unrelated text.

New backlog items start planned, not in progress. Use the repository's established status mechanism; avoid adding a second competing status system. Before marking implementation active, check for another active task. If switching from blocked work, record the blocker and explicitly return or pause that task in the queue first.

Use GitHub's child completion counts for progress. Close a parent only when all intended children are complete and the combined acceptance criteria have been verified. Closing the only linked child is insufficient when more children are still planned. Do not close issues merely because code was written or an agent reported completion.

## Access, publishing, and cleanup

Prefer an available GitHub connector or authenticated CLI; a direct GitHub API client is an acceptable fallback. Check current API documentation when endpoint behavior is uncertain. Verify current permissions as needed rather than assuming an earlier token configuration still applies.

For this project, the user may provide `GITHUB_TOKEN` in the checkout's `.env.local`. Read only as needed through a dotenv parser (Symfony's installed Dotenv is available), not by executing the file as shell code. Keep the token in memory and send it only to the intended GitHub API over HTTPS. Never print it, include it in command arguments, persist it in helper files, or forward authorization on redirects. Do not dump environment files or authorization headers.

When temporary helpers or request payloads are needed, create them in a unique system temporary directory, outside the repository. Track the exact files created. Arrange cleanup on success, failure, and interruption where possible; before handoff remove those helpers/payloads and verify their absence. Remove only files created for this operation. If interrupted before cleanup, finish cleanup when resuming. Do not retain temporary helpers as a substitute for a maintained skill resource.

Use structured API arguments or body files that preserve actual newlines. Treat shell text as code; JSON encoding is not shell escaping. Never log raw requests containing authorization.

Before retrying a timed-out or uncertain write, read GitHub state to determine whether it succeeded. Do not blindly repeat creation. If authentication, permissions, or connectivity prevent publishing, preserve useful draft content in the conversation, report the exact blocker and any successful partial writes, clean up temporary files, and stop dependent work.

## Verify and report

Confirm each write from the API response or a follow-up read: issue URL, label/milestone assignment, requested parent relationship, and accurate parent text/progress. Retain created IDs during the operation so a partial failure can be repaired without duplicates.

Return the created/updated links, grouping or status changes, remaining uncreated children where relevant, and cleanup result. Keep the handoff brief. Creating backlog items does not establish application readiness or require running application tests.
