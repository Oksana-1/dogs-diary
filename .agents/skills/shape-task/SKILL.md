---
name: shape-task
description: Clarify feature requirements and agree on a technical approach with the user before implementation in Dogs Diary. Use for new features, ambiguous changes, or consequential technical choices; skip interview rounds for routine edits whose behavior and approach are already specified.
---

# Shape a task together

Reach enough shared understanding to implement the next useful increment. The user is responsible for the final code and chooses the technical approach; the agent supplies evidence, options, and a recommendation.

Proactively review the user's proposed technical decisions and offer improvements when useful, even without an explicit review request. Explain concerns with concrete evidence and tradeoffs, distinguish correctness issues from preferences, and recommend alternatives. The user retains the final decision. Do not silently substitute an approach or repeatedly reopen a settled choice without new evidence.

## Establish what needs a decision

Read the request, prior answers, applicable repository instructions, and only the code needed to understand the relevant behavior. Preserve existing changes. Distinguish observed facts, user decisions, and unverified assumptions. Do not survey the whole repository for a small change.

Use `docs/README.md` to locate only the ADRs relevant to the task. Treat applicable accepted decisions as the starting point for the approach. If implementation and an ADR disagree, surface the discrepancy instead of silently choosing either as authoritative.

Identify missing information that could change the outcome or approach:

- The observable result and how the user will judge it complete.
- Scope, exclusions, compatibility, and important edge cases.
- Technical choices such as service boundaries, API shape, state ownership, persistence, dependencies, and verification strategy.
- External prerequisites or manual setup that cannot be verified through available, authorized access.

Investigate readily available facts yourself. Ask the user about inaccessible settings and product intent. Do not send the user to inspect code you can read, or infer hosted configuration from local files. Ask only for nonsecret confirmation of configuration.

Express acceptance criteria as observable scenarios: the relevant starting condition, action, and expected result. Include important failure paths or access boundaries when they affect the task, without requiring an exhaustive checklist. For example, if a treatment is saved but its photo upload fails, retrying in the same form must not create a duplicate treatment.

## Work in short decision rounds

Ask 1–3 concise questions whose prerequisites are already understood. Explain what each answer changes. For a technical decision, describe the concrete proposed approach, meaningful alternatives if they exist, and the reason for your recommendation. A short paragraph is enough for a simple choice; do not manufacture alternatives.

Let the user choose the approach before implementing dependent changes. A general request to implement a feature does not choose among materially different technical solutions. While answers are pending, continue only useful independent inspection. Silence does not authorize a default.

Reuse previous answers and explicit approach choices. Routine implementation details within the chosen approach do not require another approval. If new evidence requires a different approach, explain the evidence and obtain the user's choice before switching.

Apply partial approval item by item. If the user accepts one proposal and rejects or defers another, proceed only with the approved scope; do not include the other proposal as incidental refactoring or documentation. Leave unanswered choices unresolved rather than treating them as approved.

Stop asking when the next increment has clear behavior, scope, observable acceptance criteria, and an agreed approach. Do not exhaust every hypothetical future branch. For larger tasks, agree on a useful first increment and revisit later decisions when they become relevant.

## Hand off to implementation

Briefly record the chosen approach, acceptance criteria, relevant checks, and any remaining independent unknowns in the conversation. Once the user has chosen the approach and requested implementation, proceed without asking for the same approval again. If the request was only to explore or plan, deliver the agreed proposal without editing application code.

If investigation stops producing evidence, return what is known and the smallest question needed to proceed instead of expanding the investigation indefinitely. Do not delegate unless the user has requested agents or another applicable instruction explicitly authorizes delegation.
