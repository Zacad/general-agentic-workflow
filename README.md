# General agentic workflow

A small, Markdown-first workflow for coordinating LLM agents on real work.

The orchestrator coordinates. It does **not** read the work. It works through
four bounded commands — `status`, `ready`, `brief` (reads) and `result`
(record a receipt) — while explorers, workers and verifiers read sources and
artifacts in their own context and return a short receipt.

## Why it is shaped this way

- **Context economy.** One `item.md` per task, a compact brief for delegation,
  and a fixed-size receipt for results. The orchestrator's context depends on
  the number of active items, not the size of the backlog or the artifacts.
- **Durable state.** Markdown in the repository is the source of truth. A fresh
  session resumes from `status` plus one `brief`.
- **One approval.** Approval covers the item's scope and survives status,
  plan, progress and artifact changes. Only a real scope change re-prompts.
- **Bounded files.** One `item.md` per item, an optional `outputs/` directory,
  and an append-only progress log. No per-attempt directories, no copied
  projects, no databases.
- **Safe parallelism.** Dependencies and owned paths are explicit; `ready`
  reports overlaps before two agents collide.
- **Steer, don't legislate.** A short protocol, four roles and ten skills give
  the model direction without encoding every edge case.

## Quick start

Requires PHP 8.4+ and Composer.

```sh
cd cli
composer install
cd ..

# Scaffold the records (already done in this repository).
cli/bin/workflow init

# Frame work.
cli/bin/workflow new "First epic" --kind epic
cli/bin/workflow approve E-001 --by adam
cli/bin/workflow new "Parse YAML front matter" \
  --kind task --parent E-001 \
  --outcome "Front matter round-trips into a typed Item." \
  --owns cli/src/Domain/

# Fill the scope, then approve it once.
cli/bin/workflow set T-001 \
  'acceptance=Round-trips without loss; Unknown keys are preserved' \
  'scope_in=Parsing; Round-trip; Errors' \
  'scope_out=Schema migration'
cli/bin/workflow approve T-001 --by adam

# Daily coordination.
cli/bin/workflow status
cli/bin/workflow ready
cli/bin/workflow brief T-001
cli/bin/workflow result T-001 --status done --finding "Codec implemented; 14 tests pass." \
  --artifacts cli/src/Domain/FrontMatter.php --next verify
cli/bin/workflow board
```

Run commands from anywhere inside the project; the CLI finds the nearest
`.workflow/`. Use `--project` to point at another project.

## Records

```
.workflow/
  PRD.md                 product requirements
  Architecture.md        design and decisions
  board.html             generated read-only board
  items/<ID>/item.md     YAML state + Markdown body + progress log
  items/<ID>/outputs/    deliverables, only when there are real files
```

Item state:

```yaml
id: T-002
kind: task                # epic | task | subtask
parent: T-001
title: Parse YAML front matter
status: todo              # draft | todo | in-progress | done | cancelled
phase: explore            # explore | plan | implement | verify
priority: 2
depends_on: []
owns: [cli/src/Domain/]
artifacts: []
approval: {at: ..., by: adam, scope_hash: ...}
outcome: ...
scope: {in: [...], out: [...]}
acceptance: [...]
```

## The loop

1. **Frame** — outcome, scope, acceptance, dependencies, owned paths.
2. **Explore** — bounded questions: codebase, web/prior art, analysis, spikes.
3. **Plan** — write the plan; split only into independently acceptable parts.
4. **Implement** — the worker changes owned paths only.
5. **Verify** — an independent verifier checks the frozen artifacts.
6. **Close** — done or cancelled.

Phases and hierarchy are guidance, not a state machine. The CLI enforces only
what prevents corruption.

## Commands

| Command | Purpose |
| --- | --- |
| `init` | scaffold `.workflow/` |
| `new` | create a draft item |
| `status` | working set: active, ready, waiting, decisions |
| `ready` | startable work with dependency and overlap facts |
| `brief <id>` | bounded delegation packet |
| `plan <id>` | replace the `## Plan` section from text or a file |
| `decide <id>` | append a durable decision |
| `result <id>` | append a receipt |
| `deps [id]` | dependency relationships or a Mermaid graph |
| `set <id>` | update mutable fields |
| `approve <id>` | approve the current scope |
| `log <id>` | append a progress line |
| `close <id>` | mark done or cancelled |
| `validate` | structure, dependencies, approval, layout |
| `board` | write the static HTML board |
| `show <id>` | raw record (human/debug) |
| `install <dir>` | copy the kit into another project |

All reads support `--json`.

## Roles and skills

- Orchestrator — plans, frames, delegates, records, resumes.
- Explorer — answers one bounded question with primary evidence.
- Worker — delivers one approved outcome within owned paths.
- Verifier — checks the frozen artifacts independently.

Skills: `critical-reasoning`, `clear-communication`, `workflow-cli`,
`requirements`, `code-architecture`, `ui-ux-design`, `decompose-work`,
`verify-outcome`, `install-workflow`, `fresh-session-handoff`.

## Install into another project

See [docs/install.md](docs/install.md).

## Deliberate omissions

No database, ORM, server, daemon, scheduler or MCP in this version. No CAS,
manifest hashes, journal or recovery protocol. No per-attempt directories,
waves, groups or checkpoints. If a measured need for an index appears, it will
be derived and rebuildable.

See [docs/design-notes.md](docs/design-notes.md) for the review of the previous
system, the alternatives considered and the context-economy measurements.

## Development

```sh
cd cli
composer install
composer test
```
