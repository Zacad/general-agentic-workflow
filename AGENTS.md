# Project workflow rules

Always load skills 'clear-communication' and 'critical reasoning'.

This is the canonical operating contract. Every role loads the project's root
`AGENTS.md`, including when used separately.

Markdown in `.workflow/` is durable state; use `cli/bin/workflow` (or `workflow`
on `PATH`) to manage item records. Load `.agents/skills/workflow-cli/SKILL.md`
for the command contract and read bounds; load the method skill that matches
the work rather than every skill:

| Kind of work | Method skill |
| --- | --- |
| New project | start-project — runs requirements + code-architecture |
| Epic framing/decomposition | decompose-work |
| Task solution design | code-architecture (task solution design route) |
| Task execution | work-task |

## Coordination and records

**The orchestrator coordinates; it does not read the work.** Its bounded read
views are `status`, `ready`, role-specific `brief` and exact-scope `review`.
These are read limits, not a ban on coordinator mutations: the orchestrator
may frame items, write plans/designs/decisions, update state, record approvals
and receipts, and close work through the CLI. It does not open whole item
files, reports, diffs or logs; delegate a bounded question instead.
Explorers, workers and verifiers read sources/artifacts in their own context.

```
.workflow/
  PRD.md                 product requirements
  Architecture.md        design and project decisions
  items/<ID>/item.md     YAML state and Markdown narrative
  items/<ID>/outputs/    actual deliverables or evidence, only when needed
  board.html             generated, read-only view
```

Only `item.md` and optional `outputs/` belong inside an item directory. No
per-attempt directories or staged project copies. Never delete failed or
cancelled history. Project decisions belong in PRD/Architecture; item decisions
belong on the item.

### Project documents

PRD.md and Architecture.md are project documents, not item records: PRD carries
product truth (purpose, users, goals, non-goals, requirements, product-level
acceptance); Architecture carries system truth (shape, components, data and
state, interfaces, testing, durable technical decisions). The orchestrator is
the default writer; an item may explicitly own a document and then its owner
writes it. Fill PRD.md before presenting the first epic for approval, refresh it
at goal or non-goal changes and at each later epic's framing; fill
Architecture.md when recording an epic Design, referencing Architecture rather
than duplicating it. When a decision changes project-level facts, reconcile the
documents; at epic close, sweep item records for durable facts left behind —
documents stay editable after completion, and updating them is not reopening
history. Epic approval is made against the current PRD; document updates do not
change item scope and never invalidate approval or verification by themselves.

Kinds: `epic` (no parent), `task` (epic parent, or standalone with a reason),
`subtask` (task parent). Epics are containers without `owns`; children own
deliverables. `depends_on` expresses ordering, not hierarchy. Ownership names
literal project-relative files/directories, including future paths: lexical
aliases and component ancestry overlap, not globs, realpath or existence.
One deliverable has one writer; parallel work needs satisfied dependencies
and no active ownership overlap.

Statuses: `draft`, `todo`, `in-progress`, `done`, `cancelled`.
Phases: `plan`, `explore`, `design`, `implement`, `verify`. Phase does not imply
status. These guide work, not a mandatory state machine. The CLI enforces
structural integrity and current-verification completion; missing Plan/Design
produces advisory warnings rather than new process gates.

## Scope, discovery and approval

Frame outcome, scope in/out, acceptance criteria, dependencies and owned paths.
For an epic include discovery and integration.

Multi-phase or multi-goal projects are not one flat child chain. Structure them
as one epic per phase (epics are standalone containers; `depends_on` orders
phases) with the roadmap recorded in PRD.md, or as milestone groups named in the
epic Design scope map.

