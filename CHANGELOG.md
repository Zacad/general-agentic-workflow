# Changelog

User-facing changes to the workflow kit. Versions match the CLI
(`workflow --version`).

## 5.5.0

A recorded verifier `done` now surfaces the review-vs-close decision at the
exact moment it is made. No automatic status transitions were added.

- **`result` prints a decision reminder after a verifier receipt.**
  `workflow result ID --role verifier --status done` now ends the text-mode
  success path with `Verifier proof recorded — it does not close the item:
  workflow close ID, or hold for review with workflow set ID status=review and
  workflow log ID "what is pending".` The reminder fires only for that role and
  status: worker, explorer and orchestrator receipts, verifier
  `blocked`/`needs-decision`/`failed` and all error paths stay silent, and
  `--json` stdout is unchanged (a new test pins the receipt object shape).
  `review` remains an opt-in status — statuses still guide work rather than
  form a state machine.
- **Guidance names the decision point.** `work-task` step 5 is now "Review or
  close": record the verifier receipt, then `workflow set <id> status=review`
  plus a `workflow log` note when further review is pending, or `workflow close`
  when ready; review feedback returns the item to `in-progress` for rework and
  re-verification, and the Output covers a `review` hold. `orchestrator.md` now
  states that a verifier `done` is proof, not closure. `AGENTS.md` is
  unchanged: its `review` vocabulary already carries the invariant.
- **Installed projects** refresh `.agents/` and the kit rules with
  `workflow install <target> --force`.

## 5.4.1

Fixes `workflow migrate` for items that have subtasks, and makes the kit test
suite pass from a clean checkout on macOS.

- **`migrate` handles tasks with subtasks.** The flat-to-hierarchical migration
  moved subtasks before their parent task, so any task that had subtasks failed
  with "Cannot move item" (`Directory not empty`). Moves now run parent-first by
  destination depth, every successful move is journaled (including the epic
  merge phase) and undone in reverse order on failure, and a move onto an
  existing destination is refused instead of replacing it. If undoing a failed
  migration itself fails, both trees are kept and the recovery failure is
  reported. Item files and `outputs/` are preserved byte for byte; the
  `--dry-run` plan is unchanged.
- **Tests pass on macOS from a clean checkout.** The shared test root is now a
  canonical path, so macOS's `/var` and `/tmp` symlinks no longer trip the
  initializer's symlink check, and the initializer's safety tests assert the
  exact unsafe path component. The documentation test no longer reads gitignored
  `.workflow/` records; it checks tracked documents and a synthetic tracked
  fixture (`cli/tests/fixtures/architecture-source-references.md`) and still
  fails on a missing document or a broken link. No production behaviour other
  than `migrate` changed. Not covered: Linux/CI runs.
- **Known gap.** `migrate` does not rewrite `owns` and `artifacts` paths in item
  records that point at the old flat locations (`.workflow/items/<ID>/outputs/…`);
  `validate` reports them as missing artifacts until they are updated by hand.

## 5.4.0

The kit operating contract slimmed to cross-role invariants, with role
procedure moved to its canonical home.

- **Slimmed kit operating contract.** The marker-delimited block in `AGENTS.md`
  keeps only cross-role invariants (records, vocabulary, approval and design
  gates, the receipt contract, verification and follow-up policy) and moves
  role-specific procedure to its single canonical home: dispatch mechanics and
  the role→packet table to `.agents/roles/orchestrator.md`, decomposition and
  sizing detail to the `decompose-work` skill, lifecycle mechanics to
  `work-task`, and the migration how-to plus complete change-classification
  lists to the `workflow-cli` skill. `AGENTS.md` now also states the convention
  for harnesses without registered workflow agents: the coordinator reads
  `.agents/roles/orchestrator.md`, and that role file requires every delegation
  to name the delegate's role file and read it in full. Refresh existing
  installs with `workflow install <target> --force`.

## 5.3.0

Solution design moves from the orchestrator to a dedicated **design worker**,
and existing installs can now refresh the operating contract cleanly.

- **Design worker authors task solution designs.** The orchestrator plans,
  records and presents; a separate worker instance authors each task/subtask
  `## Design`, choosing the method skill by task type (default
  code-architecture route 2). The design worker reads the full item
  (`workflow show <id>`) plus its `outputs/`, not the gated worker brief.
- **Designs are verified before the user sees them.** An independent verifier
  checks the authored design; only then does the orchestrator present it for the
  user's approval. Implementation dispatch stays gated on that approval.
- **Separate, independent instances.** Design worker, design verifier and
  implementation worker are distinct runs; the instance that authored a design
  never implements it.
- **Design workers can escalate.** Insufficient evidence or an over-broad task
  returns `needs-decision` (requesting bounded exploration or proposing a split);
  only the orchestrator frames items or changes scope.
- **Marker-delimited `AGENTS.md`.** Root `AGENTS.md` now wraps the kit-owned
  rules in `<!-- BEGIN/END KIT WORKFLOW RULES -->` markers. `install --force`
  refreshes only that block and preserves project rules written outside it; a
  marker-free host file is never rewritten. Existing installs therefore pick up
  these rule changes without a hand merge, while keeping host authority.

## 5.2.1

- Hierarchical item layout fixes for `validate` and `migrate`.

## 5.2.0

- `review` status, material/non-material change classification, and flat-to-
  hierarchical structure migration.
