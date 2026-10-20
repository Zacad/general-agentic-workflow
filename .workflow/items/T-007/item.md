---
standalone_reason: "Evaluation must run in the kit root so it does not add items or sessions to the trip project; the kit's v5 epic is already closed."
id: T-007
kind: task
parent: null
title: 'Compare pilot 3 with attempts 1 and 2'
status: done
phase: verify
priority: 1
depends_on: []
owns:
  - docs/pilot-compare-3.md
artifacts:
  - docs/pilot-compare-3.md
approval:
  at: '2026-10-20T16:28:43+00:00'
  by: Adam
  scope_hash: 19be500b26a9b091b6ee4772c8676e07f3314000406b715f8d967263777892d1
last_result:
  at: '2026-10-20T16:39:42+00:00'
  role: verifier
  status: done
  finding: 'Acceptance 1-6 pass on the final report: role cost and time, record shape, verifier rounds, words and URLs, and the 7/7 plan-design claim all reproduce from session_v2 and the files; the T-001 duplication and invalid-UTF-8 review findings reproduce; the corrected E-001 coverage table reproduces row by row. One non-blocking label ambiguity remains: Szwajcaria 8 counts the stem (7 exact + 1 inflected).'
  decision: null
verified_at: '2026-10-20T16:39:42+00:00'
created: '2026-10-20T16:28:29+00:00'
updated: '2026-10-20T17:26:13+00:00'
outcome: 'A comparison report in docs/pilot-compare-3.md measures the v5 pilot against attempts 1 and 2 on cost, time, roles, records, sourcing and rework, with stated confidence and limits.'
scope:
  in:
    - 'session and cost measurement for attempt 3 and its children'
    - 'item shape, plans, designs, phases and subtasks'
    - 'review packet quality and record integrity'
    - 'verifier rounds, blocking findings and residual issues'
    - 'guide words, unique URLs and E-001 acceptance coverage'
    - 'comparison with attempts 1 and 2 using the handoff method'
  out:
    - 'editing attempts 1, 2 or 3'
    - 'editing kit code or the trip project records'
    - 're-running the pilot or repairing T-001'
    - 'new guide content'
acceptance:
  - 'report states a verdict first and compares attempt 3 with attempts 1 and 2 in one table'
  - 'cost and wall time are broken down by role with named sources'
  - 'item shape, verifier rounds and blocking findings come from the records'
  - 'guide words, unique URLs and E-001 acceptance coverage are measured'
  - 'record-integrity and review-packet defects are reported with evidence and confidence'
  - 'limits explain why the attempts are not apples-to-apples'
---

## Plan

**Question:** Did the v5 plan/explore/design/implement/verify loop improve the guide enough to justify its overhead, compared with attempts 1 and 2?

**Dimensions:**
1. Effort: wall time and cost for attempt 3, split by orchestrator, explorer, worker and verifier.
2. Record shape: items by kind, plans, designs, phases, subtasks, verifier receipts.
3. Guide quality: words, chapter balance, unique URLs, E-001 acceptance 1–8 coverage.
4. Rework: verifier rounds, blocking findings, correction passes, residual issues.
5. Flow effect: did discovery and the epic design reduce rework, or add ceremony?

**Sources:** the OpenCode session database and exports for `ses_ea6758ef8ffeSdV0PBGoeHVJdP` and its children; `wakacje-wlochy-3/.workflow` records; `wakacje-wlochy-3/guide` files; `docs/handoff-compare-3.md` for attempts 1 and 2.

**Stop:** every number in the comparison table has a named source; measurements use the same method as the handoff; limits and confidence are stated; no attempt 1/2/3 file is modified.

**Acceptance mapping:** verdict and one table (1); role cost/time (2); record shape and verifier rounds (3); words, URLs and coverage (4); integrity findings (5); limits (6).

## Design

**Outcome:** `docs/pilot-compare-3.md` gives a verdict first, then the evidence table, then what the flow changed, record defects, and limits.

