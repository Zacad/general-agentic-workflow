# Pilot 3 comparison: the v5 loop against attempts 1 and 2

**Date:** 2026-10-20
**Pilot project:** `/var/home/adam/Projects/wakacje-wlochy-3`
**Top session:** `ses_ea6758ef8ffeSdV0PBGoeHVJdP`
**Sources:** the OpenCode session database (`~/.local/share/opencode/opencode.db`), session exports, the `.workflow` records and `guide/` files of each attempt, and `docs/handoff-compare-3.md`.

## Verdict

The v5 loop improved structure and sourcing, but it did not yet justify its cost, and it did not remove the verification rework it was meant to prevent.

- Plans and designs went from 0/5 and 1/6 to **7/7**.
- The guide recovered attempt 1's sourcing depth (**106 unique URLs** vs 31 in attempt 2's guide) at **11,539 words**, between attempt 1's 13,737 and attempt 2's 7,480.
- It cost **$2.0065**, or **2.3× attempt 1** and **1.8× attempt 2**; cost per 1,000 guide words rose from $0.062 (A1) and $0.147 (A2) to **$0.174**.
- Verification did not shrink: **15 verifier sessions, 13 receipts, 5 blocking receipts**. Attempt 2 used 5 verifier sessions. The four-round loop the design phase was supposed to reduce became a 15-session load spread over 7 items.

The flow is worth keeping as a quality mechanism. It is not yet cheaper, and design-before-writing alone does not prevent numeric and cross-reference defects.

## Attempts 1, 2 and 3 side by side

| Metric | A1 baseline | A2 hardened | A3 v5 pilot |
|---|---:|---:|---:|
| Wall time | 85.7 min | 155.3 min | **99.9 min** |
| Total cost | $0.8552 | $1.1013 | **$2.0065** |
| Orchestrator | $0.0261 (3.1%) | $0.0652 (5.9%) | **$0.3297 (16.4%)** |
| Explorer | — | $0.3226 (29.3%) | **$0.9946 (49.6%)** |
| Worker / writer | $0.7192 (84.1%) | $0.3606 (32.7%) | **$0.3020 (15.1%)** |
| Verifier | $0.1098 (12.8%) | $0.3530 (32.1%) | **$0.3802 (18.9%)** |
| Child sessions | 7 | 10 | **30** |
| Items | 1 epic + 4 tasks | 1 + 5 | **1 + 6** |
| Non-empty plans / designs | 0/5 · 0/5 | 1/6 · 0/6 | **7/7 · 7/7** |
| Subtasks | 0 | 0 | **0** |
| Guide words | 13,737 | 7,480 | **11,539** |
| Research artifact words | 0 | 10,490 | **7,171** |
| Guide unique URLs | 112 | 31 | **106** |
| Combined unique URLs | 112 | 64 | **116** |
| Verifier sessions | 3 | 5 | **15** |
| Verifier receipts | 5 | 5 | **13** |
| Cost per 1,000 guide words | $0.062 | $0.147 | **$0.174** |
| Cost per combined URL | $0.0076 | $0.0172 | **$0.0173** |

### Effort by role

| Role | Sessions | Cost | Share | Session-minutes | Wall span |
|---|---:|---:|---:|---:|---:|
| Orchestrator | 1 | $0.3297 | 16.4% | 99.9 | 99.9 min |
| Explorer | 4 | $0.9946 | 49.6% | 56.9 | 20.6 min |
| Worker | 11 | $0.3020 | 15.1% | 31.7 | 43.5 min |
| Verifier | 15 | $0.3802 | 18.9% | 41.6 | 66.9 min |
| **Total** | **31** | **$2.0065** | **100%** | **230.1** | **99.9 min** |

Session-minutes sum the duration of each session (`time_updated - time_created`); they exceed the 99.9-minute wall time because children ran in parallel. Wall span is the first start to the last end for that role. The orchestrator's span is the whole run.

Measurement notes:

- A1 and A2 cost and wall-time figures reproduce exactly from `session_v2`; the attempt word counts reproduce from the files. A2's combined URL count is 64 in the handoff and 67 under the regex used here, so the A3 number is comparable within about 3 URLs.
- A3 wall time is the top session's `time_created` to `time_updated` (`1792503607589` → `1792509601665`), which agrees with the last child's timestamp.
- "Non-empty plan/design" counts section content, not the empty `## Plan` scaffold created by `workflow new`. This is why A1 and A2 show headings but no content.
- A3's 30 child sessions are 4 explorers, 11 workers and 15 verifiers.

