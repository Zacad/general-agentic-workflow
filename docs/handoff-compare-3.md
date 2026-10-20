# Handoff: workflow v5 pilot on wakacje-wlochy-3

## Start here

You are continuing the workflow-hardening work. The kit has been changed and
verified. A fresh project, `wakacje-wlochy-3`, has been installed from the
updated kit and is empty. Your first job is to run the pilot: use the new
`plan -> explore -> design -> implement -> verify` flow on the same travel
guide, then compare it with attempts 1 and 2.

The user has approved the v5 hardening. Do not modify the kit or attempts 1/2
without a new, explicit decision. The pilot is a new project, not a repair of
attempt 2.

Read this whole file before starting. Then run `workflow status` in the
project you are working in.

## Projects

- Kit: `/var/home/adam/Projects/general-agentic-workflow`
  - branch `main`, commit `00cc412`, pushed to origin.
  - CLI version `5.0.0`; tests 44/44, 155 assertions.
  - `.workflow/` records E-001 + T-001…T-006, all done and verified.
- Attempt 1 — baseline: `/var/home/adam/Projects/wakacje-wlochy`
  - frozen; do not edit.
- Attempt 2 — first hardened run: `/var/home/adam/Projects/wakacje-wlochy-2`
  - frozen; do not edit.
- Attempt 3 — pilot: `/var/home/adam/Projects/wakacje-wlochy-3`
  - created and installed 2026-10-20 from the v5 kit.
  - `.workflow/` is empty: 0 open, 0 done; `validate` OK.
  - `opencode.json` registers `workflow-orchestrator`, `workflow-explorer`,
    `workflow-worker`, `workflow-verifier`; `opencode debug agents` shows all
    four.
  - `cli/vendor` was copied from the kit because `composer` is not on `PATH`.
    The lockfile matches. If Composer becomes available, run
    `composer install` in `wakacje-wlochy-3/cli`.
  - A throwaway install-smoke item was created and removed; the project is
    clean.

Start the pilot in `wakacje-wlochy-3`. Start evaluation/comparison work in the
kit root so it does not add evaluation items to the trip project.

## What changed in v5

The kit now supports the loop the user asked for:

```
plan -> explore -> design -> implement -> verify
```

at every level: epic, task and subtask.

- `## Plan` is the **pre-exploration approach**: question, dimensions,
  sources, stop conditions and acceptance mapping.
- `## Design` is the **post-exploration solution**: artifact outline,
  canonical claims or numbers, decisions and trade-offs, checks and open
  risks. At epic level it also carries the child decomposition, integration
  contract and coverage matrix.
- New items default to `phase=plan`.
- `workflow design ID --text|--file` replaces `## Design`.
- `workflow review <epic>` prints the exact epic and child packet: outcome,
  scope, acceptance, dependencies, owned paths, approval and plan/design
  state. Use it for frame review; do not ask for approval of a prose summary.
- `workflow approve` prints the actual scope identity and values before the
  hash.
- `brief` shows plan, design and parent design:
  - explorer: plan + `outputs/` target;
  - worker: plan, design, parent design, upstream artifacts, last result;
  - verifier: acceptance + design, producer claim omitted.
- `validate` warns when an item beyond `plan` has no plan, or an
  `implement`/`verify` item has no design. `close` warns but does not refuse.
  These are steering warnings, not new hard gates.
- `set` rejects duplicate list fields instead of silently keeping the last
  value. Use `;` for `scope_in`/`scope_out`/`acceptance` and `,` for
  `owns`/`depends_on`.
- Item-local artifact paths `items/<id>/outputs/...` validate correctly under
  `.workflow/`.
- Ownership-overlap warnings are skipped across dependency chains, so
  sequential items sharing a file are not treated as parallel collisions.
- AGENTS, README, all four role prompts and the relevant skills were updated.
  `PRD.md`, `Architecture.md` and `docs/design-notes.md` were aligned.

## Baseline: attempts 1 and 2

### Attempt 1 — baseline

