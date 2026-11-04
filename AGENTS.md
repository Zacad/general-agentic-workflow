# Project workflow rules

<!-- BEGIN KIT WORKFLOW RULES (kit-managed; refreshed by `install --force`; put your project-specific rules below the END marker so they survive updates) -->

Always load the `clear-communication` and `critical-reasoning` skills before
anything else. This file is the canonical operating contract shared by every
role. When you coordinate work and no workflow agent is registered, act as the
orchestrator: read `.agents/roles/orchestrator.md` and always load the
`workflow-cli`, `work-task`, `decompose-work` and `fresh-session-handoff`
skills. If this root contract is absent, incomplete or conflicting, surface
the need for a reviewed manual merge; do not replace or assume away host
authority.

Markdown in `.workflow/` is durable state; manage item records through
`cli/bin/workflow` (or `workflow` on `PATH`), never by hand-editing them. Load
`.agents/skills/workflow-cli/SKILL.md` for the command contract and read
bounds; load the method skill that matches the work rather than every skill:

| Kind of work | Method skill |
| --- | --- |
| New project | start-project — runs requirements + code-architecture |
| Epic framing/decomposition | decompose-work |
| Task lifecycle coordination | work-task |
| Task solution design | the design worker chooses per task type (default code-architecture route 2) |
| Independent verification | verify-outcome |
| Resume or handoff | fresh-session-handoff |

## Records and project documents

```
.workflow/
  PRD.md                 product requirements
  Architecture.md        design and project decisions
  items/<ID>/item.md     YAML state and Markdown narrative (flat or nested)
  items/<ID>/outputs/    actual deliverables or evidence, only when needed
  board.html             generated, read-only view
```

Items live flat (`items/T-001/item.md`) or hierarchical
(`items/E-001/T-001/item.md`); both are supported and `workflow migrate`
converts between them (see workflow-cli). Only `item.md` and optional
`outputs/` belong inside an item directory — no per-attempt directories or
staged project copies — and never delete failed or cancelled history. Project
decisions belong in PRD/Architecture; item decisions belong on the item.

PRD.md carries product truth (purpose, users, goals, non-goals, requirements,
product-level acceptance); Architecture.md carries system truth (shape,
components, data and state, interfaces, testing, durable technical decisions).
The orchestrator is the default writer; an item may explicitly own a document
and then its owner writes it — a worker may change PRD/Architecture only when
those documents are owned. Epic approval is made against the current PRD;
document updates do not change item scope and never invalidate approval or
verification by themselves. Documents stay editable after completion, and
updating them is not reopening history.

## Kinds, statuses, phases and ownership

Kinds: `epic` (no parent), `task` (epic parent, or standalone with a reason),
`subtask` (task parent). Epics are containers without `owns`; children own
deliverables. `depends_on` expresses ordering, not hierarchy. Ownership names
literal project-relative files/directories, including future paths: lexical
aliases and component ancestry overlap, not globs, realpath or existence.
One deliverable has one writer; parallel work needs satisfied dependencies
and no active ownership overlap.

Statuses: `draft`, `todo`, `in-progress`, `review`, `done`, `cancelled`. The
`review` status indicates a task has completed internal verification but
requires additional review before closure (e.g., external PR review,
orchestrator review, or additional iteration). Items in `review` remain open
and visible in status output; use progress notes to document what is pending.
Phases: `plan`, `explore`, `design`, `implement`, `verify`. Phase does not
imply status. These guide work, not a mandatory state machine. The CLI enforces
structural integrity and current-verification completion; missing Plan/Design
produces advisory warnings rather than new process gates.

## Scope, discovery and approval

Frame outcome, scope in/out, acceptance criteria, dependencies and owned paths;
an epic also includes discovery and integration. Approval covers only kind,
parent, title, outcome, scope, acceptance and dependencies, and `approve`
prints those exact values with their scope hash. Status, phase, priority, owns,
Plan/Design, decisions, artifacts and progress do not invalidate approval;
changed scope does. Approval freshness and verification freshness are
separate. Do not ask again for routine steps.

Present actual records, not a summary — `workflow review <epic>` for the exact
epic/descendant packet, `workflow brief <id> --for orchestrator --json` for one
standalone item — and ask the user to confirm that packet before
`workflow approve`. Approval is staged: epic/discovery scope first, then the
exact new child records after required evidence; the epic confirmation ask and
the children confirmation ask are separate turns. Answers to questions,
resolutions of the coordinator's own doubts, silence, 'no objection' and 'no
further questions' are never approval. If user input changes a presented
packet, re-present the updated packet in a separate turn and obtain a literal
yes/approve addressed to its current content before `workflow approve`; never
record any approval in the same conversational turn in which you received
answers or scope changes. If the user raises new questions or objections after
answering, pause affected work until they are resolved. Required independent
evidence means a separate explorer run, not a mandatory extra item.

Explorer dispatch is the only dispatch allowed without current approval:
pre-approval discovery is legitimate when it informs the scope being approved,
and the Plan should state which mode is used. Implementation work always
requires current approval; never frame implementation children before approving
the epic.

After approval, classify changes with `workflow change`: material changes
(outcome changes, scope removal, dependency removal, owned path changes)
require full re-approval with `workflow approve`; non-material additions
(scope, dependency, acceptance additions) take `workflow approve <id> --delta`.

## Design

