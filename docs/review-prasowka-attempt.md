# Workflow performance review — prasowka-bot attempt

Date: 2026-10-22 · Scope: records-only audit of the mid-run attempt in
`~/Projects/prasowka-bot` (E-001 + T-001 done/verified, T-002 awaiting
verification, T-003 waiting). Not a full run; judged sufficient for an
evaluation of the workflow kit. No code was executed in prasowka-bot.

## Verdict

The workflow is performing its purpose. The attempt shows a clean, staged
process with real value delivered at each stage: exploration produced
verified evidence, decisions were surfaced to the user at the right moments,
the mid-run course correction was handled by re-decomposition with an audit
trail, and the independent verifier caught discrepancies the producer
missed. The findings below are refinements, not failures.

## What worked

- **Staged approval held under a mid-run change.** Epic approved 12:57 UTC
  ("zatwierdzam" recorded with scope hash) → explorers 13:01 → decisions
  resolved by user 13:04 → children framed 13:06 → user corrected the course
  form at 13:39 → epic Design re-recorded with an explicit note that it
  "nadpisuje wcześniejszą rację" → children approved 14:16. Epic
  scope/acceptance never changed, so the 12:57 epic approval stayed valid —
  correct per "design does not invalidate approval; changed scope does".
  No implementation was dispatched before 14:16.
- **Exploration paid for itself.** Three bounded explorer runs (LLM access
  routes with a live endpoint check, 13–14 verified RSS channels with 404s
  discovered, stack comparison) each produced a durable artifact under the
  epic's `outputs/`, and each became a load-bearing Design claim (D8
  endpoint, feeds.toml content, Python stack).
- **Verifier independence caught real defects.** T-001's verifier found:
  (a) lesson §1's "13 kanałów" count contradicts the evidence breakdown,
  and (b) the lesson cites a UA-ASCII check that no test in the suite
  performs. Both were things the worker reported as clean. This is the
  strongest evidence that blinding + independent verification works.
- **One-writer discipline.** All three children own disjoint file sets;
  `ready` reports no overlaps; dependencies are a clean T-001 ← T-002 ←
  T-003 chain. The `feed_ok.xml` addition was pre-authorized in the Design
  ("dodana … w ramach przebiegu, jeśli parser jej wymaga") — ownership
  stayed clean.
- **Decision hygiene.** Decisions (D7 Python, D8 OpenCode Go incl. the
  fair-use caveat) are recorded with user resolution and evidence pointers;
  PRD was revised at the goal change (D4 kurs form). PRD/Architecture
  carry the trajectory.
- **Audit trail quality.** Every receipt follows the 5-line format; close
  sequence on T-001 (verifier receipt → close) is correct; timestamps in
  records reconcile with file mtimes (records UTC, filesystem UTC+2).

## What didn't (findings)

**F1 — Factual error propagated through the pipeline (13 vs 14 channels).**
The explorer's own receipt says "13 zweryfikowanych kanałów (4 polska, 5
świat, 5 IT)" — the breakdown sums to 14, and the artifact's tables list 14
verified channels (`rss-sources.md` itself states no count). The wrong
"13" propagated into the epic Design (canonical map) and lesson §1. The
T-001 verifier noticed and classified it "minor non-blocking" — but nothing
records a fix or follow-up. The error survived two independent control
points (design review, verification) because both treated it as cosmetic.
*Lesson: receipts should not introduce new canonical claims; counts should
be computed from the artifact, and a verifier-found inaccuracy should get
an explicit disposition (fixed / follow-up / accepted-with-reason).*

**F2 — Duplicate canonical map.** `outputs/module-1-design-map.md` is a
verbatim copy of E-001's `## Design` section, and child designs reference
the copy. The rules make the epic `## Design` the durable map and say an
`outputs/` artifact is supporting evidence, "not the durable map". The CLI
does not create this file — it was hand-copied. Currently identical, but
two sources of truth invite drift (the map was already revised once; a
future revision would have to remember both copies).
*Fix direction: children should reference the epic item, not a copied
artifact; or the CLI's `design` command should generate the artifact
itself so there is one writer.*

