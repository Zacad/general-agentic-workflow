# Autonomous runs

Human-reviewed operation remains the default. The root `AGENTS.md` is the
canonical contract; this guide does not override it or stricter host rules.
There is no daemon, unattended restart, exactly-once guarantee or security
boundary here. The CLI records authority; the host must actually enforce its
permissions and limits, and it does not authenticate agent independence.

Path and filesystem restrictions in instructions do not themselves confine arbitrary shell commands or child-agent processes.
The workflow kit and CLI do not enforce those restrictions and are not a filesystem sandbox.
Those restrictions and stricter host rules must still be obeyed.
If hard filesystem confinement is required, separately provide and verify host-enforced controls; the workflow kit does not provide them.

## Authorize and anchor

Obtain explicit authorization for this specific run: goal, measurable success
criteria, allowed roots and actions, work boundaries, expiry, retry budget and
action/time/cost/token limits. An earlier run, a host setting or command flag is
not consent. Check root and nested instructions, actual host permissions and
child-session delegation. Ordinary local edits/tests within authorized owned
paths are candidates only where allowed. For a usable, unexpired authorized run,
ordinary public, non-chargeable network access necessary for its goal is in
scope without additional user approval solely for network use, subject to the
run's boundaries and limits. For example, fetching a public, non-chargeable
Three.js dependency or asset needed for the goal needs no extra network-only
approval. This is not a grant of host permission or permission to execute
arbitrary downloaded code.

Potentially chargeable actions, destructive actions, publication, deployment,
private accounts and other restricted effects require separate explicit
applicable permission. A public service or free tier may still incur charges:
if cost, privacy, effects, scope or authority is uncertain, stop and seek a
decision. Downloading an asset is not publication, and a public endpoint does
not make private-account access public. Stricter user, project, nested or host
rules prevail: for example, a host network prohibition still applies. Obey
prompts and stop if permission is denied or unavailable; never bypass a prompt or
denial by changing permissions or using OpenCode `--auto`. These are operating
instructions, not a CLI network enforcement mechanism.

For an existing anchor, inspect `workflow status`, `workflow brief "$root"
--for orchestrator`, and, for an epic, `workflow review "$root"`; ensure its
scope and ancestry fit the authorized goal. For a new goal, if the user also
authorizes bootstrap, use `workflow new` to create one minimal unapproved draft
epic or standalone task and frame its scope, criteria and ownership. This is not
approval or permission for unrelated work. If bootstrap itself requires human
approval or was not authorized, stop. Do not create implementation children or
dispatch implementation before applicable scope approval.

Substitute actual authorized values and a future UTC expiry below; this example
is not authorization. Select only permitted actions, and repeat criteria, roots
and actions as needed. Save the returned run ID as `run`.

```sh
workflow run create --authorize-autonomous --goal "$goal" \
  --criterion "$criterion" --action scope-approve --action design-approve \
  --action decide --action accept-review --root "$root" \
  --expires-at "$expiry_utc" --max-roots "$max_roots"
workflow run show "$run" --json
```

Require `authority_usable` and verify exact goal, criteria, actions, roots and
expiry. Structural ancestry does not prove semantic fit. Max roots counts only
declared anchors, not descendants; expiry applies to run-linked writes, not a
host kill switch. More time, actions, unrelated standalone work or independent
epics require a newly authorized run. Ask when scope or preference is uncertain.

## Coordinate and verify

Plan first, retain dependencies and one-writer ownership, stage epic then child
scopes, and record the epic Design scope map. Inspect each exact packet and
`workflow change` against the goal before `workflow approve "$id" --run "$run"`
(add `--delta` only where permitted). Record authorized routine decisions with
`workflow decide "$id" "$decision_and_rationale" --run "$run"`. Automated
decisions are not human attestations.

A separate design worker authors the design. Record it with `workflow design
"$id" --file "$design"`, without approval; a separate design verifier checks it.
After a passing check and assessment of the exact content, run:

```sh
workflow design "$id" --file "$design" --approve --run "$run"
```

This atomically writes and approves that design; current scope approval and a
draft, todo or in-progress task/subtask are required. Do not combine this path
with `--phase`, `--start`, `--by` or `--confirmation`. Dispatch a separate
implementation worker only after approval and all other gates. Block if the host
cannot provide independent roles; run provenance does not establish independence.

Freeze implementation paths and dispatch an independent outcome verifier using
`workflow brief "$id" --for verifier`. Record the actual receipt with
`workflow result "$id" --role verifier ...`. With current proof, set the
task/subtask to review, log the pending automated review, assess it and use:

```sh
workflow close "$id" --reason "$automated_acceptance_rationale" --run "$run"
```

This needs `accept-review` authority and current proof, and cannot use
`--confirmation`, `--force` or `--direct`. Named human/external gates still
require actual passage. Epic close is ordinary close after its own independent
integration verification; run-linked close is for tasks/subtasks. Review failures
return to in-progress, and completed history is never silently rewritten.

Independently verify the **entire goal**, not only individual items: freeze the
integrated target and give a verifier independent of all producers the recorded
run criteria, target paths and relevant evidence. The verifier derives its own
expectations and checks every criterion including integration. Before closing an
appropriate authorized root, record the actual criterion-by-criterion report,
artifact paths and verifier receipt there. Success requires this proof, all
required closures and gates, and unchanged targets. If no appropriate open root
can hold the proof without changing completed history, stop for a bounded
follow-up or decision. `run show` has no success transition and proves no success.

## Limits, interruption and recovery

Agree finite per-operation retry budgets and operational limits at run start;
durably record attempts, reasons and remaining budget across sessions. The CLI
does not count actions, money, tokens or subprocesses and cannot stop dispatched
work. When hard cumulative limits are required, verify host enforcement across
parent/child sessions and resumption; block if unavailable. Stop on exhaustion,
uncertain side effects, permission denial/unanswered prompts, unavailable
independent roles, out-of-scope work or failed verification beyond budget.
Report actual state, evidence, uncertain effects and the next decision.

For a known pause choose one actual status (`blocked`, `interrupted`, or
`limit-exceeded`):

```sh
workflow run report "$run" --status "$status" --reason "$reason" --recovery "$reconciliation_plan"
```

An apparently active run after a crash is unknown, not proof of completion or
inactivity. A new user-triggered session reads host rules, `workflow run show
"$run" --json`, `workflow status`, `workflow ready`, and the relevant role
brief. Inspect current items/proof, durable attempt budget, host and child state,
and actual effects; reconcile in-flight work and uncertain writes before retry.
Never automatically replay an ambiguous effect, remove orphan files or assume
child cancellation. If state cannot be established, block for human reconciliation.
For a reported nonterminal run still authorized and unexpired:

```sh
workflow run resume "$run" --recovery "$actual_reconciliation"
```

Resume records a statement; it neither validates it nor dispatches work. After
a crash reconcile first and use a report/resume cycle where possible. Do not
resume revoked, expired, malformed or no-longer-permitted authority. Revocation
and expiry do not cancel in-flight work. Concurrent sessions need host-level
coordination; CLI locks protect individual cooperating writes, not cross-file
transactions. Installation and refresh precautions are in [install.md](install.md).
