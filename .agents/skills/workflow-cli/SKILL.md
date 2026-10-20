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
| `brief ID --for role` | the delegation packet: `explorer`, `worker` or `verifier` |
| `deps [ID] [--graph]` | dependencies, dependents, Mermaid |
| `validate` | parents, cycles, approvals, artifacts, layout |
| `show ID [--section Name]` | human/debug only |
| `board` | write `.workflow/board.html` |

Default output is bounded: `status` caps each section, `ready` shows the first
20. Add `--all` only when you genuinely need the whole list. Reads accept
`--json`.

**Never read a whole `item.md` in the orchestrator.** Use `brief`. Prefer
delegating by reference: give the role the ID and tell it to run the
role-specific brief, so the packet stays in that role's context.

### Role-specific briefs

Always pass `--for`; the default is the worker brief.

| Role | Command | Effect |
| --- | --- | --- |
| Explorer | `brief ID --for explorer` | adds the `items/<id>/outputs/` artifact target |
| Worker | `brief ID --for worker` | includes plan, upstream artifacts and the last result |
| Verifier | `brief ID --for verifier` | omits the producer claim so expectations come from the criteria first |

A verifier that runs plain `brief ID` sees the worker's last result and loses
the intended blinding. Use `--for verifier` every time.

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
| `result ID --status ... --finding "..."` | record a receipt; use `--role verifier` for a verifier |
| `close ID [--status done\|cancelled] [--reason "..."]` | terminal status; done needs a verifier receipt unless `--force` |
| `install DIR` | copy the kit into another project |

Only the orchestrator mutates records. Workers write artifacts and return a
receipt. Approval covers only the scope fields (kind, parent, title, outcome,
scope, acceptance, dependencies); status, phase, plan, decisions, progress and
artifacts never invalidate it. Re-approve only after a scope edit.

A receipt is fixed: `status` (`done`, `blocked`, `needs-decision`, `failed`),
one- or two-sentence `finding`, `artifacts`, `decision`, `next`. A verifier
`done` receipt sets `verified_at` only when recorded with `--role verifier`.
Then close the item. `close --status done` without a verifier receipt is
refused unless `--force` is passed deliberately.

## Phase and plan discipline

Use the phases as a working sequence, not decoration:

1. `new` creates a draft in `explore` by default.
2. Approve the scope. The item is `todo`.
3. Explore with `brief --for explorer`; artifacts go under `items/<id>/outputs/`.
4. `workflow plan ID --file F` (or `--text`), then `set ID phase=plan`.
5. `set ID status=in-progress phase=implement`; a worker reads
   `brief --for worker` and writes the owned deliverable.
6. `set ID phase=verify`; a verifier reads `brief --for verifier`, checks the
   criteria and returns a verifier receipt.
7. `result ID --role verifier ...`, then `close ID`.

`brief` and `validate` warn when an open implement or verify item has no
plan. A short plan is enough; an empty plan is not.

## Exit codes

`0` success · `1` validation failure · `2` invalid input.

## Safety

- Writes are serialized by one `FlockStore` lock in `.workflow/.locks/` and
  written atomically.
- Do not hand-edit `item.md` during normal work; use the commands. Use `###`
  for subsections inside a section.
- Run `validate` after structural changes or a merge.
