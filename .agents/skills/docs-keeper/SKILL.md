---
name: docs-keeper
description: Review, reorganize, or update Dogs Diary documentation against current code, organized by modules and architectural layers. Use only when explicitly requested.
---

# Keep documentation current

Work only on request, within the requested module, change, or documentation scope. Do not run as a routine step for every code edit. Write documentation in concise English.

## Agree before writing

Read the relevant documentation and implementation. For change-based requests, establish the intended diff or revision range; do not assume all working-tree changes belong to the task.

Present the concrete discrepancies and proposed edits. For reorganization, show the proposed file structure and moves. Ask in rounds of 1–3 questions when choices remain, then wait before dependent edits. Do not ask again for a scope or approach already approved in the conversation.

Use current code and configuration to verify implementation claims. Distinguish implemented behavior, proposed plans, historical decisions, and unverified hosted configuration. Ask about inaccessible facts without requesting secrets.

## Organize for reading

- Keep `docs/README.md` as the only file at the root of `docs`, with a concise project overview and links to every other documentation file, including nested indexes. Put other documents under `docs/adr/`. Do not depend on legacy README files slated for removal.
- Under `docs/adr/`, prefer `architecture`, `domain`, `testing`, and `tooling` as top-level categories, with specialization subfolders where useful. Follow the agreed structure flexibly. Preserve guides and plans as such when relocating them.
- Group domain records by business concept (`dogs`, `treatments`, `media`); keep shared rules at the domain root. Use technical specializations such as frontend/backend for implementation decisions.
- Use descriptive kebab-case filenames without numeric prefixes, and unnumbered document titles. Preserve `README.md` for indexes.
- Group system documentation by actual modules, describing relevant layer responsibilities and interactions within each module. Follow the code's boundaries rather than imposing a new architecture.
- Keep shared architecture information in one overview; link to it from modules.
- Keep setup, operations, deployment, and testing instructions separate from module descriptions. Preserve their essential commands, warnings, and environment distinctions.
- Keep one canonical explanation per topic. Replace duplication with links.
- Link to ADRs for decision rationale. Keep current behavior in module documentation; preserve accepted ADRs as history. If a decision changed, propose a successor record rather than silently rewriting it. Include ADR edits only when within the agreed scope.

Use existing documentation as source material, checking it against code before carrying claims forward. Describe each module's purpose, important behavior or contracts, and relevant layers or dependencies without listing every class or endpoint. Omit empty sections and generic framework explanations.

Keep new documents self-contained. Link to code and other ADRs rather than legacy documentation slated for removal.

## Update and verify

Make the agreed edits and update affected inbound links when moving files. Preserve useful information and flag conflicting claims instead of guessing which is correct. Documentation work does not authorize application, dependency, or runtime changes.

Check changed relative links and anchors, referenced paths, and consistency with the inspected implementation. Use configured documentation checks when available. Do not execute operational commands merely because they appear in documentation; report which commands were verified and which were not run.

Return a short list of updated documents, validation results, and remaining unknowns. If no update is needed, say so without manufacturing edits.
