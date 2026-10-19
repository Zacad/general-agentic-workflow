---
name: workflow-cli
description: Use the project-local workflow CLI for compact reads, approvals, plans, decisions and receipts.
---

# Workflow CLI

**Inputs:** the project root, the binary (`cli/bin/workflow` or `workflow` on
`PATH`), and the read or write you intend.

## Start a session

Run `workflow status`. The CLI finds the nearest `.workflow/`; pass
`--project /abs/path` when running from elsewhere. If the status is unclear,
run `workflow ready`.

## Reads

| Command | Use |
| --- | --- |
| `status [--all]` | working set: active, ready, waiting, decisions, counts |
| `ready [--all]` | startable items; flags owned-path overlaps |
| `brief ID [--for role]` | the delegation packet |
| `deps [ID] [--graph]` | dependencies, dependents, Mermaid |
| `validate` | parents, cycles, approvals, artifacts, layout |
| `show ID [--section Name]` | human/debug only |
| `board` | write `.workflow/board.html` |

Default output is bounded: `status` caps each section, `ready` shows the first
20. Add `--all` only when you genuinely need the whole list. Reads accept
`--json`.

**Never read a whole `item.md` in the orchestrator.** Use `brief`. Prefer
delegating by reference: give the worker the ID and tell it to run `brief`
itself, so the packet stays in the worker's context.

## Writes

| Command | Use |
| --- | --- |
| `init` | scaffold `.workflow/` |
| `new` | create a draft item |
| `plan ID --file F` / `--text S` | replace the `## Plan` section |
| `decide ID "..."` | append a durable decision |
| `set ID key=value ...` | status, phase, parent, title, outcome, priority, depends_on, owns, acceptance, scope_in, scope_out |
| `approve ID [--by name]` | approve the current scope; moves draft to todo |
| `log ID "..."` | append a progress line |
| `result ID --status ... --finding "..."` | record a receipt |
| `close ID [--status done\|cancelled] [--reason "..."]` | terminal status |
| `install DIR` | copy the kit into another project |

Only the orchestrator mutates records. Workers write artifacts and return a
receipt. Approval covers only the scope fields (kind, parent, title, outcome,
scope, acceptance, dependencies); status, phase, plan, decisions, progress and
artifacts never invalidate it. Re-approve only after a scope edit.

A receipt is fixed: `status` (`done`, `blocked`, `needs-decision`, `failed`),
one- or two-sentence `finding`, `artifacts`, `decision`, `next`. A verifier
`done` receipt sets `verified_at`.

## Exit codes

`0` success · `1` validation failure · `2` invalid input.

## Safety

- Writes are serialized by one `FlockStore` lock in `.workflow/.locks/` and
  written atomically.
- Do not hand-edit `item.md` during normal work; use the commands. Use `###`
  for subsections inside a section.
- Run `validate` after structural changes or a merge.