**F3 — Stale Architecture ownership (T4).** Architecture.md T4 still says
T-001 owns the whole `prasowka/` tree and T-002 owns all lessons — the
*pre-correction* decomposition. After the 13:39 re-decomposition, ownership
is per-file across three children. Actual item records govern (no real
conflict), but a project document contradicts the canonical epic map, and
child designs still cite "Architecture T1–T4" including the stale T4.
*Rule reinforcement: "when a decision changes project-level facts,
reconcile the documents" — reconciliation didn't happen here, and nothing
in the CLI warns about it.*

**F4 — Worker judgment call not covered by rules.** T-002's worker changed
behavior beyond its Design (Go endpoint requires `x-opencode-session` /
User-Agent headers) and proceeded, reporting it in the receipt, rather
than returning `needs-decision`. Defensible — the requirement came from
approved exploration evidence, and it was transparent — but the rules give
no explicit criterion for "within-design correction justified by upstream
evidence" vs "needs-decision".
*Fix direction: one sentence in AGENTS.md: corrections whose sole basis is
approved upstream evidence may proceed if reported in the receipt;
anything touching scope, ownership or an unresolved decision returns
`needs-decision`.*

**F5 — `ready` doesn't surface the verify phase.** With T-002 worker-done
and T-003 blocked, `workflow ready` prints "No ready items". Technically
correct (ready lists `todo` items only) but the operator's next action —
dispatch an independent verifier for T-002 — is invisible in the command
that exists to answer "what now?". `status` shows T-002 active with the
worker role, so the information exists but is one inference away.
*Fix direction: ready (or status) should show in-progress items with
their pending next-phase action, e.g. "T-002 awaiting independent
verification".*

**F6 — Out-of-scope boilerplate.** Every child repeats the full M2–M5
exclusion list (5–7 items) that the epic already scopes out. Low harm, but
it is exactly the copying the "reference the parent instead of copying"
rule warns about, and it inflates every approval packet.
*Fix direction: convention of a one-line "out: as epic (M2–M5)" plus only
child-specific exclusions.*

**F7 — Environment note: no git in prasowka-bot.** The attempt ran without
version control, so the workflow's freeze-path/re-verify protections have
no diff history to lean on. Not a workflow defect, but worth a line in the
kit docs: the workflow assumes (or should recommend) an initialized repo.

**F8 — Epic phase metadata goes stale (advisory).** E-001 still reads
`phase: design` while its children are in implement/verify. Phase is
explicitly not a state machine, and the CLI treats this as advisory — but
if phases are kept, the epic's phase should move with the actual work
or the field adds noise.

**F9 — Method skills existed but were never loaded** (added 2026-10-23,
from user feedback on the attempt). The prasowka-bot repo had all three
method skills installed at `~/Projects/prasowka-bot/.agents/skills/`
(`requirements`, `code-architecture`, `decompose-work` — alongside
`workflow-cli` and the kit's other skills), but the attempt's run never
loaded any of them. The symptoms are visible in the records:
Architecture.md stayed shallow (system shape and a decision list, no
drivers/characteristics comparison); task Designs were thin stubs — a
reference to the epic map, "Tu tylko decyzje lokalne", plus a bullet list —
that went straight into implementation; and no subtask analysis was made
despite tasks bundling two deliverables (solution + lesson) plus sub-goals.
Root cause: AGENTS.md says "load the method skill that matches the work"
but gives no phase-to-skill mapping and no design-substance/gate
requirement, so nothing triggered the skills. *Kit response: E-004 (this
repo) adds the mapping, a design-substance rule, the CLI design gate and
organizer skills.*

## Recommended follow-ups (findings-first; not yet approved work)

1. Fix the canonical-claims rule + verifier disposition (F1) — highest
   value: it is the only place where an error slipped through to a
   durable document.
2. Decide the design-map duplication policy (F2): reference-only, or
   CLI-generated artifact.
3. Add the document-reconciliation sweep at re-decomposition (F3) — a
   checklist line or a CLI advisory warning comparing epic Design against
   Architecture ownership facts.
4. One-sentence worker-correction rule (F4).
5. Ready/status verify-phase surfacing (F5) — small CLI change.
6. Boilerplate convention (F6) and docs note (F7) — cheap.
