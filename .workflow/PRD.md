# General Agentic Workflow — product requirements

## Purpose

A lightweight, installable workflow framework that lets one developer
coordinate LLM agents to plan, execute and verify work on any project. Durable
Markdown records hold the work; a small PHP/Symfony CLI keeps those records
consistent and gives an orchestrator compact answers instead of whole files.

The framework exists to solve one problem well: **an orchestrator should spend
its context on coordination, not on reading task content or worker output.**

## Problem

Long-running agent work fails in three predictable ways:

1. The orchestrator reads whole task records and worker reports, its context
   fills, and it loses the thread.
2. Work is not resumable: after a session ends, nobody knows what was approved,
   what was done, and what comes next.
3. Parallel agents collide because ownership and dependencies are implicit.

The previous system solved some of this by adding guarded writes, revision
hashes, per-attempt directories and a large protocol. That produced a second
problem: excessive ceremony, repeated approvals, and unbounded file growth.

## Users and scenarios

Users: one developer supervising one or more coding agents in a repository.

Scenarios:

- Start a new endeavor, record its requirements and architecture, and
  decompose it into epics and tasks.
- Approve an item once; then explore, plan, implement and verify it without
  re-approving routine steps.
- Run independent tasks in parallel without writing over each other.
- Resume in a fresh session from the records alone.
- Hand a bounded question to an explorer and receive a short receipt.
- Hand a frozen artifact to a verifier and receive a verdict against criteria.
- Install the workflow into another project.
- View the board as a human.

## Goals

Ranked. Each has an observable check.

1. **Understandable core.** The whole process fits in a short `AGENTS.md`, a
   few roles and a few skills. Check: word budget; a reader can explain the
   loop without opening the CLI source.
2. **One truth.** Markdown files are the only source of truth. The CLI reads
   and writes them directly. Check: no state database; deleting any derived
   file loses nothing.
3. **Low ceremony.** One durable approval per item. Routine status, plan,
   progress and artifact changes never re-prompt. Check: approval prompts per
   completed item.
4. **Bounded artifacts.** Each item is one `item.md` plus, only when needed, an
   `outputs/` directory. No per-invocation directories and no staged project
   copies. Check: `validate` rejects unexpected entries; files per item stay
   bounded.
5. **Steer, don't legislate.** Principles and examples carry the process; the
   model handles the long tail. Check: no exhaustive edge-case rule lists.
6. **Context economy.** The orchestrator answers "what is happening", "what can
   start" and "what came back" from bounded projections, not full files. Check:
   output budgets and a measured 50-item scenario.
7. **Dependency awareness.** Hierarchy and dependencies are explicit; cycles
   are impossible; ready work is computed. Check: `validate` and `ready`.
8. **Safe parallelism.** Each item can declare owned paths; overlapping work is
   reported. Check: `ready` flags overlaps.
9. **Installable.** Copy the kit, install dependencies, initialize a project.
   Check: a fresh project passes `init` and `validate`.
10. **Durable.** Writes are atomic and serialized by one lock. Check: a
    concurrent-write test.

## Non-goals

- Daemons, schedulers, long-running servers, global databases.
- Authentication, multi-user sync, remote collaboration.
- Enforcing a rigid process or every edge case.
- Migrating records from the previous system.
- Full-text search, comments, attachments, time tracking, auto-commit.
- A web UI beyond a generated read-only board.

## Functional requirements

### Records

- `FR1` A project has `.workflow/PRD.md`, `.workflow/Architecture.md`,
  `.workflow/items/<ID>/item.md`, and a generated `.workflow/board.html`.
- `FR2` An item is `epic`, `task` or `subtask`. An epic has no parent; a task
  has an epic parent or an explicit standalone reason; a subtask has a task
  parent.
- `FR3` Item state lives in YAML front matter; narrative lives in Markdown
  sections.
- `FR4` Item state includes: id, kind, parent, title, status, phase, priority,
  dependencies, owned paths, artifacts, approval, timestamps, outcome, scope
  and acceptance criteria.
