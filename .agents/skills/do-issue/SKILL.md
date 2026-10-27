---
name: do-issue
description: Coordinate one Dogs Diary GitHub issue through planner, implementer, QA, reviewer, bounded repairs, and a handoff for the user's review. Use when the user requests execution of an issue, such as "$do-issue 10"; not for backlog creation, status-only questions, or unattended scheduling.
---

# Execute one Dogs Diary issue

The main conversation coordinates the existing Dogs Diary agents. The user starts work by naming an issue number or URL, for example `$do-issue 10`. The outcome is a reviewed implementation and an evidence-based handoff for the user's review.

This invocation requests the Dogs Diary agent workflow for that issue. Read the repository's `AGENTS.md` and the role files in `.codex/agents/`; those files remain authoritative for role responsibilities, model choices, editing boundaries, and test rules. Do not duplicate their full instructions here.

## Boundaries

- Work on one implementation issue at a time. Parent features track progress; selecting a parent is not permission to implement every child. Resolve which child to execute before implementation.
- Write repository documentation, code comments, and any authorized GitHub content in English. Converse in Ukrainian unless the user requests otherwise.
- Invocation authorizes scoped local implementation, relevant tests, and agent delegation. Respect existing session authorization for Git actions and external writes; do not infer permission to commit, push, create a PR, merge, deploy, or close issues solely from this skill.
- Use the existing `manage-issues` skill for GitHub access and any separately authorized tracking updates. Use `shape-task` for unresolved consequential choices and `debug-together` for troubleshooting, as required by `AGENTS.md`.
- Do not start a scheduler, background runner, automatic restart, or hours-long wait. Resume only when the user requests it. Do not switch credentials, models, or billing to work around a usage limit.

## Intake and handoff context

1. Resolve the issue against the checkout's Git remote. Read its current body, relevant decisions/comments, parent, and dependencies using `manage-issues`. If the request is closed, duplicated, blocked by unmet prerequisites, or conflicts with another active task, explain the specific conflict before dependent work.
2. Record the original request, acceptance criteria, selected approach already approved by the user, base commit, branch/worktree, and existing tracked/untracked changes. Inspect whether the issue describes current code. Preserve unrelated changes; do not attribute them to this task.
3. Before implementation, create and switch to the issue's dedicated branch following `AGENTS.md`, or reuse its existing branch for continuation. Verify the base and existing changes first; if safe separation from another issue is unclear, ask the user before switching. Record the branch in agent handoffs and the final report. Branch creation does not authorize a push or PR.
4. Establish the applicable test environment and configured checks from `docs/README.md` and `docs/adr/tooling/devops/ci.md`. Read `docs/adr/testing/frontend/testing-guide.md` before frontend test edits. Separate browser, component, transport, backend, and migration evidence.
5. Give every agent the original issue and criteria, relevant accepted decisions, base revision, current changes, assigned files/responsibility, dependencies, test environment, and authorized actions. Tell writers that they share the workspace and must preserve others' changes.

Do not send every agent the whole conversation when a focused handoff provides the needed facts. Keep rejected approaches and accepted choices clear enough to avoid reopening settled decisions.

## Coordination sequence

### 1. Planner

Read `.codex/agents/planner.toml` and use the available `planner` role. Ask for observable numbered criteria, current behavior with code evidence, affected files, implementation steps, dependencies, meaningful checks, and unresolved material decisions. Planner does not edit files.

Review the plan yourself. If a consequential approach is not yet selected, present the concrete recommendation, alternatives, and tradeoffs and wait for the user's choice. An issue's proposed solution is not automatically an approved decision. Continue directly when the existing conversation or accepted decisions already settle the approach; routine details need no repeated approval.

### 2. Implementer

Read `.codex/agents/implementer.toml`. Give the `implementer` the selected plan and explicit ownership of implementation files, relevant tests, and documentation. Keep small Symfony/API/Vue slices together. Only this agent may write during its phase.

Require addressed criteria, changed files and reasons, exact checks/results, and unresolved gaps. Inspect the resulting changes and outputs before advancing; a completion message alone is not verification. Stop the writer before handing files to QA.