**Outline:**
1. Verdict (3–5 sentences, with the cost and rework numbers that support it).
2. Measured attempt 3 (wall time, roles, sessions, items, guide).
3. Comparison table attempts 1/2/3 (effort, shape, output, verification).
4. What the flow changed: discovery, epic design, four chapter writers, integrator, verifier rounds.
5. Record-integrity findings: T-001 duplicate Plan/Design, invalid UTF-8 in `review`, hidden design copies.
6. Limits and what would change the verdict.

**Canonical numbers (verified against the session database and files, 2026-10-20):**

| Metric | A1 | A2 | A3 |
|---|---:|---:|---:|
| Wall time (min) | 85.7 | 155.3 | 99.9 |
| Total cost | $0.8552 | $1.1013 | $2.0065 |
| Orchestrator | $0.0261 (3.1%) | $0.0652 (5.9%) | $0.3297 (16.4%) |
| Explorer | — | $0.3226 (29.3%) | $0.9946 (49.6%) |
| Worker | $0.7192 (84.1%) | $0.3606 (32.7%) | $0.3020 (15.1%) |
| Verifier | $0.1098 (12.8%) | $0.3530 (32.1%) | $0.3802 (18.9%) |
| Child sessions | 7 | 10 | 30 |
| Items | 1+4 | 1+5 | 1+6 |
| Plans / designs | 0/4 · 0/4 | 1/5 · 0/5 | 7/7 · 7/7 |
| Subtasks | 0 | 0 | 0 |
| Guide words | 13,737 | 7,480 | 11,539 |
| Research words | 0 | 10,490 | 7,171 |
| Guide unique URLs | 112 | 31 | 106 |
| Combined unique URLs | 112 | 64 | 116 |
| Verifier sessions | 3 | 5 | 15 |
| Verifier receipts | 5 | 5 | 13 |

Derived: cost per 1,000 guide words $0.062 / $0.147 / $0.174; cost per combined URL $0.0076 / $0.0172 / $0.0173.

**Decisions:**
- Compare measured attempt 3 with the handoff's attempts 1/2, after spot-checking that the handoff numbers reproduce from the database and files (they do).
- Separate "flow effect" from "research depth": more exploration can raise quality and cost without the design phase causing it.
- Treat T-001 duplication and `review` UTF-8 breakage as findings, not as things to repair inside this task.
- Do not re-run the build or edit any attempt; measure the frozen outputs.

**Checks (acceptance):**
1. Verdict and one table.
2. Role cost and wall time with source names.
3. Item shape, verifier rounds, blocking receipts.
4. Words, URLs, E-001 acceptance 1–8 coverage.
5. Integrity findings with evidence and confidence.
6. Limits.

**Risks:** attempts used different acceptance criteria; A2's verified state was already weak; session cost is provider-reported, not recomputed; four chapter writers may have helped or hurt voice, and E-001 has no explicit voice criterion.

## Progress

- 2026-10-20T16:34:31+00:00 · worker · done
  Finding: Report revised after the first verifier pass: added a per-role effort table (cost, session-minutes, wall span), replaced the asserted E-001 coverage with a measured occurrence table for criteria 1-8, and corrected the T-001 size labels to characters and bytes.
  Artifacts: docs/pilot-compare-3.md
  Decision: none
  Next: Independent verification of the revised report.

- 2026-10-20T16:39:42+00:00 · verifier · done
  Finding: Acceptance 1-6 pass on the final report: role cost and time, record shape, verifier rounds, words and URLs, and the 7/7 plan-design claim all reproduce from session_v2 and the files; the T-001 duplication and invalid-UTF-8 review findings reproduce; the corrected E-001 coverage table reproduces row by row. One non-blocking label ambiguity remains: Szwajcaria 8 counts the stem (7 exact + 1 inflected).
  Artifacts: docs/pilot-compare-3.md
  Decision: none
  Next: Close T-007 as done.

- 2026-10-20T16:39:42+00:00 · closed as done
  Reason: Comparison report verified against acceptance 1-6.

## Decisions

- 2026-10-20T17:26:13+00:00 · Follow-up: the record-path fix recommended in the report was implemented as T-008; the duplicated T-001 record was repaired and re-verified. The report itself is unchanged.
