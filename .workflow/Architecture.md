# General Agentic Workflow — architecture

## Overview

A microkernel with one storage rule: **Markdown is truth.** The kit is a short
protocol, four roles and ten skills. A project-local PHP/Symfony CLI reads and
writes the records, computes readiness, validates integrity and renders a
static board. There is no database, daemon or server.

```
AGENTS.md ─────────────── principles and the loop
.agents/roles/ ────────── who does what
.agents/skills/ ───────── how to do a kind of work
cli/ ──────────────────── projections, writes, validation
.workflow/ ────────────── durable records
```

## Components

### Kit

- `AGENTS.md`: hierarchy, phases, delegation, receipt contract, approval rule,
  context discipline. Deliberately short.
- `.agents/roles/`: `orchestrator`, `explorer`, `worker`, `verifier`.
- `.agents/skills/`: ten methods, each a single `SKILL.md`.
- `docs/install.md`: installation and update.

### CLI

A Symfony Console application. No DI container, no bundles, no HTTP kernel.
Components: `console`, `yaml`, `filesystem`, `finder`, `lock`.

```
cli/
  bin/workflow
  composer.json
  src/
    Console/           Application + one class per command
    Domain/            Item, enums, Approval, FrontMatter, exceptions
    Store/             ItemStore, Project, atomic writes, lock
    Validate/          ItemValidator
    Board/             BoardRenderer
    Receipt/           Receipt formatting
  templates/           PRD.md, Architecture.md
  tests/
```

### Records

```
.workflow/
  PRD.md
  Architecture.md
  board.html              generated, ignored by git
  .locks/                 transient lock files, ignored by git
  items/<ID>/
    item.md
    outputs/              only when a real deliverable exists
```

## Item model

Front matter (state):

```yaml
id: T-012
kind: task                # epic | task | subtask
parent: E-001
title: Parse front matter
status: todo              # draft | todo | in-progress | done | cancelled
phase: explore            # explore | plan | implement | verify
priority: 2
depends_on: [T-010]
owns: [cli/src/Store/]
artifacts: []
approval:
  at: 2026-10-19T10:00:00+00:00
  by: adam
  scope_hash: <sha256>
last_result: null         # {at, role, status, finding}
verified_at: null
created: 2026-10-19T09:00:00+00:00
updated: 2026-10-19T10:00:00+00:00
outcome: "Front matter parses into a typed Item."
scope:
  in: [parsing, round-trip, errors]
  out: [schema migration]
acceptance:
  - "Round-trips YAML without loss"
  - "Unknown keys are preserved"
```

Body (narrative):

```markdown
## Plan
...
## Decisions
...
## Progress
- 2026-10-19T14:30:00+00:00 · worker · done
  Finding: ...
  Artifacts: cli/src/Store/FrontMatter.php
  Decision: none
  Next: verify
```

Artifacts are listed in front matter; the progress log keeps the receipt trail.

The YAML codec is `symfony/yaml`. Front matter must start with `---` and close
with `---`; unknown keys are preserved on read/write to stay forgiving toward
human edits.

## Approval

The scope hash is SHA-256 over canonical JSON of exactly:

`kind, parent, title, outcome, scope.in, scope.out, acceptance, depends_on`

`depends_on` and arrays are normalized before hashing; acceptance order is
preserved because criteria are numbered.

- `approve` stores `{at, by, scope_hash}`.
- Any change to a scope field makes the stored hash differ; the CLI reports
  "stale approval" in `validate` and withholds the item from `ready`.
- Status, phase, priority, owns, artifacts, plan, decisions, progress and
  receipts never affect the hash.

This is the mechanism that fixes repeated approval prompts: the approval is
stable until the *scope* changes, not until any byte changes.

## CLI surface

Reads (bounded projections):

| Command | Output | Budget |
| --- | --- | --- |
| `status` | working set + counts | ~15–25 lines (sections capped) |
| `ready` | startable items, dependencies, overlaps | 1 line/item, first 20 |
| `brief <id>` | delegation packet | ~20 lines |
| `deps [id]` | relationships, Mermaid | 1 line/item |
| `validate` | diagnostics | errors/warnings only |
| `board` | path to generated HTML | 1 line |
| `show <id>` | raw record (human/debug only) | whole item |

Writes: `init, new, plan, decide, set, approve, log, result, close, install`.

Exit codes: `0` success, `1` validation failure, `2` invalid input. No special
recovery codes.

`--json` is available on reads. Human output is the default.

### Read discipline

- L0 `status`: every session.
- L1 `ready`: when choosing work.
- L2 `brief`: when delegating; the only task content the orchestrator reads.
  Prefer delegating by reference (worker runs `brief` itself) so even this
  stays out of the orchestrator's context.
- L3 `show --section` or one artifact: exceptional; prefer a new bounded
  question to an explorer.
- L4 full report/diff/log: never in the orchestrator; belongs to the verifier
  or a fresh explorer.

### Receipt

`result` appends a fixed block and updates `last_result`/`artifacts`:

```
- <iso timestamp> · <role> · <done|blocked|needs-decision|failed>
  Finding: <one or two sentences>
  Artifacts: <comma-separated paths>
  Decision: <question or none>
  Next: <one action>
```

A verifier `done` result sets `verified_at`. `close done` warns when it is
absent; it does not block (guidance, not enforcement).

## Readiness and dependencies

`ready(id)` holds when:

1. status is `todo`;
2. approval exists and its scope hash is current;
3. every item in `depends_on` has status `done`;
4. the item loaded successfully (an unreadable item makes reads fail; run
   `validate`).

Owned-path overlap is computed separately. `ready` reports an item whose `owns`
paths overlap an `in-progress` item with an `overlap:` marker; it does not
silently drop it, because some work legitimately reads the same paths.

`validate` detects dependency cycles with a depth-first traversal and rejects
self-dependencies and missing targets.

## Concurrency and durability

- One Symfony `FlockStore` lock in `.workflow/.locks/` surrounds every write.
- A write serializes the whole item, writes a temp file in the same directory,
  `fsync`s it, then `rename`s it over `item.md`.
- Reads are lock-free; atomic rename means a reader sees the old or new file,
  never a partial one.
- Only the orchestrator mutates records. Workers write artifacts to their owned
  paths and return receipts. This gives one writer per record without a journal.

## Distribution

- `install <target>` copies `AGENTS.md`, `.agents/`, `docs/` and the `cli/`
  source tree (excluding `vendor/`, `tools/`, caches and `.workflow/`) into the
  target, then initializes `.workflow/`.
- The target runs `composer install` in `cli/`.
- The kit never copies the source project's `.workflow/` records.

## Testing

PHPUnit covers the codec, scope hash stability, approval invalidation, store
atomicity, dependency validation, readiness and the command surface. CLI tests
run against a temporary project root.

## Deliberate omissions

No database or ORM; no Dolt; no server, daemon or scheduler; no MCP in the
first version (the same projections can be wrapped later); no CAS, manifest
hashes, journal or recovery protocol; no per-attempt directories; no waves,
groups, checkpoints or coordinating briefs; no auto-commit.

## Decisions

- Storage: Markdown only. Revisit only if a measured need for an index appears;
  then it is derived and rebuildable.
- IDs: `E-NNN`, `T-NNN`, `S-NNN`, allocated by scanning existing items.
- Approval: scope hash, per item.
- Roles: orchestrator (plans), explorer, worker, verifier.
- Board: static read-only HTML.
- Development: conventional first; dogfood once stable.

See [docs/design-notes.md](../docs/design-notes.md) for the review that
produced these decisions, the alternatives considered, and the measured context
economy.
