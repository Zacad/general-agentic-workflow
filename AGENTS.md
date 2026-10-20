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
`owns` for the paths a worker may change. An epic is a container: do not give
it `owns` paths, because its children own the deliverables.

Statuses: `draft`, `todo`, `in-progress`, `done`, `cancelled`.
Phases: `explore`, `plan`, `implement`, `verify`.

The hierarchy and phases are guidance, not a state machine. The CLI enforces
only what prevents corruption: valid kinds, valid parents, no dependency
cycles, one fresh approval, and a verifier receipt before a done closure. It
warns when an implement or verify item has no plan.

## The loop

1. **Frame.** Create the item, write its outcome, scope, acceptance criteria,
   dependencies and owned paths. Present the exact item and ask for approval.
2. **Explore.** Before implementation, answer the open questions. Exploration
   can have several independent dimensions: the codebase, the web/prior art,
   data analysis, a spike. Each dimension is a bounded question to an
   explorer, with its own artifact under `items/<id>/outputs/`. Give the
   explorer `workflow brief <id> --for explorer`. Sequence dimensions with
   `depends_on` when one answer changes the next question.
3. **Plan.** Write `## Plan` from the exploration artifacts with
   `workflow plan`, then set `phase=plan`. Split into children only when a
   part has its own independently acceptable outcome. A plan records the
   approach, artifact outline, evidence needed, checks and open risks; five
   to ten lines is enough.
4. **Implement.** Set `phase=implement` and give a worker the plan and the
   upstream artifacts. A worker changes only its owned paths, synthesizes
   from the plan and evidence, and does not repeat broad research. It records
   what it did in the artifact and receipt.
5. **Verify.** Set `phase=verify`. A verifier reads
   `workflow brief <id> --for verifier` (the producer claim is omitted),
   checks the frozen artifacts against the acceptance criteria independently,
   and returns a verifier receipt. Record it with
   `workflow result <id> --role verifier ...`; a verifier `done` receipt sets
   `verified_at`.
6. **Close.** Run `workflow close <id>` only after the verifier receipt. A
   done closure without a verifier receipt is refused unless `--force` is
   passed deliberately.

Small work may pass through the phases in one session. Do not invent ceremony
for a one-line change, but still write a one-line plan and use a verifier
unless the change is trivial.

## Definition of ready for implementation

An item is ready for a worker only when all of these hold:

- approval is current (scope fields unchanged since approval);
- the plan section is non-empty and names the artifact outline and checks;
- the exploration artifacts the plan depends on exist, or the item records
  that no exploration was needed;
- dependencies are done and owned paths do not overlap active work;
- the worker has one owned deliverable and one writer.

`workflow ready` checks dependencies and ownership. `workflow brief` shows the
plan and artifacts. `workflow validate` warns when an implement or verify
item has no plan.

## Task sizing

One task should produce one independently acceptable deliverable and have one
writer. If a task spans several research dimensions, use explorer questions
first; if it spans several independently acceptable deliverables, use a
subtask or a sibling task. Do not create two writers for the same file.

Subtasks are for parts with their own outcome and own path, not for steps
inside one deliverable. Use an explorer when the missing work is evidence for
one deliverable. Use `outputs/` for research notes, comparisons and spike
reports. A worker session that needs several research passes or dozens of
edits is a sign the item was too broad.

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

Give each role a bounded assignment:

- the item ID;
- the exact question or outcome;
- the owned paths and any upstream artifact paths it must read;
- what to return.

The cheapest delegation is **by reference**: give the role the item ID and
tell it to run the role-specific brief. Then the brief stays in that role's
context, not the orchestrator's. Read the brief yourself only when you need to
review or approve the scope.

| Role | Brief command | What the brief adds |
| --- | --- | --- |
| Explorer | `workflow brief <id> --for explorer` | the `outputs/` artifact target |
| Worker | `workflow brief <id> --for worker` | plan, upstream artifacts and last result |
| Verifier | `workflow brief <id> --for verifier` | producer claim omitted |

Always pass `--for`. The default brief is the worker brief, which is the
wrong context for an explorer or verifier. In particular, a verifier must not
see the producer's conclusion before forming expectations.

Workers never talk to each other directly. The orchestrator routes artifact
paths. Run work in parallel only when `ready` shows no ownership overlap and
the items do not depend on each other.

## Receipts

Every role ends with exactly this, and the orchestrator records it with
`workflow result`:

```
status: done | blocked | needs-decision | failed
finding: one or two sentences
artifacts: paths the result depends on
decision: the question, or none
next: one bounded action
```

A receipt is not a report. Detail lives in the artifact. Record a verifier
receipt with `--role verifier`; the default role is worker. Then close the
item. A done closure without a verifier receipt is refused unless `--force`
is used deliberately.

## Context discipline

| Level | Command | When |
| --- | --- | --- |
| L0 | `workflow status` | every session |
| L1 | `workflow ready` | choosing work |
| L2 | `workflow brief <id> --for role` | delegating |
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
workflow brief ID --for role       delegation packet (explorer, worker, verifier)
workflow plan ID --file F         replace the plan section
workflow decide ID "..."          append a durable decision
workflow result ID --status done --finding "..." --artifacts path [--role verifier] [--decision "..."] [--next "..."]
workflow set ID status=in-progress phase=plan
workflow approve ID [--by name]
workflow log ID "progress note"
workflow close ID [--status done|cancelled] [--reason "..."] [--force]
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
- Write the plan before implementation; a short plan is still a plan.
- Keep plans and progress short; the record is a working tool, not a diary.
- Record decisions where they apply: project decisions in `PRD.md` or
  `Architecture.md`, item decisions in the item.
- Never delete failed or cancelled history; it is the reason the current
  state is trustworthy.
- Ask the user only when scope or a real decision is at stake.