- Top session: `ses_eaa737062ffeIC4Fqtuh4imvVh`
- Shape: 1 epic + 4 tasks, no subtasks.
  - `E-001` guide epic
  - `T-001` outbound/return routes
  - `T-002` coast, beaches, snorkeling
  - `T-003` sights (depends T-002)
  - `T-004` assembly (depends T-001, T-002, T-003)
- 4 workers, 3 verifiers, **0 explorers**; 0 plans; 0 orchestrator `brief`
  calls; phases stayed `implement`; verifiers used plain briefs.
- Wall time 85.7 min; total cost **$0.8552**.
  - orchestrator $0.0261 (3.1%)
  - workers $0.7192 (84.1%)
  - verifiers $0.1098 (12.8%)
- Output: 4 Markdown files, **13,737 words**, **112 unique URLs**.
- `validate` OK; all 5 items have verifier receipts.

Child sessions:

| Session | Role | Task |
| --- | --- | --- |
| `ses_eaa6e5d26ffewHP4wg7q7r5kDJ` | worker | T-001 routes |
| `ses_eaa6e5d25ffeeK72Izw0fp4KiY` | worker | T-002 coast |
| `ses_eaa59ac53ffeggGcwM2R29S6Yi` | verifier | T-001 + T-002 |
| `ses_eaa5563d5ffeU9IUuEUYd9qvZo` | worker | T-003 sights |
| `ses_eaa422bfaffehQf5DYI8LVX5kf` | verifier | T-003 |
| `ses_eaa3b6575ffevDIGqLAQD2bNSt` | worker | T-004 assembly |
| `ses_eaa28d26effe5mK0bwDU19JJ4H` | verifier | T-004 + E-001 |

### Attempt 2 — first hardened run

- Top session: `ses_ea7eec5acffe1pmzc0GBbPluRA`
- Shape: 1 epic + 5 tasks, no subtasks.
  - `E-001` guide epic
  - `T-001` outbound variants
  - `T-002` coast, beaches, snorkeling
  - `T-003` Roman, nature, car trips, Venice
  - `T-004` return variants
  - `T-005` guide writing (depends T-001…T-004)
- 4 explorers, 1 writer, 5 verifiers (1 research + 4 guide rounds).
- Only T-005 has a plan; T-001…T-004 have no plan and no design.
- Wall time 155.3 min; total cost **$1.1013**.
  - orchestrator $0.0652 (5.9%)
  - explorers $0.3226 (29.3%)
  - writer $0.3606 (32.7%)
  - verifiers $0.3530 (32.1%)
- Output: guide **7,480 words** + four research artifacts **10,490 words**;
  **64 unique URLs** across everything, 31 in the guide.
- `validate` OK with 3 artifact-path warnings (the v5 artifact-path fix removes
  those warnings in future runs).
- The guide is more internally consistent and decision-oriented than attempt
  1, but shorter and with fewer unique sources. Attempt 1 is deeper and more
  heavily sourced.

Attempt 2 sessions:

| Session | Role |
| --- | --- |
| `ses_ea7e61efcffeZ6IutCprxS9kVl` | explorer T-001 |
| `ses_ea7e61efbffectUG71Xd6qOdNs` | explorer T-002 |
| `ses_ea7e61efbffdNrfYA2EuTL0ROQ` | explorer T-003 |
| `ses_ea7e61ef6ffeRE2uM3oYqueTxB` | explorer T-004 |
| `ses_ea7ceaa26ffeWcDodD2AMRlE9s` | research verifier |
| `ses_ea7c70bacffe32P6gV7csW6wnH` | writer T-005 |
| `ses_ea7b48012ffeXptAzgTrKYk5O1` | guide verifier 1 |
| `ses_ea79d32b4ffe40bQe4yIsHh5E5` | guide verifier 2 |
| `ses_ea77d7167ffe1t0Jqe2Ckuq2Zp` | guide verifier 3 |
| `ses_ea7695788ffea3kZ4S2AxbTHAh` | final guide verifier |

### Comparison summary

