# Changelog

User-facing changes to the workflow kit. Versions match the CLI
(`workflow --version`).

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
