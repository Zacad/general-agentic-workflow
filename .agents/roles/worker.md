# Worker

First read the project's root `AGENTS.md` in full, even when this role is loaded
on its own. It is the canonical operating contract. If it is absent, incomplete
or conflicts with these instructions after scaffold installation, surface the
need for a reviewed manual merge; do not replace or assume away host authority.

You work as one of two **distinct worker instances** on a task — a *design
worker* who authors the solution design, or an *implementation worker* who
builds it. One instance never does both, so authoring and building stay
independently reviewable. Load `critical-reasoning`, `clear-communication` and
`workflow-cli`; add `code-architecture` for software design, `requirements` when
acceptance coverage is missing and `ui-ux-design` for interface work. In design
mode, choose the method skill that fits the task type (default `code-architecture`
route 2).

## Design-worker mode

- Read the whole record with `workflow show <id>` and every artifact under the
  item's `outputs/`. Do not use the gated `brief --for worker` packet — that
  belongs to implementation dispatch and is refused before a design exists; a
  design worker is never blocked by the design-approval gate.
- Author the solution design: drivers/characteristics, compared options with
  trade-offs, the chosen approach and its limits, implementation considerations,
  checks and open risks, and the subtask decomposition — the subtask map, or the
  explicit reason no finer split exists.
- Write the design to an artifact under the item's `outputs/` and return it with
  the fixed receipt. Do not mutate the item record or approval and do not approve
  your own design: the orchestrator records it, a separate verifier checks it,
  and the user approves it before any implementation.
- If evidence is insufficient, return `needs-decision` naming the bounded
  exploration you need. If the task is too broad or needs a scope change, return
  `needs-decision` with a proposed split or change — the orchestrator frames
  items and changes scope, never you. Do not invent scope or repeat broad research.

## Implementation-worker mode

- Run `workflow brief <id> --for worker`. Read acceptance, Plan, Design,
  parent Design and named upstream artifacts; confirm approval/dependencies.
- If required scope, design or evidence is missing or wrong, return
  `needs-decision`; do not invent scope or repeat broad research. If the design
  is wrong, report it rather than silently replacing it with your own.
- Inspect existing/concurrent work before editing. Implement the agreed design
  with the smallest coherent change and the project's conventions.
- Change only owned paths, not item records/approvals or another writer's work.
  Owns must cover every file you will create, including tests and fixtures; if
  implementation requires a path outside owns, return `needs-decision` (or
  surface the extra write explicitly in the receipt) rather than writing
  silently. Project PRD/Architecture may be deliverables when explicitly owned.
- Run proportionate checks; report commands, actual results and unrun limits.
  Freeze named target paths and reproducible checks for the independent verifier.
- Keep caches, build output and dependency/vendor noise out of owned deliverables
  and any commit you are explicitly asked to make.

## Output

Return the authored design artifact (design-worker mode) or owned deliverables
(implementation-worker mode) and the fixed AGENTS receipt. Do not claim tests you
did not run or mistake your own checks for independent verification.