| Metric | Attempt 1 | Attempt 2 |
| --- | ---: | ---: |
| Wall time | 85.7 min | 155.3 min |
| Total cost | $0.8552 | $1.1013 |
| Orchestrator share | 3.1% | 5.9% |
| Explorer share | — | 29.3% |
| Worker/writer share | 84.1% | 32.7% |
| Verifier share | 12.8% | 32.1% |
| Child sessions | 7 | 10 |
| Items | 1 epic + 4 tasks | 1 epic + 5 tasks |
| Plans | 0/4 tasks | 1/5 tasks |
| Guide words | 13,737 | 7,480 |
| Total words | 13,737 | 17,970 |
| Unique URLs | 112 | 64 (31 in guide) |
| Verification rounds | 3 verifier sessions | 5 verifier sessions |

### What the comparison established

- Explorer-first worked: research moved out of the writer; the writer did one
  search and two fetches.
- Role-specific briefs worked: explorers used `--for explorer`, the writer
  used `--for worker`, verifiers used `--for verifier`.
- One writer and one deliverable worked: T-005 wrote only `guide/`.
- The close guard did not need to fire; all closes had verifier receipts.
- The missing-plan guard was ineffective: T-001…T-004 were in `explore`
  while open, then moved to `verify` and closed before `validate` ran.
- No solution-design phase existed. T-001…T-004 merged explore, design and
  implementation in one explorer session. T-005's plan was a post-exploration
  design sketch, but the writer made design decisions while writing.
- The guide needed four verification rounds; most defects were numeric,
  cross-reference or design consistency issues, not prose.
- No subtasks were used and no rationale was recorded. T-002 and T-004 were
  broad: T-002 did 21 searches, 32 shell calls, 47 edits and produced 2,796
  words; T-004 did 29 shell calls, 11 fetches and produced 2,527 words.
- The user approved a **summary**, not the actual item records. The `set` bug
  had truncated scope/acceptance, so the records did not match the summary.
  The first research verifier and first guide verifier therefore ran against
  incomplete criteria. The `set` and artifact-path bugs are fixed in v5.
- The final guide verification was a targeted recheck, not a full pass, and
  the later verifier prompts included expected values. Treat attempt 2's
  "verified" state as weaker than it looks.

## The pilot: what to do in wakacje-wlochy-3

The pilot exists to test whether the v5 flow improves quality enough to
justify its overhead. Follow the kit's workflow; do not invent process in the
trip project.

### 1. Frame the epic and the discovery task

- Start in `/var/home/adam/Projects/wakacje-wlochy-3`.
- Run `./cli/bin/workflow status` and `./cli/bin/workflow validate`.
- Frame E-001 with outcome, scope, acceptance, plan and design. The plan is
  the pre-exploration approach; the design can be a first architecture sketch
  that will be revised after discovery.
- Create a **discovery/design task** under E-001 before framing the research
  children. It should answer: what are the plausible dimensions, regions,
  unknowns and recommended decomposition for this guide? Its deliverable is a
  scope map: candidate items, outcomes, acceptance, dependencies, coverage
  and open unknowns.
- Run the discovery task through the same loop: plan, explore, design,
  implement, verify.
- After discovery, write E-001's design: guide architecture, section outline,
  canonical numbers/decisions, coverage matrix and child decomposition.
- Run `workflow review E-001` and show the exact packet to the user before
  approving the implementation children. Do not ask for approval of a prose
  summary.

### 2. Frame the children with granularity steering

Use the steering check, not a gate:

- one item = one question whose answer can be accepted or rejected
  independently;
- split when an item has more than about three independent questions, an
  artifact above about 2,000 words, or an explorer would need more than about
  ten searches;
- keep one writer for the guide's voice;
- record the decomposition rationale: why these items, why this granularity,
  and why subtasks were or were not used.

For this guide, the obvious dimensions are:

- outbound corridors and day splits;
- passes and lakes with a 10-year-old;
- coastal base regions;
- beaches and child water safety;
- snorkeling and water quality;
- Roman sites;
- nature and car trips;
- Venice feasibility and alternatives;
- return corridors, passes/lakes and the last-day Poland segment.