- `FR5` Status is `draft`, `todo`, `in-progress`, `done` or `cancelled`.
- `FR6` Phase is `explore`, `plan`, `implement` or `verify`.
- `FR7` Progress is an append-only `## Progress` section. Receipts are
  appended there in a fixed compact format.

### Approval

- `FR8` Approval covers only scope-defining fields: kind, parent, title,
  outcome, scope and acceptance criteria, plus dependencies.
- `FR9` Approval persists across status, phase, priority, ownership, artifact,
  plan and progress changes.
- `FR10` Editing a scope-defining field marks the approval stale; `validate`
  reports it and `ready` withholds the item until re-approval.
- `FR11` `approve` records who approved and when, and moves a `draft` item to
  `todo`.

### CLI

- `FR12` `init` scaffolds `.workflow/` with PRD and architecture templates.
- `FR13` `new` creates a draft item and allocates an ID.
- `FR14` `status` returns the working set only: active, ready, waiting,
  decisions and counts, independent of backlog size.
- `FR15` `ready` returns startable items with dependency and ownership
  information.
- `FR16` `brief` returns a bounded delegation packet: identity, approval,
  dependencies, owned paths, outcome, scope, acceptance, plan and artifacts.
- `FR17` `result` appends a fixed-format receipt and updates the item's last
  result and artifact list.
- `FR18` `deps` returns dependency and dependent relationships, and can render
  a Mermaid graph.
- `FR19` `set` updates mutable fields; `plan` replaces the plan section;
  `decide` appends a durable decision; `log` appends a progress line; `close`
  sets a terminal status.
- `FR20` `validate` checks structure, IDs, parent rules, dependency cycles,
  statuses, approval freshness, owned-path overlap, missing artifacts and
  unexpected files.
- `FR21` `board` writes a static, escaped, read-only HTML board.
- `FR22` `install` copies the kit to a target project and initializes it.
- `FR23` Reads support `--json`. JSON is stable enough for scripts.

### Context discipline

- `FR24` No default command prints a whole `item.md`.
- `FR25` The orchestrator reads only `status`, `ready`, `brief` and `result`
  during normal coordination. Full artifacts belong to explorers, workers and
  verifiers.
- `FR26` Receipts are fixed-size: status, one- or two-sentence finding,
  artifact paths, decision needed, next action.

### Distribution

- `FR27` The kit is `AGENTS.md`, `.agents/`, `cli/`, `docs/` and templates.
- `FR28` The CLI requires PHP 8.4+ and `composer install`; `vendor/` is not
  committed.

## Acceptance criteria

1. A fresh project runs `init`, creates items, approves them, and passes
   `validate` with no errors.
2. Editing progress or status does not invalidate approval; editing outcome or
   acceptance criteria does.
3. `ready` excludes items with unmet dependencies or stale approval and flags
   overlapping owned paths.
4. `status` output stays within its budget for a 50-item project and shows only
   the working set.
5. `brief` contains enough to delegate an item without opening `item.md`.
6. `validate` detects a dependency cycle, a bad parent, a stale approval and an
   unexpected file in an item directory.
7. `result` appends one compact receipt and no new directories or files.
8. `board` produces a self-contained HTML file that opens without network.
9. The PHPUnit suite passes.
10. The whole agent-facing kit can be read in one sitting.

## Decisions

- Build fresh in this repository; the previous system is reference only.
- Markdown is truth; no SQLite, no ORM, no Dolt.
- One approval per item, over a stable scope hash.
- Four roles: orchestrator (also plans), explorer, worker, verifier.
- Ten skills: critical reasoning, clear communication, workflow CLI,
  requirements, code architecture, UI/UX design, decomposition, verification,
  installation, fresh-session handoff.
- No implementation skill; workers rely on judgment plus architecture,
  reasoning and communication skills.
- References that informed the interface (not the storage): Beads' ready-work
  graph, Backlog.md's Markdown-native tasks and review checkpoints, Anthropic's
  context-engineering guidance on just-in-time retrieval, subagents and
  structured notes.