Present actual records, not a
summary: `workflow review <epic>` gives the exact epic/descendant packet,
including plans/designs and owned paths. For one standalone item show
`workflow brief <id> --for orchestrator --json` to preserve exact lists/strings.
Ask the user to confirm that packet before `workflow approve`. Answers to
questions, resolutions of the orchestrator's own doubts and silence are not
approval. If user input changes the presented packet, re-present the updated
packet in a separate turn and obtain an explicit confirmation of that packet —
a literal yes/approve addressed to its current content — before
`workflow approve`. The epic confirmation ask and the children confirmation ask
are separate turns; never record any approval in the same conversational turn in
which you received answers or scope changes. If the user raises new questions or
objections after answering, pause affected work until they are resolved; 'no
objection' and 'no further questions' are never approval.

Approval is staged: approve the epic/discovery scope first; gather required
independent evidence before framing implementation children (or justify no
exploration); then present and approve the exact new child records. Independent
means a separate explorer run, not a mandatory extra item. Discovery may be
recorded on the epic; create a discovery task only for its own useful,
independently acceptable outcome.

Explorer dispatch is the only dispatch allowed without current approval:
pre-approval discovery is legitimate when it informs the scope being approved,
and the Plan should state which mode is used. Implementation work always
requires current approval; never frame implementation children before approving
the epic.

Epic **Design owns the one canonical scope map**: dimensions, children,
acceptance coverage, dependencies, owned paths, integration contract and open
unknowns. Child designs reference the parent instead of copying the map.
Include a coverage matrix and a decomposition rationale that, for each child,
names the finer split considered (if any) and why the chosen boundary beats it —
not only why subtasks were or were not useful, and answers the boundedness
test: does the child bundle two or more independently acceptable outcomes?
If so, split it — at epic level into sibling tasks, inside a task into
subtasks — or record the explicit broad-item reason. A task kept whole while
its outcomes are independently acceptable is the exception; subtasks are the
default decomposition under a task. 'No finer split exists' or 'the finer
split fails one-writer/one-outcome' are valid answers; silence or a bare
'no need' is not.
The epic's `## Design` section is the durable map; record it before children are
approved. An `outputs/` artifact is supporting evidence, not the durable map.
Later evidence may revise decomposition; review/reapprove only affected changed
scopes, not an unchanged epic.

A task or subtask Design is a solution design, not a map reference plus bullet
stubs: it states the drivers/characteristics, the compared options, the chosen
approach and its limits. The method is code-architecture (task solution
design route).

Approval covers only kind, parent, title, outcome, scope, acceptance and
dependencies. `approve` prints those exact values with their scope hash.
Status, phase, priority, owns, Plan/Design, decisions, artifacts and progress
do not invalidate approval; changed scope does. Do not ask again for routine
steps. Approval freshness and verification freshness are separate.