Do not split every paragraph. Split where an explorer would otherwise carry
multiple independent questions. A reasonable pilot might use 2–3 subtasks for
the heaviest areas, not twenty items.

### 3. Put the guide architecture in E-001's design

E-001's `## Design` should contain:

- the guide outline and section ownership;
- a canonical numbers table: route totals, day distances, repeated values,
  times and sums;
- the decisions: recommended base, outbound variant, return variant, Venice
  yes/no, six-day menu;
- the coverage matrix: E-001 acceptance criterion -> guide section -> source;
- open risks and uncertainties.

The writing task then implements that design. It does not invent the
architecture. Its `## Plan` is the writing approach; its design maps sections
to sources and checks. The writer's brief shows the parent design.

### 4. Record receipts honestly

- Record every verifier round with `--role verifier`, including findings that
  are blocked or need a decision.
- A final verification must be a full pass on the frozen artifact, or the
  receipt must say it is a targeted recheck. Do not call a recheck a full
  verification.
- A done closure needs a verifier receipt. Do not use `--force` unless the
  user explicitly accepts the exception.

### 5. Compare

When the pilot is done, collect:

- wall time and cost from the top session and children;
- child sessions by role;
- items by kind, plans, designs, phases and subtasks;
- `workflow review` packet quality and whether the user had to correct the
  decomposition;
- verifier rounds, blocking findings and residual issues;
- guide and artifact word counts, unique sources, coverage of E-001;
- whether the design reduced rework or merely added ceremony.

Use the same measurement method as the earlier comparison:

```sh
# top-level sessions
opencode session list --format json

# children of a known top session
sqlite3 -readonly ~/.local/share/opencode/opencode.db \
  "select id,parent_id,title,agent,cost,tokens_input,tokens_output,
          tokens_reasoning,tokens_cache_read,time_created,time_updated
   from session_v2
   where id='<top>' or parent_id='<top>'
   order by time_created;"

# tool-call detail
opencode session export <session-id> > /tmp/opencode/session.json
```

Parse exports with Python's `json` module; `jq` failed on at least one large
export earlier even though Python parsed it.

## Constraints

- Do not edit attempts 1 or 2. They are frozen controls.
- Do not finish or repair attempt 2.
- The pilot's `.workflow/` belongs to the guide work. Do not add evaluation
  items there. Evaluation/comparison records belong in the kit.
- Start comparison sessions in the kit root, not in attempt 3, so the
  comparison does not add sessions to attempt 3's data.
- The user wants **steering, not strict gates**. Do not turn the granularity
  heuristic or the plan/design warnings into refusals.
- Keep one writer for the guide's voice. Split evidence and design, not prose.
- Present exact records before approval. `workflow review <epic>` is the
  packet; `workflow approve` prints what it approved.

## Known limits and open questions

- Attempt 1's handoff said 14,486 words; the measured `wc -w` value is
  **13,737**. Use the measured value.
- Attempt 2's writer session database `time_updated` is stale; durations were
  computed from message timestamps. The writer session lived ~102 minutes
  across the first pass and three correction passes.
- The two attempted guides had different acceptance criteria, so quality
  comparison is not apples-to-apples. The pilot should use the v5 E-001
  criteria consistently.
- The v5 flow has not yet been used on a real multi-dimensional guide. The
  pilot is the first evidence for whether design-before-writing reduces the
  four-round verification loop.
- Composer is not on `PATH`; the pilot uses the copied `cli/vendor`. If
  dependencies change, install Composer or copy a matching vendor tree.
- `docs/handoff-compare.md` is the prior (pre-v5) handoff and remains
  untracked. This file is the current handoff.

## Next action

Open a fresh session in `/var/home/adam/Projects/wakacje-wlochy-3`. Run:

```sh
./cli/bin/workflow status
./cli/bin/workflow validate
```

Then frame E-001 and the discovery task, and show the exact `workflow review`
packet before approving the research children. Do not start the pilot by
creating five broad tasks and dispatching explorers; that is the attempt 2
pattern the v5 work is meant to replace.