### 3. QA

Read `.codex/agents/qa.toml`. Give `qa` the original criteria and implemented code, not just the implementer's success claims. Assign only relevant tests/fixtures for edits and require criterion-by-criterion pass/fail/not-run evidence, reproductions, and exact commands/results.

QA may add missing behavioral tests within that ownership. Production defects go back to the coordinator. No production writer runs concurrently with QA. If a new test toolchain or fixture environment is needed, resolve that choice rather than allowing QA to change unassigned configuration or gates.

### 4. Reviewer

After QA stops writing, read `.codex/agents/reviewer.toml` and have `reviewer` inspect the stable complete diff, including QA additions, against the original criteria and base revision. Reviewer does not edit files. Require actionable findings with evidence, severity, affected locations, and verification limits.

Record the exact code state reviewed, including relevant uncommitted/untracked files; a base commit alone does not identify an uncommitted diff. If any files change afterward, affected checks and review must be refreshed before presenting that state as reviewed.

### 5. Bounded repairs

Evaluate QA/reviewer findings against the evidence and agreed scope. Return confirmed defects to implementer with concrete reproduction/acceptance expectations. Do not mechanically implement disputed preferences or expand architecture without the user's choice.

Allow at most two repair rounds after the initial implementation. Each round is implementer fixes, QA verification of affected behavior, then reviewer inspection of the updated stable diff. Keep the original criteria intact and rerun affected configured checks after changes. Reuse agents when available and provide only the changed context.

After two rounds, hand off remaining findings and evidence. Do not reset the counter by renaming a task or spawning a new coordinator. If the result is clean earlier, stop repairing and complete the handoff.

## Agent availability and shared workspace

Use named roles when available. If a role is unavailable but generic delegation exists, read and pass its complete TOML instructions to a generic subagent and disclose the fallback. Apply its configured model/reasoning only through supported controls; report differences rather than silently claiming equivalence. TOML sandbox settings are not automatically enforced by passing them as text.

If delegation is unavailable, report that the requested independent agent cycle cannot run. Provide completed inspection/planning and the next required action; do not impersonate four independent agents in one conversation.

Use one writer at a time. This workflow deliberately runs QA before final review so QA's test additions are included. Wait for an interrupted writer to stop before assigning another. Tests with writable caches/data also need coordinated ownership; worktrees alone do not isolate databases, uploads, or ports.

## Pauses and manual continuation

After each phase, keep a concise checkpoint in the conversation: issue URL, selected approach, base and working directory, stage, relevant file changes, checks completed/missing, findings, repair count, outstanding user decision, and next action. This is a handoff, not another temporary planning file.

If blocked or approaching a known usage stop, quiesce active writers when possible and return the checkpoint. A hard interruption may prevent a final checkpoint, so record progress at phase boundaries rather than relying on a last-minute save. Do not claim to know reset time or remaining tokens unless the tool reports them.

On a user-requested continuation, re-read current GitHub and workspace state, check for changes since the checkpoint, and resume the appropriate incomplete phase. Revalidate tests/review invalidated by intervening edits. Do not repeat completed phases without a reason. In a new conversation without the checkpoint, reconstruct it from available evidence or ask for the missing handoff; do not invent prior approvals or results.

Temporary API helpers and payloads follow `manage-issues` cleanup rules and must be removed before handoff. Preserve implementation files and useful test failure evidence; they are not disposable helpers.

## Final handoff

Inspect the integrated result yourself. Report:

- Issue URL and outcome: complete for the requested scope, needs changes, or blocked.
- Behavior changed and important files, mapped to acceptance criteria.
- Exact verification commands and observed pass/fail/not-run results for the final code state.
- QA/reviewer findings, repair rounds used, and remaining risks or manual checks.
- What the user should review and the next authorized action.

Existing CI remains the release gate. Do not call the result deployable or fully verified with required checks missing or unexplained failures. A locally reviewed diff can still be handed to the user with explicit CI/browser gaps. Leave issue closure and final acceptance to the agreed user/PR workflow; do not select another issue automatically.