## What the v5 flow changed

### Discovery before writing

T-001 was a real discovery item: one explorer per dimension, four artifacts, **7,171 words and 112 unique URLs** for $0.9946 — half of the run's cost. Every chapter writer's plan cites the T-001 artifacts, and the writers did not repeat broad research. Tool-call counts from the exports:

| Writer | Shell | Read | Edit/write | Search/fetch |
|---|---:|---:|---:|---:|
| Route (T-002) | 11 | 3 | 1 | 1 fetch |
| Bases and sea (T-003) | 25 | 6 | 36 | 0 |
| Places (T-004) | 31 | 9 | 60 | 1 search, 3 fetches |
| Practical (T-005) | 21 | 4 | 2 | 1 search, 1 fetch |

That is the same order as attempt 2's writer (1 search, 2 fetches), so research mostly moved out, but writers still verified individual facts.

### Design before writing

E-001's design carried the canonical numbers (Stelvio closed 28.08.2027 8:00–16:00, Venice 5–6 h from the east and 5.5–6.5 h from the west, Baia del Silenzio 450 people), the document architecture, the coverage matrix and the child decomposition. T-001's design then fixed the chapter split and the voice contract, and the children copied those values into their own designs.

This is visible in the records: 7/7 plans and designs, and E-001's decisions log records the two mid-run canon changes (one base without a recommendation; Venice times corrected) rather than letting them drift.

### Four writers instead of one

The pilot used four chapter writers plus one integrator, not the single writer the handoff suggested. Chapter lengths are balanced within 80 words (2,609 / 2,591 / 2,598 / 2,671), and the final E-001 verifier found no voice defect. That is weak evidence, because E-001 has no explicit voice criterion; it only requires a Polish guide. The deviations that were found were about cross-file facts, not tone.

### E-001 acceptance coverage

Occurrence counts below measure that each element is present in the frozen guide; the E-001 verifier checked correctness. They are not a second full verification. Counts are case-sensitive and cover the four chapter files unless a file is named; the Roman-site row is case-insensitive.

| # | Criterion (abridged) | Measured evidence | Result |
|---|---|---|---|
| 1 | Markdown and standalone HTML cover 3+6+3 | README says "3+6+3"; 4 chapters + `przewodnik.md` (11,539 words) + `przewodnik.html` (115,098 bytes, 0 `<script>`, 0 external stylesheets, 0 `<img>`) | pass |
| 2 | ≥2 route variants with km, times, pass seasonality, Switzerland | Chapter 01 has variant headings A, B and C; R1/R2/R3 5× each; 1,777 / 1,698 / 1,720 km 2× each; Stelvio 26, Gavia 7, San Bernardino 10, Gotthard 7, Simplon 3; "Szwajcaria" 8 | pass |
| 3 | Each recommended base has beach, snorkeling, character, excursion radius | No base is recommended; the table compares 6 areas plus a 7th option with beach, snorkeling reach, a "Promień wycieczek" column and evenings; the beach table adds water entry, shade, facilities and lifeguard (`ratownik` 10) | pass, conditional on "recommended" being read as "compared" |
| 4 | Snorkeling: concrete places, conditions, suitability for a 10-year-old | Chapter 02 names Bergeggi (24), Sentiero Blu (6), Paraggi (3), Niasca (2), San Fruttuoso (2), Capo Noli (4), Punta Manara (4), Tellaro (9), Manarola (3) and Corniglia (2), plus MPA rules, equipment, jellyfish and currents | pass |
| 5 | ≥3 Roman sites with a child angle | Chapter 03 (case-insensitive): Albenga 11, Luni 13, Ventimiglia 6 | pass |
| 6 | Venice with real time, in the return variant, not a day from Liguria | Chapters 01+04: "5–6 h" 6×, "5,5–6,5" 5×, "Mestre" 18×, "4–6 h" 3×; R1 return route | pass |
| 7 | Source and date for time-varying facts | 4 "Źródła i data sprawdzenia" blocks; 12 mentions of 2026-10-20 | pass |
| 8 | No bookings, prices or hourly plan | 0 €/EUR/PLN or number-zł; 0 hourly schedule patterns; "rezerwacja" appears only as a requirement note (Toirano) | pass |

