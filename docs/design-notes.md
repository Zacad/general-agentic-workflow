# Design notes

This document records why the rewrite looks the way it does. It is the bridge
between the review of the previous system and the current PRD/architecture.

## What we kept from the previous system

The parts that were genuinely useful, independent of its implementation:

- durable Markdown records;
- an item hierarchy with dependencies;
- preparation before implementation;
- roles with different responsibilities;
- independent verification;
- compact reads for an orchestrator;
- an installable kit.

## Plan and design are separate

The first version of this kit had one `## Plan` section written after
exploration. In practice that collapsed two different decisions: what to
investigate, and what the evidence means for the solution. A run that skipped
the second decision produced a guide whose numbers were resolved while
writing; four verification rounds then caught contradictions that a design
pass would have caught earlier.

The current loop is `plan -> explore -> design -> implement -> verify` at every
level. `Plan` is the pre-exploration approach: question, dimensions, sources,
stop conditions and acceptance mapping. `Design` is the post-exploration
solution: outline, canonical claims or numbers, decisions, trade-offs, checks
and risks. At epic level the design also carries the child decomposition,
integration contract and coverage matrix. The CLI warns when either is missing
but does not turn the sequence into a hard gate.

## What we removed, and why

| Removed | Reason |
| --- | --- |
| Revision-hash approvals and CAS flags on every write | Approval became a moving target; routine edits re-prompted the user |
| Per-attempt directories and staged project copies | Produced 1.1M directories and 51 GB of history |
| Waves, groups, coordinating briefs and checkpoints | Re-created hierarchy under different names and multiplied ceremony |
| SQLite as state truth alongside Markdown | Two truths, drift, adopt and journal machinery |
| Two engines (Go CLI and PHP port) | Duplicate semantics and documentation |
| Exit codes 0–9, journals and explicit recovery | Correct for a database, disproportionate for Markdown files |
| Manifest fingerprints, request IDs and preconditions | Guarded writes dominated a tool whose value is reading |
| Skill/YAML validator machinery | Unrelated to coordinating work |
| "Frozen history, never migrate" plus snapshot-before-delete | Unbounded growth by policy |

## The context problem

The orchestrator's context has four consumers:

1. **state** — status, dependencies, readiness, approval;
2. **task content** — outcome, scope, plan, acceptance;
3. **worker output** — reports, diffs, logs, research;
4. **judgment** — what next, who does it, whether to accept it.

The workflow keeps (1) compressed, lets (2) live in the worker's context,
keeps (3) to a receipt, and reserves the orchestrator for (4).

The read levels:

| Level | Command | When |
| --- | --- | --- |
| L0 | `status` | every session |
| L1 | `ready` | choosing work |
| L2 | `brief` / `review <epic>` | delegating / exact frame review |
| L3 | one section or artifact | exceptional |
| L4 | full report/diff/log | never in the orchestrator |

**Delegation by reference** is part of the design: the orchestrator gives the
worker an item ID and tells it to run `brief`, so even the brief stays out of
the orchestrator's context.

## Does a CLI make sense?

Yes, but as a **projection and integrity layer over Markdown**, not as a state
engine.

It earns its place because it computes facts an LLM should not re-derive from
files (readiness, dependency closure, cycles, approval freshness, ownership
overlap), because it returns stable bounded output, and because it serializes
writes under one lock.

Alternatives considered:

| Alternative | Why not |
| --- | --- |
| Files and conventions only | Every agent re-parses; readiness and cycles drift; the orchestrator ends up reading whole files |
| One `tasks.json` | All-or-nothing reads, merge conflicts, poor progressive disclosure |
| SQLite or Dolt as truth | Opaque, git-hostile, migrations; good for scale/sync (Beads) but overkill here |
| MCP server | Useful later as a wrapper; not a reason to build a daemon now |
| GitHub/Jira | Network, auth, latency, outside the repository |
| A memory-bank of a few files | Goes stale and has no computed readiness or validation |

The interface ideas — a ready-work query, Markdown-native tasks with review
checkpoints, just-in-time retrieval, subagents and structured notes — come from
Beads, Backlog.md, and Anthropic's context-engineering guidance. None of their
storage engines are copied.

## Approval

Approval is a hash of the **scope** only: kind, parent, title, outcome, scope,
acceptance and dependencies. Status, phase, priority, ownership, plan, design,
decisions, artifacts and progress do not affect it. `approve` prints the
actual values it approved, and `review <epic>` prints the exact epic and child
packet before approval. That is the whole fix for repeated approval prompts:

- ask once for a real scope;
- routine work never asks again;
- a scope edit makes the approval stale and `validate` says so.

## File growth

One `item.md` per item. An `outputs/` directory only when there are real
deliverables. Receipts append to the item's progress log. `validate` fails on
unexpected entries. Locks live in `.workflow/.locks/` and are ignored.

## Measured context economy

A synthetic 51-item project (one epic, 50 approved tasks, 0.03 s per read):

| Read | Size |
| --- | --- |
| `status` | 400 bytes, 11 lines |
| `ready` | 1,412 bytes, 21 lines (first 20 + "and 30 more") |
| `brief` (one item) | 403 bytes |
| one full `item.md` | 540 bytes |
| all 51 `item.md` files | 27,437 bytes |

`status` is about 68× smaller than the full record set and stays flat as the
backlog grows. `brief` is constant per item regardless of history, because
progress is not included.

## Risks and revisit signals

- **An incomplete brief pushes the orchestrator back to files.** The brief must
  contain everything needed to delegate. If that fails in practice, fix the
  brief, not the process.
- **Receipts lose nuance.** That is intentional; the artifact path carries the
  detail and the verifier reads it.
- **Markdown parsing is forgiving, not magic.** `validate` catches structural
  damage; unknown keys are preserved.
- **One writer.** Only the orchestrator mutates records. If several
  orchestrators ever run concurrently, per-item locking or a merge strategy
  becomes necessary.
- **Scale.** If a project exceeds a few thousand items, or needs cross-project
  queries, add a **derived, rebuildable** index. Never make it authority.
- **Self-hosting.** Develop conventionally first, then dogfood. Using the
  process to build the process before it is stable compounds overhead.
