---
name: work-task
description: Organize one task's lifecycle — role brief, solution design through the design-approval gate, implementation, independent verification and close.
---

# Work task

Use to organize work on ONE task. Root `AGENTS.md` is the authority for
approvals, records and receipts; this skill sequences the lifecycle, it does
not restate those rules.

**Inputs:** the task item ID, the role brief (`workflow brief <id> --for <role>`;
a design worker reads the full item via `workflow show <id>`), and root
`AGENTS.md`.

## Procedure

1. Load the bounded brief for the role at hand (`--for orchestrator` while
   coordinating, `worker` or `verifier` when dispatching). A design worker
   instead reads the full item with `workflow show <id>` plus its `outputs/`.
   Scope framing and staged approval stay with root `AGENTS.md`.
2. **Solution design (design worker, not the orchestrator):** dispatch a design
   worker — its own instance. It applies `.agents/skills/code-architecture/SKILL.md`
   route 2 — task solution design, or the method skill that fits the task type:
   drivers/characteristics, compared options, one recommendation with limits and
   implementation considerations. It authors the Design as an artifact under
   `outputs/` and returns a receipt; it does not approve its own design. The
   orchestrator records it with `workflow design <id> --file ...` (no `--approve`
   yet) and dispatches an independent design verifier
   (`workflow brief <id> --for verifier`). Only after a passing design
   verification does the orchestrator present the design to the user; on the
   user's approval it re-records with `workflow design <id> --approve` in the
   same mutation. The CLI refuses the implementation worker packet without a
   current design approval, and a material design change stales it (re-present,
   re-approve). If evidence is insufficient or the task is too broad, the design
   worker returns `needs-decision` (requesting exploration or proposing a split);
   the orchestrator frames any new items.
3. **Implement:** `phase=implement`; dispatch an implementation worker — a
   different instance from the design worker. It touches only owned paths and
   returns the receipt contract (status, finding, artifacts, decision, next).
4. **Verify:** freeze named paths, `phase=verify`, and dispatch an
   independent verifier with `workflow brief <id> --for verifier` — it
   derives expectations from acceptance and design before producer claims.
5. **Review or close:** record the verifier receipt
   (`workflow result <id> --role verifier --status done`), then decide in a
   separate call: further review needed → `workflow set <id> status=review`
   plus `workflow log <id> "what is pending"`; ready → `workflow close <id>`.
   Review feedback returns the item to `in-progress` for rework and
   re-verification.

## Escalation

Return `needs-decision` when a scope or design question needs the user;
`blocked` when an external dependency stops progress; `failed` for an
unusable result. The orchestrator resolves the decision and pauses only
affected work.

## Output

An implemented task with current design approval, a recorded verifier
receipt and a `done` close or `review` hold with a progress note — or an
escalated receipt naming the exact decision or blocker.
