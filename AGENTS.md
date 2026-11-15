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

Human-reviewed mode is the default. Human presentation, confirmation and
acceptance requirements in this contract apply unless the narrowly scoped
autonomous branch below applies; all other requirements remain in force.

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

Explorer dispatch is the only dispatch allowed without current scope approval:
pre-approval discovery is legitimate when it informs the scope being approved,
and the Plan should state which mode is used. Implementation work always
requires current approval; never frame implementation children before approving
the epic.

After approval, classify changes with `workflow change`: material changes
(outcome changes, scope removal, dependency removal, owned path changes)
require full re-approval with `workflow approve`; non-material additions
(scope, dependency, acceptance additions) take `workflow approve <id> --delta`.

## Autonomous branch (per run only)

This branch replaces only the human checkpoint presentation/confirmation and
acceptance steps above and below, and only while an applicable run has usable
authority for the action and item. It never replaces independent roles, evidence,
scope, history, host policy or CLI gates. Otherwise use human-reviewed mode.
A stricter project, nested, developer, host or user rule prevails; conflicts or
missing/incomplete contracts require reviewed manual merge or human decision.

The user must explicitly authorize this run and its goal, measurable success
criteria, roots, allowed actions and boundaries, expiry, retry budget and
operational limits. A vague goal, prior run, host setting, silence or CLI flag
alone is not authorization. Read host rules and permissions first. Authorized
local edits and ordinary tests within goal and ownership are candidates only
where permitted. For a usable, unexpired authorized run, ordinary public,
non-chargeable network access necessary for its goal, such as fetching public
dependencies or assets, is in scope without additional user approval solely for
network use, subject to the run's boundaries and limits. This grants no host
permission and does not authorize arbitrary execution of downloaded code.
Potentially chargeable actions, destructive actions, publication, deployment,
private accounts and other restricted effects require separate explicit applicable
permission. Public visibility or a free tier does not prove zero cost; if cost,
privacy, effects, scope or authority is uncertain, stop and seek a decision.
Stricter user, project, nested and host rules prevail. Obey prompts and stop on
denied or unavailable permission; never bypass a denial or change permissions
(including OpenCode `--auto`). Check child-session permissions and delegation.

Path and filesystem restrictions in instructions do not themselves confine arbitrary shell commands or child-agent processes.
The workflow kit and CLI do not enforce those restrictions and are not a filesystem sandbox.
Those restrictions and stricter host rules must still be obeyed.
If hard filesystem confinement is required, separately provide and verify host-enforced controls; the workflow kit does not provide them.

For a new goal, only when bootstrap itself is authorized, create and frame a
minimal unapproved draft epic or standalone task as a run anchor; do not create
implementation children or dispatch implementation until scope approval. If
bootstrap needs human approval, stop. For existing roots inspect `status`, the
orchestrator brief and epic `review`; choose existing user-authorized roots whose
scope and ancestry fit the goal. New unrelated roots require new authorization.
Create the run with the actual goal, criteria, selected actions, roots, expiry
and max roots using `workflow run create --authorize-autonomous`; inspect
`workflow run show <run> --json` and require `authority_usable` and exact fit.
The CLI checks ancestry, not goal fit; max roots counts anchors, not descendants.
More authority or time needs a newly authorized run.

Stage epic and child scopes as usual, inspect their exact packets and changes
against the goal and use `workflow approve <id> --run <run>` (or permitted
`--delta`). Record authorized routine decisions with `workflow decide <id>
<decision> --run <run>`. A distinct design worker authors a design; record it
without approval, then a separate verifier checks it. Only after that check,
assess the exact content and use `workflow design <id> --file <path> --approve
--run <run>` before dispatching a separate implementation worker. Run-linked
design approval cannot use `--phase`, `--start`, `--by` or `--confirmation`.
If semantic fit, permission or user preference is unresolved, ask; never call
automated approval a human attestation. If independent roles are unavailable,
block. Explicit human or external gates cannot be invented or replaced.

Freeze targets and record an actual independent verifier receipt. Hold each
task/subtask in review and log the pending automated review; with current proof
and `accept-review` authority, assess and close using `workflow close <id>
--reason <rationale> --run <run>`, never `--confirmation`, `--force` or
`--direct`. Epic closure uses ordinary close after independent integration proof.
Before closing an appropriate authorized root, independently verify the frozen
integrated goal against every recorded run criterion, record the actual
criterion-by-criterion report and verifier receipt there, and preserve evidence.
Report success only after that proof, all gates and closures, and no target
changes. Item proof alone is not goal proof; run records have no success state.
If proof cannot be recorded without changing completed history, stop for a
bounded follow-up or decision. The CLI does not authenticate independent roles.

Set finite per-operation retry budgets and action/time/cost limits at run
start; durably record attempts, reasons and remaining budget across sessions.
The kit does not require a token limit; any user-, project- or host-required
token limit remains binding.
CLI expiry and root limits do not enforce host actions, spending, child processes
or cancellation. Where hard limits are required, require verifiable host-enforced
accounting across children and resumption; otherwise stop. Stop on exhaustion,
unsafe ambiguity, denied/unanswered permissions, unavailable roles or unresolved
verification. Report status, evidence, uncertain effects and the next decision.

For a known pause use `workflow run report <run> --status <blocked|interrupted|limit-exceeded>
--reason <reason> --recovery <plan>` with one actual status. A crashed active
record proves neither completion nor inactivity. On user-triggered resumption,
read host rules, run show, status, ready and the relevant brief; reconcile item
proof, saved budget, child state and actual effects before retrying. Never replay
ambiguous side effects, assume child cancellation, delete orphan files or claim
exactly-once or cross-file atomicity. Block if reconciliation is impossible.
For reported nonterminal state, while still authorized and unexpired, record
`workflow run resume <run> --recovery <actual-reconciliation>`; it neither
validates reconciliation nor dispatches work. After a crash reconcile first and
use report/resume where possible. Expiry/revocation does not cancel in-flight
work; do not resume expired, revoked or malformed authority. Concurrent sessions
need host coordination; CLI locks cover only individual cooperating writes. No
daemon, automatic restart or guaranteed unattended continuation is provided.
See `docs/autonomous-runs.md` for exact commands and limits.

## Browser-rendered verification

When browser-rendered evidence is required, follow `docs/browser-testing.md`.
A missing desktop browser catalog alone is neither a browser ban nor permission
to run one. A desktop `browser` deny and a `browser` skill deny restrict those
capabilities; if denial provenance, intent or coverage is unknown, block and
ask the responsible authority before proposing a fallback. Respect broader
host, project and user prohibitions and never bypass a denial or change
permissions. Use a local route only when the exact role, action and effects are
independently authorized and available. Independent visual inspection remains
required; where continuous playback is required, a reviewer must actually watch
the moving target or faithful recording, not just stills or sampled frames. If
permission, capability or required inspection is unavailable, report blocked.
This guidance does not enforce host permissions or change review gates.

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
it, and implementation dispatch requires approval of that design,
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
6. **Review, then close:** record the verifier receipt with `workflow result
   <id> --role verifier ...`. A verified task/subtask is held, not closed:
   `workflow set <id> status=review` plus a `workflow log` note naming the
   pending gate (PR/CI, deployment check, user acceptance). Review findings
   return the item to `in-progress` on the same item — no follow-up. Close
   in a separate call with `workflow close <id> --confirmation "..."` only on
   the user's acceptance or the named external gate passing; the CLI refuses
   `close` on a task/subtask not in `review`. Only when no review applies,
   `close <id> --direct --reason "..."` records that exception. Epics close
   without the hold.

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
