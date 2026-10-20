# General agentic workflow

A small, Markdown-first workflow for coordinating LLM agents on real work.

The orchestrator coordinates. It does **not** read the work. It works through
five bounded commands — `status`, `ready`, `brief`, `review` (reads) and
`result` (record a receipt) — while explorers, workers and verifiers read
sources and artifacts in their own context and return a short receipt.

## Why it is shaped this way

- **Context economy.** One `item.md` per task, a compact brief for delegation,
  and a fixed-size receipt for results. The orchestrator's context depends on
  the number of active items, not the size of the backlog or the artifacts.
- **Durable state.** Markdown in the repository is the source of truth. A fresh
  session resumes from `status` plus one `brief`.
- **One approval.** Approval covers the item's scope and survives status,
  plan, design, progress and artifact changes. Present the exact records
  (`workflow review <epic>`) rather than a summary; only a real scope change
  re-prompts.
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

# Plan before exploration, design after it.
cli/bin/workflow plan T-001 --text "Question, dimensions, sources, stop conditions."
cli/bin/workflow design T-001 --text "Outline, canonical values, decisions, checks."

# Daily coordination.
cli/bin/workflow status
cli/bin/workflow ready
cli/bin/workflow brief T-001 --for worker
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
phase: plan               # plan | explore | design | implement | verify
priority: 2
depends_on: []
owns: [cli/src/Domain/]
artifacts: []
approval: {at: ..., by: adam, scope_hash: ...}
outcome: ...
scope: {in: [...], out: [...]}
acceptance: [...]
```

`board.html` is generated and read-only. Commands that change a card
(`init`, `new`, `set`, `approve`, `close`) rewrite it automatically, so
reopening the board shows the current state. `workflow board` forces a
rewrite after a manual edit.

## The loop

1. **Frame** — outcome, scope, acceptance, dependencies, owned paths.
2. **Plan** — pre-exploration approach: question, dimensions, sources, stop
   conditions, acceptance mapping.
3. **Explore** — bounded questions: codebase, web/prior art, analysis, spikes.
4. **Design** — post-exploration solution: outline, canonical claims or
   numbers, decisions, trade-offs, checks, risks. At epic level: guide or
   system architecture, child decomposition and coverage.
5. **Implement** — the worker changes owned paths only and implements the
   design.
6. **Verify** — an independent verifier checks the frozen artifacts against
   acceptance and design.
7. **Close** — done or cancelled.

Apply the loop to every epic, task and subtask. For multi-dimensional work,
run a discovery/design task before framing the implementation children, then
present the exact `workflow review <epic>` packet for approval. Phases and
hierarchy are guidance, not a state machine. The CLI enforces only what
prevents corruption; it warns when a plan or design is missing. Each role gets
`workflow brief <id> --for <role>`. A done closure without a verifier receipt
is refused unless `--force` is used deliberately.

## Commands

| Command | Purpose |
| --- | --- |
| `init` | scaffold `.workflow/` |
| `new` | create a draft item |
| `status` | working set: active, ready, waiting, decisions |
| `ready` | startable work with dependency and overlap facts |
| `brief <id> --for role` | bounded delegation packet |
| `review <epic>` | exact epic + child scope packet for approval |
| `plan <id>` | replace the `## Plan` section from text or a file |
| `design <id>` | replace the `## Design` section from text or a file |
| `decide <id>` | append a durable decision |
| `result <id>` | append a receipt |
| `deps [id]` | dependency relationships or a Mermaid graph |
| `set <id>` | update mutable fields |
| `approve <id>` | approve the current scope |
| `log <id>` | append a progress line |
| `close <id>` | mark done or cancelled; done needs a verifier receipt |
| `validate` | structure, dependencies, approval, layout |
| `board` | write the static HTML board |
| `show <id>` | raw record (human/debug) |
| `install <dir>` | copy the kit into another project |

All reads support `--json`.

## Roles and skills

- Orchestrator — plans, frames, decomposes, delegates, records, resumes.
- Explorer — answers one bounded question with primary evidence.
- Worker — implements the approved design within owned paths.
- Verifier — checks the frozen artifacts against acceptance and design.

Delegation is by reference and role-specific: the explorer gets the plan and
`outputs/` target, the worker gets the plan, design, parent design and upstream
artifacts, and the verifier gets the acceptance criteria and design with the
producer claim omitted.

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