The epic `## Design` section is the durable, canonical scope map — candidate
children, acceptance coverage, dependencies, owned paths, integration contract
and open unknowns, with the decomposition rationale and boundedness test from
decompose-work. Record it before children are approved; child designs
reference the parent instead of copying the map, and an `outputs/` artifact is
supporting evidence, not the durable map. Later evidence may revise the map:
review and re-approve only the affected changed scopes, never an unchanged
epic.

A task or subtask Design is a solution design authored by a **design worker**,
never by the orchestrator: drivers/characteristics, compared options with
trade-offs, the chosen approach and its limits, and the subtask decomposition
— the subtask map, or the explicit reason no finer split exists. The
orchestrator records it with `workflow design <id> --file ...` (no
`--approve`), an independent design verifier checks it before the user sees
it, and implementation dispatch requires the user's approval of that design,
recorded with `workflow design <id> --approve` in the same mutation that writes
it. Scope approval survives Design changes; design approval does not — a
material Design change stales it, so re-present the design and re-approve
before dispatch. The design worker, the design verifier and the
implementation worker are separate instances; an instance that authored a
Design never implements it.

## Work sequence

Apply the same approach at each level, scaled to the outcome:

1. **Plan before exploration:** question, dimensions, sources, stop conditions
   and acceptance mapping. Record with `workflow plan`.
2. **Explore:** one bounded question per explorer (`workflow brief <id> --for
   explorer`), sequencing dependent questions; evidence under the assigned
   `.workflow/items/<ID>/outputs/` path.
3. **Design from evidence:** a design worker authors it (see Design).
   Insufficient evidence returns `needs-decision` requesting bounded
   exploration; an over-broad task returns `needs-decision` with a proposed
   split — the orchestrator, not the worker, frames any new items.
4. **Implement:** `phase=implement`; dispatch an implementation worker only
   with current scope and design approval, Plan/Design, required evidence (or
   justified no-exploration), done dependencies and uncontested ownership.
5. **Verify:** freeze named paths, use `phase=verify`, and assign an
   independent verifier (`workflow brief <id> --for verifier`) that derives
   expectations before producer conclusions and reports limits.
6. **Close:** record the verifier receipt with `workflow result <id> --role
   verifier ...`, then `workflow close <id>` in a separate call.

Small work needs one item, short exact scope approval, a one-line Plan and a
one-line Design authored by a design worker and recorded with its design
approval, and an explicit reason if no exploration is needed. Design and
implementation still use separate worker instances and it normally uses an
independent verifier; collapsing them (one instance designing and implementing
its own work) requires the same explicit recorded reason as a deliberate
`close --force --reason "..."` exception, which records unverified closure,
never verifier success; triviality is not an implicit exemption.

Split independently acceptable deliverables, not steps, and prefer the
smallest child that still has one independently acceptable outcome; subtasks
are the default decomposition under a task, and a task kept whole or a broad
item carries the explicit reason. Multi-phase or multi-goal projects are not
one flat child chain: one epic per phase ordered by `depends_on` with the
roadmap in PRD.md, or milestone groups named in the epic Design scope map. The
deliverable/step test, split boundaries and sizing review signals are in
decompose-work; preserve one writer for one voice.

## Delegation and receipts

Delegate by reference — item ID, bounded question/outcome, owned paths,
upstream artifact references and expected return — naming the role file and
always passing `workflow brief <id> --for <role>`, so the full packet stays in
that role's context, not the coordinator's. Explorers, workers and verifiers
read sources and artifacts in their own context; the orchestrator's read views
are bounded (`status`, `ready`, `brief`, `review`). Route artifacts through the
orchestrator; workers do not coordinate directly. Only the orchestrator mutates
item records: a design worker authors its design into `outputs/` and the
orchestrator records that artifact.

Every delegated role returns exactly this receipt; detail stays in artifacts:

```
status: done | blocked | needs-decision | failed
finding: one or two sentences
artifacts: paths the result depends on
decision: the question, or none
next: one bounded action
```

`workflow result` defaults to `--role worker`; always mark verifier receipts
`--role verifier`. A producer's `done` is not item completion. Resolve real
decisions with the user and pause only affected work.

## Verification and historical completion

A successful verifier receipt establishes proof for its frozen target. A
verifier `done` may carry recorded external-dependency limits plus a named
follow-up only when the frozen outcome or acceptance explicitly approves the
alternative branch; otherwise the verifier returns `blocked` and the force
decision stays with the orchestrator. `close --force` is reserved for targets
that could not be verified at all. For open items, tracked scope,
Plan/Design/Decisions, owns or artifact-reference changes, producer receipts
and non-done verifier receipts invalidate that proof. All supported routes
entering `done` require current proof; only an explicit forced close records an
exception. Force does not bypass structure or history protection.

Done items remain historical. Material scope/Plan/Design/Decisions/ownership/
artifact changes, new producer work, unsuccessful verification or other new
work require an **explicit new follow-up item and scope approval**, not edits
to the completed item. Never auto-reopen, generate successors or migrate old
records in bulk. Non-material notes and priority corrections remain possible.
Same-path artifact content changes are not detected by hashes: freeze paths
by convention, report changes and reverify before closing; after completion
use a follow-up and preserve the historical target.

Resume with `workflow status` and one role-specific brief, choosing work with
`ready` (see fresh-session-handoff). Do not reconstruct session memory from
reports. Ask the user only about scope or a real decision.

<!-- END KIT WORKFLOW RULES -->

## Project-specific rules (host-owned; preserved across `install --force`)

Add this project's own conventions below. The kit never rewrites this section —
only the block above between the BEGIN/END markers is managed. Keep host
authority: where a project rule narrows or extends a kit rule, the project rule
governs here and the roles honor the stricter bound.