Tasks and subtasks have a second, separate gate: implementation begins only
after the solution design is presented to the user and approved. A task
Design states its subtask decomposition — the subtask map, or the explicit
reason no finer split exists. Record that
approval with `workflow design <id> --approve` in the same mutation that
writes the Design; the CLI refuses worker dispatch without it ('design not
approved' / 'stale design approval'). Scope approval survives Design
changes; design approval does not — a material Design change stales it, so
re-present the design and re-approve before dispatch.

## Work sequence

Apply the same approach at each level, scaled to the outcome:

1. **Plan before exploration:** question, dimensions, sources, stop conditions
   and acceptance mapping. Record with `workflow plan`.
2. **Explore:** give each explorer one bounded question and
   `workflow brief <id> --for explorer`. Evidence goes under the assigned
   `.workflow/items/<ID>/outputs/` path. Sequence dependent questions.
3. **Design from evidence:** artifact outline, canonical claims/values,
   decisions, trade-offs, checks and open risks. Record with `workflow design`.
4. **Implement:** use `phase=implement`; dispatch a worker only with current
   scope and design approval, Plan/Design, required evidence (or justified
   no-exploration), done dependencies and uncontested ownership. `ready`
   checks computed readiness and reports overlaps; it does not check every
   procedural condition here.
5. **Verify:** freeze named paths, use `phase=verify`, and assign an independent
   verifier `workflow brief <id> --for verifier`. The verifier derives
   expectations before seeing producer conclusions, checks the actual target
   against acceptance/design, and reports limits.
6. **Close:** record the verifier receipt with `workflow result <id> --role
   verifier ...`, then `workflow close <id>` in a separate call.

Small work needs one item, short exact scope approval, a one-line Plan and
Design (with its design approval), and an explicit reason if no exploration
is needed. It normally still uses an independent verifier. A deliberate
`close --force --reason "..."` records an unverified exception, never
verifier success; triviality is not an implicit exemption.

Split independently acceptable deliverables, not steps. A **deliverable** can
be accepted or rejected on its own while siblings remain unfinished; a **step**
only has value inside the whole. Pipeline nodes (fetch→select→verify→summarize→
compose) are steps of one deliverable; an e-mail sender, an on-demand CLI entry
point and a persisted history store are each deliverables. Prefer the smallest
child that still has one independently acceptable outcome: a child is too coarse
when its acceptance cannot be verified in one focused worker-and-verifier pass,
or when it bundles two or more separately acceptable outcomes under one writer.
In that case split by deliverable — subtasks under the task, sibling tasks
at epic level — or record the explicit broad-item reason. Bundling needs the
same stated justification as splitting. Use explorers for missing evidence.
Decompose tasks into subtasks for parts with their own outcome and path — by
default; a task without subtasks carries the explicit no-split reason in its
Design.
About three independent questions, 2,000 words or ten searches signal a sizing
review, not a gate. A broad item needs an explicit reason; preserve one writer
for one voice.

## Delegation and receipts

Delegate by reference: item ID, bounded question/outcome, owned paths, upstream
artifact references and expected return. The role runs its own brief so the
packet stays out of the coordinator's context. Always pass `--for`:

| Role | Packet |
| --- | --- |
| `explorer` | Plan and repository-relative evidence target |
| `worker` | Plan, Design, parent Design, upstream artifacts, last result |
| `verifier` | Acceptance, Design, decisions and artifacts; no producer receipt/progress |

The default is worker, not verifier. Blinding is field-based, not censorship
of approved narrative. Route artifacts through the orchestrator; workers do
not coordinate directly. Only the orchestrator mutates item records. A worker
may change project PRD/Architecture only when those documents are owned.

Every delegated role returns exactly this receipt; detail stays in artifacts:

```
status: done | blocked | needs-decision | failed
finding: one or two sentences
artifacts: paths the result depends on
decision: the question, or none
next: one bounded action
```

`result` defaults to worker; always mark verifier receipts `--role verifier`.
A producer's `done` is not item completion. Resolve real decisions with the
user and pause only affected work.

## Verification and historical completion

A successful verifier receipt establishes proof for its frozen target. A
verifier `done` may carry recorded external-dependency limits plus a named
follow-up only when the frozen outcome or acceptance explicitly approves the
alternative branch; otherwise the verifier returns `blocked` and the force
decision stays with the orchestrator. `close --force` is reserved for targets
that could not be verified at all. For open items, tracked scope, Plan/Design/Decisions, owns or artifact-reference
changes, producer receipts and non-done verifier receipts invalidate that
proof. All supported routes entering `done` require current proof; only an
explicit forced close records an exception. Force does not bypass structure
or history protection.

Done items remain historical. Material scope/Plan/Design/Decisions/ownership/
artifact changes, new producer work, unsuccessful verification or other new
work require an **explicit new follow-up item and scope approval**, not edits
to the completed item. Never auto-reopen, generate successors or migrate old
records in bulk. Non-material notes and priority corrections remain possible.
Same-path artifact content changes are not detected by hashes: freeze paths
by convention, report changes and reverify before closing; after completion
use a follow-up and preserve the historical target.

Resume with `workflow status` and one role-specific brief; use `ready` to
choose work. Defaults are bounded; `status --all` is exhaustive, including
terminal groups. Do not reconstruct session memory from reports. Ask the
user only about scope or a real decision.
