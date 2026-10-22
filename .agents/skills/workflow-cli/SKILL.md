---
name: workflow-cli
description: Use the project-local workflow CLI for compact reads, approvals, plans, decisions and receipts.
---

# Workflow CLI

**Inputs:** project root, `cli/bin/workflow` (or `workflow` on `PATH`), intended
read/write. Load root `AGENTS.md` for authority, role boundaries and lifecycle
policy; this skill is the command contract, not a second operating loop.

Commands find the nearest `.workflow/`; use `--project /absolute/path` to select
another root. Start a session with `status`.

## Read projections

| Command | Contract |
| --- | --- |
| `status [--all]` | Working set/counts; default text sections are capped. `--all` removes every section cap and adds done/cancelled groups in text and JSON. The `decisions` section lists items *pending* a decision (last receipt `needs-decision`), not recorded decisions. |
| `ready [--all]` | Todo items with current approval and done dependencies; reports ownership overlaps. Default text shows 20. |
| `brief ID --for ROLE` | Role packet: `orchestrator`, `explorer`, `worker`, `verifier`; always specify the role. When the item has a parent whose Design section is empty, the packet warns `parent design: (not written yet)` and JSON adds `parent_design_present: false`. Compact `last_result` includes a truncated `decision` for non-verifier roles. |
| `review E-001` | Exact epic/descendant scope, owns, approval, Decisions and full Plan/Design for review; the epic packet adds a bounded PRD excerpt (Goals/Non-goals) labeled with document state. |
| `deps [ID] [--graph]` | Dependency/dependent relationships or Mermaid. |
| `validate` | Structure, approval, artifacts, layout and advisory process diagnostics (explorer receipts predating approval, approved children with an unrecorded parent Design, template PRD/Architecture with epics present, producer artifacts outside owns). |
| `show ID [--section Name]` | Raw human/debug record, not a coordinator read view. |

These commands accept `--json`. JSON preserves literal strings. Review/approve
text uses unambiguous literal values for lists, empty values, multiline strings
and Console-style tags; approval values/hash are captured in one locked mutation.
Default status/ready JSON is already uncapped for its included groups.

Briefs include scope, acceptance, Plan/Design, parent Design, decisions and
upstream artifacts. Explorer adds `.workflow/items/<ID>/outputs/`; worker and
other non-verifier roles may see a compact last receipt. Verifier text **and**
JSON exclude receipt, progress and unknown metadata. This is field-based blinding,
not removal of conclusions someone placed in approved narrative. Default role is
worker: plain `brief ID` is unsafe for independent verification.

## Mutations and derived output

| Command | Contract |
| --- | --- |
| `init [--name NAME]` | Add missing project templates/layout; prints the fill moments (PRD before the first epic's approval, Architecture at epic design). |
| `new TITLE [--kind task] [--parent E-001]` | Draft item in phase `plan` by default; repeat `--owns PATH` / `--depends ID`; parentless tasks use `--standalone-reason TEXT`. |
| `plan ID --file F` or `--text S` | Replace Plan; optional `--phase PHASE` and/or `--start`. |
| `design ID --file F` or `--text S` | Replace Design; same optional state flags. |
| `set ID key=value ...` | status, phase, parent, title, outcome, priority, depends_on, owns, acceptance, scope_in, scope_out, standalone_reason. |
| `approve ID [--by NAME] [--confirmation TEXT]` | Record current scope approval; draft becomes todo; print exact scope and hash plus an attestation line. Pass `--by` with the user's name only when the user explicitly confirmed; `--confirmation` stores the user's literal confirming words in the same mutation. `validate` warns when an approval record lacks a confirmation quote. |
| `decide ID TEXT` / `log ID TEXT` | Append decision / progress note. Use `decide` whenever the user resolves a scope, acceptance or design question — the `## Decisions` section ships in every brief, including the verifier's, and in the epic review packet. After recording, reconcile PRD/Architecture if project-level facts changed. |
| `result ID --status STATUS --finding TEXT` | Receipt; optional `--role ROLE` (default worker), repeatable/comma-separated `--artifacts PATH`, `--decision TEXT`, `--next TEXT`. |
| `close ID [--status done\|cancelled] [--reason TEXT] [--force]` | Done requires current verifier proof; force records an unverified exception, not success. |
| `board` | Regenerate derived `.workflow/board.html`, not a read-only command. |
| `install DIR [--name NAME] [--force]` | Scaffold; preservation/manual-merge contract in `docs/install.md`. |

A new item starts in `plan`; approving a draft moves it to `todo`. Plan/design
require exactly one content source. Without flags they change only content,
never rewind state. `--phase` explicitly selects any valid phase but
does not imply status; only `--start` requests in-progress. Content and requested
state changes validate before one atomic mutation, then refresh the board.
Use `set` for other state corrections. Example:

```sh
workflow design T-001 --file /path/to/design.md --phase implement --start
```

List assignments appear once per `set` call: separate scope/acceptance entries
with `;`, owns/dependencies with `,`. Duplicate list keys are rejected. Ownership
comparison is lexical on literal project-relative paths, including missing paths;
it does not expand globs or resolve links.

Receipt statuses are `done`, `blocked`, `needs-decision`, `failed`. A verifier
success must use `--role verifier --status done`; record it and `close` in two
calls. Tracked target changes invalidate proof independently of scope approval.
Done-history refusals require an explicitly created/reviewed follow-up; see
AGENTS for the freeze convention and deliberate exception policy.

Item writes use a project lock and atomic replacement. Do not hand-edit item
records in normal operation; use `###` inside narrative sections. Run `validate`
after structural changes/merges. Exit codes: `0` success, `1` validation failure,
`2` invalid input. Warnings are not new phase/approval gates.
