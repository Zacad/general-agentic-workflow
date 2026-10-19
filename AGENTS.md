# Project workflow rules

This project uses the general agentic workflow. Markdown under `.workflow/`
is the durable record. The `workflow` CLI is the supported way to read and
write it.

## The rule that matters most

**The orchestrator coordinates; it does not read the work.**

It works through four bounded commands — `status`, `ready`, `brief` (reads) and
`result` (record a receipt). Explorers, workers and verifiers read the actual
sources and artifacts in their own context and return a short receipt. If the
orchestrator needs more detail, it delegates a new bounded question instead of
opening a report.

## Records

```
.workflow/
  PRD.md                product requirements
  Architecture.md       design and decisions
  items/<ID>/item.md    one item: YAML state + Markdown body
  items/<ID>/outputs/   deliverables, only when there are real files
  board.html            generated, read-only view
```

No other files belong in an item directory. There are no per-attempt
directories and no staged copies of the project.

## Work items

Kinds are `epic`, `task` and optional `subtask`. An epic has no parent, a task
belongs to an epic (or is a standalone task with a reason), and a subtask
belongs to a task. Use dependency edges (`depends_on`) for ordering and
`owns` for the paths a worker may change.

Statuses: `draft`, `todo`, `in-progress`, `done`, `cancelled`.
Phases: `explore`, `plan`, `implement`, `verify`.

The hierarchy and phases are guidance, not a state machine. The CLI enforces
only what prevents corruption: valid kinds, valid parents, no dependency
cycles, and one fresh approval.

## The loop

1. **Frame.** Create the item, write its outcome, scope, acceptance criteria,
   dependencies and owned paths. Present the exact item and ask for approval.
2. **Explore.** Before implementation, answer the open questions. Exploration
   can have several independent dimensions: the codebase, the web/prior art,
   data analysis, a spike. Each dimension is a bounded question to an
   explorer, with its own artifact. Sequence dimensions with `depends_on`
   when one answer changes the next question.
3. **Plan.** Write `## Plan` from the exploration artifacts with
   `workflow plan`. Split into children only when a part has its own
   independently acceptable outcome.
4. **Implement.** A worker changes only its owned paths and records what it
   did in the artifact and receipt.
5. **Verify.** A verifier checks the frozen artifacts against the acceptance
   criteria, independently of the worker. A verifier `done` receipt sets
   `verified_at`.
6. **Close.** `workflow close <id>` when the outcome is done or cancelled.

Small work may pass through the phases in one session. Do not invent ceremony
for a one-line change.

## Approval

Approval covers the item's **scope**: kind, parent, title, outcome, scope,
acceptance criteria and dependencies. It is recorded once with
`workflow approve <id>` and stays valid while that scope is unchanged.

Status, phase, priority, owned paths, plan, decisions, artifacts and progress
never invalidate approval. Editing a scope field does; `validate` reports it
and the item is not `ready` until re-approved.

This is deliberate: ask the user once per real scope. Do not re-ask for
routine steps, and do not treat a status change as a new permission.

## Delegation

Give each worker a bounded assignment:

- the item ID;
- the exact question or outcome;
- the owned paths and any upstream artifact paths it must read;
- what to return.

The cheapest delegation is **by reference**: give the worker the item ID and
tell it to run `workflow brief <id>`. Then the brief stays in the worker's
context, not the orchestrator's. Read the brief yourself only when you need to
review or approve the scope.

Workers never talk to each other directly. The orchestrator routes artifact
paths. Run work in parallel only when `ready` shows no ownership overlap and
the items do not depend on each other.

## Receipts

Every worker ends with exactly this, and the orchestrator records it with
`workflow result`:

```
status: done | blocked | needs-decision | failed
finding: one or two sentences
artifacts: paths the result depends on
decision: the question, or none
next: one bounded action
```

A receipt is not a report. Detail lives in the artifact.

## Context discipline

| Level | Command | When |
| --- | --- | --- |
| L0 | `workflow status` | every session |
| L1 | `workflow ready` | choosing work |
| L2 | `workflow brief <id>` | delegating |
| L3 | `workflow show <id> --section Plan` or one artifact | exceptional |
| L4 | full report, diff or log | never in the orchestrator |

Default to the lowest level that answers the question. A fresh session
resumes from `status` plus one `brief`; there is no session memory to
reconstruct.

## CLI

The binary is `cli/bin/workflow` in this kit. Add `cli/bin` to `PATH` or use
the full path; the examples below use `workflow`. Load
`.agents/skills/workflow-cli/SKILL.md` for the full command contract and read
bounds.

```
workflow init                     scaffold .workflow/
workflow new "title" [--kind task] [--parent E-001] [--owns path] [--depends ID]
workflow status [--all]           working set
workflow ready [--all]            startable work
workflow brief ID [--for worker]  delegation packet
workflow plan ID --file F         replace the plan section
workflow decide ID "..."          append a durable decision
workflow result ID --status done --finding "..." --artifacts path [--decision "..."] [--next "..."]
workflow set ID status=in-progress phase=plan
workflow approve ID [--by name]
workflow log ID "progress note"
workflow close ID [--status done|cancelled] [--reason "..."]
workflow deps [ID] [--graph]
workflow validate
workflow board
workflow show ID [--section Plan]  raw record (human/debug)
workflow install DIR              copy the kit into another project
```

Run commands from anywhere inside the project; the CLI finds the nearest
`.workflow/`. Use `--project` to point elsewhere.

## Rules of thumb

- Prefer a bounded question over a large read.
- Keep plans and progress short; the record is a working tool, not a diary.
- Record decisions where they apply: project decisions in `PRD.md` or
  `Architecture.md`, item decisions in the item.
- Never delete failed or cancelled history; it is the reason the current
  state is trustworthy.
- Ask the user only when scope or a real decision is at stake.
