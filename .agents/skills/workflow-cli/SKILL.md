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
| `review E-001` | exact epic + child scope packet for approval |
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
| Explorer | `brief ID --for explorer` | adds the plan and the `items/<id>/outputs/` artifact target |
| Worker | `brief ID --for worker` | includes plan, design, parent design, upstream artifacts and the last result |
| Verifier | `brief ID --for verifier` | includes acceptance and design; omits the producer claim |

`review E-001` prints the epic plan/design and every descendant's exact
outcome, scope, acceptance, dependencies, owned paths and plan/design state.
Use it for the frame review; do not ask for approval of a summary.

A verifier that runs plain `brief ID` sees the worker's last result and loses
the intended blinding. Use `--for verifier` every time.

## Writes

| Command | Use |
| --- | --- |
| `init` | scaffold `.workflow/` |
| `new` | create a draft item |
| `plan ID --file F` / `--text S` | replace the `## Plan` section (pre-exploration approach) |
| `design ID --file F` / `--text S` | replace the `## Design` section (post-exploration solution) |
| `decide ID "..."` | append a durable decision |
| `set ID key=value ...` | status, phase, parent, title, outcome, priority, depends_on, owns, acceptance, scope_in, scope_out |
| `approve ID [--by name]` | approve the current scope; prints the actual values and hash |
| `log ID "..."` | append a progress line |
| `result ID --status ... --finding "..."` | record a receipt; use `--role verifier` for a verifier |
| `close ID [--status done\|cancelled] [--reason "..."]` | terminal status; done needs a verifier receipt unless `--force` |
| `install DIR` | copy the kit into another project |

Only the orchestrator mutates records. Workers write artifacts and return a
receipt. Approval covers only the scope fields (kind, parent, title, outcome,
scope, acceptance, dependencies); status, phase, plan, design, decisions,
progress and artifacts never invalidate it. Re-approve only after a scope
edit.

List fields use one value per call. Separate multiple `scope_in`, `scope_out`
and `acceptance` values with `;`; separate `owns` and `depends_on` with `,`.
Repeating the same list key in one `set` call is rejected rather than silently
keeping the last value.

A receipt is fixed: `status` (`done`, `blocked`, `needs-decision`, `failed`),
one- or two-sentence `finding`, `artifacts`, `decision`, `next`. A verifier
`done` receipt sets `verified_at` only when recorded with `--role verifier`.
Then close the item. `close --status done` without a verifier receipt is
refused unless `--force` is passed deliberately.

## Phase and plan/design discipline

Use the phases as a working sequence, not decoration:

1. `new` creates a draft in `plan` by default.
2. Approve the scope. The item is `todo`.
3. `workflow plan ID --file F` (or `--text`) writes the pre-exploration
   approach: question, dimensions, sources, stop conditions, acceptance
   mapping. Then `set ID phase=plan` or move to `explore`.
4. Explore with `brief --for explorer`; artifacts go under
   `items/<id>/outputs/`.
5. `workflow design ID --file F` (or `--text`) writes the post-exploration
   solution: outline, canonical claims or numbers, decisions, trade-offs,
   checks, risks. Then `set ID phase=design`.
6. `set ID status=in-progress phase=implement`; a worker reads
   `brief --for worker` and implements the design in the owned deliverable.
7. `set ID phase=verify`; a verifier reads `brief --for verifier`, checks
   acceptance and design, and returns a verifier receipt.
8. `result ID --role verifier ...`, then `close ID`.

`brief` and `validate` warn when an open item beyond `plan` has no plan or an
`implement`/`verify` item has no design. `close` warns but does not refuse
when a done item has no design. Short plans and designs are enough; empty ones
are not. For an epic, put the architecture, canonical numbers, coverage matrix
and child decomposition in its `## Design`.

## Exit codes

`0` success · `1` validation failure · `2` invalid input.

## Safety

- Writes are serialized by one `FlockStore` lock in `.workflow/.locks/` and
  written atomically.
- Do not hand-edit `item.md` during normal work; use the commands. Use `###`
  for subsections inside a section.
- Run `validate` after structural changes or a merge.
