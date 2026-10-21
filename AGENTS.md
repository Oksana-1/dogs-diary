# Repository instructions

## Shared boundaries

- Stay within the requested scope and preserve unrelated changes. Inspect current code before trusting planning documents.
- Before implementing each new GitHub issue, create a dedicated branch (for example, `issue/10-mobile-layouts`), including for individual sub-issues. Reuse that issue's branch for continuation and repairs; do not combine different issues on one implementation branch. Confirm the appropriate base before branching, preserve existing changes, and do not silently carry another unfinished issue's work into the new branch. Backlog creation alone does not require a branch.
- Follow the existing Symfony application services, owner-scoped repositories, API views, and Vue islands.
- Use `docs/README.md` and `docs/adr/tooling/devops/ci.md` for configured checks and isolated test environments. Keep secrets out of reports and do not run destructive database commands against development or production data.
- Do not weaken tests or quality gates to obtain a pass. Report exact commands and observed results, distinguishing passed, failed, and not run.
- Commit, push, publish, merge, deploy, contact others, or access production data only when authorized for that action.

## Collaboration and technical decisions

- The user owns the final code and the technical approach. Before implementing an approach that the user has not already selected, present the proposed solution, meaningful alternatives, tradeoffs, and a recommendation, then wait for the user's choice. Make the proposal concrete through focused inspection first. An implementation request alone does not select an approach when materially different solutions remain.
- Proactively review the user's technical decisions and suggest improvements when useful, including without an explicit review request. Explain concerns with concrete evidence and tradeoffs, distinguish correctness issues from preferences, and recommend alternatives. The user retains the final decision; do not silently replace the chosen approach or repeatedly reopen a settled choice without new evidence.
- Use [shape-task](.agents/skills/shape-task/SKILL.md) for unclear requirements, new features, and technical approach decisions. Use [debug-together](.agents/skills/debug-together/SKILL.md) for troubleshooting application, environment, deployment, or integration failures.
- Carry forward the user's answers and approved decisions. Implement routine details within the chosen approach without repeated approval; naming and formatting alone do not require a decision round. Bring changes to architecture, dependencies, API contracts, persistence, or verification strategy back to the user when they introduce a new choice.
- Ask about consequential unknowns early, in rounds of 1–3 short questions. Explain why the answer matters and recommend an option for decisions. Check readily available facts yourself; ask about inaccessible external settings and manual actions rather than guessing. Missing local configuration does not establish the state of a hosted environment. Never request secret values in chat.
- Wait for answers before taking dependent actions. Independent, relevant inspection may continue while a question is pending. Silence or elapsed time is not an answer.
- Before each diagnostic check, identify the hypothesis and how its possible results change the next step. After two unsuccessful attempts to test the same hypothesis without new evidence, stop that line of investigation and discuss the blocker. If 10–15 minutes pass without meaningful progress, return the established facts, remaining unknowns, and the next question or proposed check instead of continuing indefinitely.
- Give concise updates when evidence, direction, or blockers change. If progress depends on the user, unavailable access, or a usage reset, return a clear handoff rather than sleeping or polling for hours. Do not claim exact token accounting or a hard runtime limit unless the tools provide it.

## Optional agent workflow

When the user asks to use the Dogs Diary agents, the main conversation coordinates the roles in `.codex/agents/`. Each role's detailed instructions live in its TOML file.

| Agent | Responsibility | File edits |
| --- | --- | --- |
| `planner` | Inspect behavior and define acceptance criteria and implementation steps | None |
| `implementer` | Implement the assigned change and relevant tests | Assigned implementation, tests, and docs |
| `qa` | Independently verify behavior and add missing tests | Assigned tests and fixtures |
| `reviewer` | Review the diff and report actionable findings | None |

1. Record the original request, base revision, existing changes, and scope. Have planner define observable acceptance criteria and resolve material product questions.
2. Give implementer the criteria, relevant context, assigned files, dependencies, and configured checks. Keep small Symfony/API/Vue features together.
3. After implementation stops, have QA verify the original criteria and reviewer inspect a stable diff. Review any subsequent QA test additions before completion.
4. Route justified findings to implementer. Allow at most two automatic repair rounds by default, then report remaining blockers and evidence.
5. Rerun affected checks after changes. Use existing CI checks as the release gate; results apply only to the code state tested. Do not declare readiness with missing required checks or unexplained failures.
6. Return completed scope, changed files, criteria addressed, exact verification results, unresolved findings, and required decisions. Another agent's completion message is not independent evidence.

Use one writer at a time in a shared workspace. Concurrent writers require separate worktrees, clear file ownership, agreed API contracts, and isolated databases/uploads/ports. Verify the integrated result; worktrees alone do not isolate Docker volumes or databases.

Handoffs include the original request, criteria, base revision, existing changes, assigned files, dependencies, test environment, and authorized actions. Responses state complete, needs changes, or blocked, with supporting evidence and verification gaps.

To invoke the workflow, ask: "Use the Dogs Diary agents for [task]." If named roles are unavailable in the client, pass their TOML instructions to generic subagents and disclose the fallback. This does not apply TOML sandbox settings; role editing boundaries remain instructions unless enforced by the runtime. These definitions do not implement an automatic runner.

## Frontend unit tests

Before creating or modifying frontend tests, test helpers, or frontend testing configuration, read `tests/JavaScript/TESTING.md` completely and follow it as the canonical project testing guide.

- Test observable behavior; do not call component or composable internals through `wrapper.vm`.
- Keep production changes driven by product behavior, not by test-only access requirements.
- Use only test commands that are configured in the repository. If the frontend test toolchain changes, update `tests/JavaScript/TESTING.md` and the package scripts in the same change.
- Run the relevant frontend tests after every frontend test or implementation change and report the exact command and result.