### Verification load

| Item | Verifier receipts | Blocking receipts | What blocked |
|---|---:|---:|---|
| T-001 discovery | 1 | 0 | — |
| T-002 route | 2 | 1 | Venice 4–5 h vs 5–6 h from the east; Luegbrücke 2030 vs 2031 |
| T-003 bases | 2 | 1 | Baia limit stated as 450/day instead of 450 at a time; limit sourced from a portal |
| T-004 places | 1 | 0 | — |
| T-005 practical | 4 | 3 | Austria 90 vs 100 km/h and 2 vs 3-year probation; child-seat rule; speed-table legend; dead ISPRA URL |
| T-006 integration | 2 | 0 | README closed at 15:08, reopened at 15:14, reclosed at 15:16 |
| E-001 epic | 1 | 0 | README missing variant C; PRD still described a 3+3 split |
| **Total** | **13** | **5** | |

The five blocking findings are numeric, legal or source defects — the same class as attempt 2's numeric and cross-reference defects. The design's canonical table covered some values (Venice times, the 450 limit) but not the ones that failed (Austria's speed table, the child-seat threshold, the Luegbrücke end year, a URL slug). Design-before-writing changed where the decisions were made; it did not make the facts correct.

The T-006 reopen and the E-001 cross-file findings show a second gap: the per-chapter verifier scope cannot see README/PRD consistency, and a close with a verifier receipt can still precede the epic-level pass that catches it.

## Record-integrity findings

These are findings about the v5 records, not edits to the pilot.

1. **T-001 stores its Plan twice and its Design three times.** The two `## Plan` sections are identical: 1,334 characters (1,383 bytes) each. The Design body is stored three times: an 8,657-character `###` version (9,067 bytes) and two `##` versions (8,171 and 7,848 bytes). One section differs between the two `##` copies (982 vs 659 bytes). `workflow validate` still reports `OK (0 warnings)`.
2. **`workflow review E-001` emits invalid UTF-8.** It inserts line breaks mid-character (for example `0xC4` followed by `\n` where `ą` should continue). The saved packet is 41,237 bytes / 544 lines and cannot be decoded as UTF-8; the result is identical at `COLUMNS=40` and `COLUMNS=200`. The item files themselves are valid UTF-8, so this is in the review/output path.
3. **`review` hides most of a duplicated Design.** `Item::section()` stops at the first `##` heading, so the packet shows the `###` copy and silently omits the two `##` copies.

**Confidence:** the file contents and the UTF-8 failure are directly reproducible (high). The likely mechanism for the duplication is `ItemStore::replaceSection()`: it terminates at the first `##`, so a design file that uses `##` subsections replaces only the first block and leaves the rest, duplicating content on each call (medium-high; the exact command sequence is not in the record). The impact on the 14:29 approval is limited because the duplicated content is substantively the same, but the claim that the user approved the *exact* record is not supported.

## Limits

- The three attempts used different acceptance criteria. A1 is deeper and longer; A2 is decision-oriented and shorter; A3 uses the v5 E-001 criteria. Quality is not apples-to-apples.
- Attempt 2's "verified" state was already weak (targeted recheck, incomplete criteria), so the comparison flatters A2's rework count.
- Costs are provider-reported session costs, not recomputed from tokens.
- A3's acceptance coverage was checked by its own verifier and spot-checked here (files, URLs, sources, no prices, Roman sites, Venice, variants), not fully re-verified.
- Four writers vs one is confounded with everything else the v5 flow changed.
- The pilot ran in one session on one model; wall time depends on parallel dispatch and provider latency.

## What would change the verdict

If a future run keeps discovery and the epic design but gets blocking verifier receipts to zero or one, and cost per 1,000 guide words below $0.12, the flow would be justified on both quality and cost. The cheapest next experiments:

1. Make the canonical-facts table a checked artifact: the integrator greps every repeated number (speeds, limits, years, travel times) against E-001's design before the first verifier pass.
2. Add an epic-level consistency check that runs *before* per-item closes, so README/PRD drift cannot be found only after T-006 is closed.
3. Fix the record path: `replaceSection` should handle `##` subsections, `validate` should warn on duplicate section headings, and `review` should be encoding-safe.
