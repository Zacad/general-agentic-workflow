---
standalone_reason: 'Kit-wide CLI defect; it is not part of the closed v5 epic and must be verified against the CLI suite.'
id: T-008
kind: task
parent: null
title: 'Fix section replacement and Unicode output'
status: done
phase: verify
priority: 1
depends_on: []
owns:
  - cli/src/Store/ItemStore.php
  - cli/src/Domain/Item.php
  - cli/src/Console/Command/BriefCommand.php
  - cli/src/Console/Command/ReviewCommand.php
  - cli/src/Validate/ItemValidator.php
  - cli/tests/Console/CommandTest.php
artifacts:
  - cli/src/Store/ItemStore.php
  - cli/src/Domain/Item.php
  - cli/src/Console/Command/BriefCommand.php
  - cli/src/Console/Command/ReviewCommand.php
  - cli/src/Validate/ItemValidator.php
  - cli/tests/Console/CommandTest.php
approval:
  at: '2026-10-20T17:20:56+00:00'
  by: Adam
  scope_hash: 160484017bdaee5abff3d5a2602789b8f96e1a2b813f7b37b578a150277a1bb3
last_result:
  at: '2026-10-20T17:26:13+00:00'
  role: verifier
  status: done
  finding: 'All five acceptance criteria hold: brief/review are valid UTF-8 with intact Polish text; repeated plan writes with ## subsections and seeded duplicates collapse to one section; validate warns on duplicate and unexpected headings; suite 48 tests/173 assertions; repaired T-001 has one Plan, one Design and six ### subsections with validate OK. Attempts 1 and 2 untouched.'
  decision: null
verified_at: '2026-10-20T17:26:13+00:00'
created: '2026-10-20T17:20:53+00:00'
updated: '2026-10-20T17:26:13+00:00'
outcome: 'Section writes are duplicate-free and Unicode-safe, validate warns on duplicated or unexpected headings, and the suite covers both defects.'
scope:
  in:
    - 'Unicode-safe line splitting in brief and review'
    - 'section boundary handling in Item::section and ItemStore::replaceSection'
    - 'duplicate and unexpected heading warnings in validate'
    - 'regression tests'
    - 'repair of the T-001 record using the fixed CLI'
  out:
    - 'changing approval gates'
    - 'editing attempts 1 or 2'
    - 'guide content'
    - 'changing the record format beyond the four known sections'
acceptance:
  - 'review and brief keep Polish characters and emit valid UTF-8'
  - 'two plan or design calls with ## subsections leave exactly one section with the latest content'
  - 'replacing a body that already has duplicate sections collapses them to one'
  - 'validate warns on duplicate known sections and unexpected ## headings'
  - 'the PHPUnit suite passes'
---

## Plan

**Question:** Why did T-001 accumulate two Plan sections and three Design bodies, and how do we make plan/design writes Unicode-safe, duplicate-free and lossless?

**Dimensions:**
1. Output encoding: `review`/`brief` line splitting on `\R`.
2. Section boundaries: how `replaceSection` and `section` decide where a section ends.
3. Validation: how duplicates and unexpected top-level headings are detected.
4. Repair: reconstruct T-001 from its copies without losing unique text.

**Sources:** `.workflow/items/T-001/item.md`, the top-session export (`ses_ea6758ef8ffeSdV0PBGoeHVJdP`), `cli/src/Store/ItemStore.php`, `cli/src/Domain/Item.php`, `cli/src/Console/Command/{Brief,Review}Command.php`, `cli/src/Validate/ItemValidator.php`, `cli/tests/Console/CommandTest.php`.

**Stop:** the two reproduction defects fail before the change and pass after; the suite is green; T-001 has one Plan and one Design and a diff shows every unique line from the old copies is still present.

**Acceptance mapping:** UTF-8 test (1), section replacement test (2), duplicate-repair test (3), validate warning test (4), T-001 diff (5).

## Design

**Root cause (from the session export):** the first `plan T-001` call passed a file that began with `## Plan`; `replaceSection` inserted it after the existing `## Plan`, creating a nested heading. The next call could then replace only the text before the nested heading, so the duplicate survived. The design file used `## 1.`… `## 6.` headings and was passed five times; each call replaced only the text before the first `##` subsection, leaving the previous bodies in place. All writes came from the orchestrator, sequentially; no parallel subagent wrote T-001. The 0x85 byte inside "ą" explains the invalid UTF-8: `\R` matches NEL in non-Unicode mode.

**Fix:**
1. `BriefCommand` and `ReviewCommand`: `preg_split('/\R/u', ...)`.
2. `Item::section()` and `ItemStore::replaceSection()`: a section ends at the next **known** top-level heading other than the section being read or replaced. This makes `##` subsections content, not boundaries, and lets one replacement collapse duplicate copies.
3. `ItemValidator`: warn on duplicate known headings and on unexpected `##` headings (steering, not a gate).
4. Tests: Polish text through `brief`/`review`; repeated plan/design with `##` subsections; collapse of seeded duplicates; validate warning.

**Semantics:** replacement remains last-write-wins per section, but it now consumes the whole section, so it cannot leave stale copies. It never merges silently; a repair must pass the merged content explicitly.

**Checks:** suite green; a controlled T-001 repair yields one Plan and one Design; every unique line from the old copies is present in the repaired body.

## Progress

- 2026-10-20T17:24:56+00:00 · worker · done
  Finding: Root cause confirmed from the session export: sequential plan/design calls with top-level ## headings; replaceSection stopped at the first ##, so each call left the previous body. Brief/review now use \R/u, section boundaries stop at the next known section and collapse duplicates, validate warns on duplicate/unexpected headings, and the suite is 48/173. T-001 was repaired and re-verified with no current content lost.
  Artifacts: cli/src/Store/ItemStore.php, cli/src/Domain/Item.php, cli/src/Console/Command/BriefCommand.php, cli/src/Console/Command/ReviewCommand.php, cli/src/Validate/ItemValidator.php, cli/tests/Console/CommandTest.php
  Decision: none
  Next: Independent verification of T-008.

- 2026-10-20T17:26:13+00:00 · verifier · done
  Finding: All five acceptance criteria hold: brief/review are valid UTF-8 with intact Polish text; repeated plan writes with ## subsections and seeded duplicates collapse to one section; validate warns on duplicate and unexpected headings; suite 48 tests/173 assertions; repaired T-001 has one Plan, one Design and six ### subsections with validate OK. Attempts 1 and 2 untouched.
  Artifacts: cli/src/Store/ItemStore.php, cli/src/Domain/Item.php, cli/src/Console/Command/BriefCommand.php, cli/src/Console/Command/ReviewCommand.php, cli/src/Validate/ItemValidator.php, cli/tests/Console/CommandTest.php
  Decision: none
  Next: Close T-008.

- 2026-10-20T17:26:13+00:00 · closed as done
  Reason: Fixes verified against acceptance 1-5.
