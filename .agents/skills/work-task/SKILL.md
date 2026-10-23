---
name: work-task
description: Organize one task's lifecycle — role brief, solution design through the design-approval gate, implementation, independent verification and close.
---

# Work task

Use to organize work on ONE task. Root `AGENTS.md` is the authority for
approvals, records and receipts; this skill sequences the lifecycle, it does
not restate those rules.

**Inputs:** the task item ID, the role brief (`workflow brief <id> --for
<role>`), and root `AGENTS.md`.

## Procedure

1. Load the bounded brief for the role at hand (`--for orchestrator` while
   coordinating, `worker` or `verifier` when dispatching). Scope framing and
   staged approval stay with root `AGENTS.md`.
2. **Solution design:** apply `.agents/skills/code-architecture/SKILL.md`
   route 2 — task solution design: drivers/characteristics, compared
   options, one recommendation with limits. Present it to the user and
   discuss; then record the approval with `workflow design <id> --approve`
   in the same mutation that writes the `## Design`. The CLI refuses worker
   packets without a current design approval, and a material design change
   stales it (re-present, re-approve).
3. **Implement:** `phase=implement`; the worker touches only owned paths and
   returns the receipt contract (status, finding, artifacts, decision, next).
4. **Verify:** freeze named paths, `phase=verify`, and dispatch an
   independent verifier with `workflow brief <id> --for verifier` — it
   derives expectations from acceptance and design before producer claims.
5. **Close:** record the verifier receipt
   (`workflow result <id> --role verifier ...`), then `workflow close <id>`
   in a separate call.

## Escalation

Return `needs-decision` when a scope or design question needs the user;
`blocked` when an external dependency stops progress; `failed` for an
unusable result. The orchestrator resolves the decision and pauses only
affected work.

## Output

An implemented task with current design approval, a recorded verifier
receipt and a `done` close — or an escalated receipt naming the exact
decision or blocker.
