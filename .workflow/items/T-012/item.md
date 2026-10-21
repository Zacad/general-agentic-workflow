---
id: T-012
kind: task
parent: E-002
title: 'Reject invalid record structure before persistence'
status: done
phase: verify
priority: 2
depends_on:
  - T-009
owns:
  - cli/src/Validate/StructureValidator.php
  - cli/src/Validate/ItemValidator.php
  - cli/src/Store/ItemStore.php
  - cli/src/Console/Command/NewCommand.php
  - cli/tests/Validate/ItemValidatorTest.php
  - cli/tests/Validate/StructureValidatorTest.php
  - cli/tests/Store/StructureWriteTest.php
  - cli/tests/Console/StructureCommandTest.php
  - .workflow/items/T-012/outputs/failed-target.patch
artifacts:
  - cli/src/Validate/StructureValidator.php
  - cli/src/Validate/ItemValidator.php
  - cli/src/Store/ItemStore.php
  - cli/src/Console/Command/NewCommand.php
  - cli/tests/Validate/ItemValidatorTest.php
  - cli/tests/Validate/StructureValidatorTest.php
  - cli/tests/Store/StructureWriteTest.php
  - cli/tests/Console/StructureCommandTest.php
  - .workflow/items/T-012/outputs/verification.md
  - .workflow/items/T-012/outputs/failed-target.patch
  - .workflow/items/T-012/outputs/verification-recheck.md
approval:
  at: '2026-10-21T04:51:18+00:00'
  by: user
  scope_hash: 9fbb2bc1e181bc415a515ff95588856e7290e18e19d618f2c3b08daf9dee1c7a
last_result:
  at: '2026-10-21T05:30:31+00:00'
  role: verifier
  status: done
  finding: 'All six acceptance criteria pass; prior malformed-child failures now reject atomically. All 151 tests and independent persistence/graph probes pass with frozen hashes unchanged.'
  decision: null
verified_at: '2026-10-21T05:30:31+00:00'
created: '2026-10-20T19:13:13+00:00'
updated: '2026-10-21T05:30:31+00:00'
outcome: 'Create, save and mutate reject invalid parent relationships and dependency graphs atomically without introducing workflow gates.'
scope:
  in:
    - 'Extract shared pure structural checks from existing diagnostics'
    - 'Validate candidates and affected graph relationships inside the existing write lock'
    - 'Keep full-project diagnostics while allowing repairs despite unrelated legacy defects'
    - 'Adapt invalid-record test fixtures to explicit raw fixture writes'
  out:
    - 'Approval, Plan, Design or phase-order gates on persistence'
    - 'Lifecycle verification rules or automatic repair of legacy records'
    - 'ID allocation redesign'
acceptance:
  - 'Create, save and mutate reject invalid ID-kind identity, epic parents, task parents of the wrong kind and missing or wrong-kind subtask parents'
  - 'Missing references, self-parenting, self-dependencies and multi-item dependency cycles are rejected before persistence'
  - 'Changing a parent kind cannot silently invalidate existing child relationships'
  - 'Rejected store and command writes preserve the original item bytes and do not leave new item records'
  - 'Candidate and affected-graph checking allows a valid edit or structural repair when an unrelated legacy record has a structural defect'
  - 'Full validate still reports legacy defects and standalone-reason guidance remains a warning rather than a new gate'
---

## Plan

Question: where can all supported writes share structural refusal without process gates?
Dimensions: parent rules, reference/cycle checks and atomic candidate validation.
Sources: .workflow/items/E-002/outputs/record-lifecycle-discovery.md, ItemStore and existing validator algorithms/tests.
Stop: every supported persistence route and affected reverse relationship has a bounded regression.
Acceptance: compare pure graph results with store/CLI refusal, unchanged bytes and unrelated-defect repair cases.

## Design

Files: extract StructureValidator, integrate ItemStore and ItemValidator, adjust NewCommand as needed and own focused structure tests plus invalid-fixture adaptation.
Decision: run pure candidate-plus-affected graph checks under the existing lock, not full workflow validation.
Checks: every create/save/mutate route, cycles, parent-kind changes, raw legacy fixtures and rejection-before-write behavior.
Risks: unrelated defects must not block repairs, standalone reasons stay guidance, and this writer continues through lifecycle then coordination on shared sources.
Upstream: .workflow/items/E-002/outputs/record-lifecycle-discovery.md and parent Design define the seam and limits.

## Progress

- 2026-10-21T05:07:47+00:00 · worker · done
  Finding: Implemented atomic structural validation under the existing write lock. Focused checks passed with 109 tests and 424 assertions and the full suite passed with 249 tests and 1392 assertions.
  Artifacts: cli/src/Validate/StructureValidator.php, cli/src/Validate/ItemValidator.php, cli/src/Store/ItemStore.php, cli/src/Console/Command/NewCommand.php, cli/tests/Validate/ItemValidatorTest.php, cli/tests/Validate/StructureValidatorTest.php, cli/tests/Store/StructureWriteTest.php, cli/tests/Console/StructureCommandTest.php
  Decision: none
  Next: Independently verify the frozen owned targets.

- 2026-10-21T05:15:04+00:00 · verifier · failed
  Finding: Independent persistence probes found that skipped malformed children let save/mutate and parent creation invalidate affected relationships, failing acceptance criterion 3. All 109 supplied tests pass but do not cover the defect.
  Artifacts: .workflow/items/T-012/outputs/verification.md
  Decision: none
  Next: Repair omitted affected-child validation and independently reverify T-012.

- 2026-10-21T05:24:21+00:00 · worker · done
  Finding: Repaired malformed-child graph loading and preserved the failed-target evidence. Focused tests pass with 151 tests and 591 assertions; full independent recheck remains pending.
  Artifacts: cli/src/Validate/StructureValidator.php, cli/src/Validate/ItemValidator.php, cli/src/Store/ItemStore.php, cli/src/Console/Command/NewCommand.php, cli/tests/Validate/ItemValidatorTest.php, cli/tests/Validate/StructureValidatorTest.php, cli/tests/Store/StructureWriteTest.php, cli/tests/Console/StructureCommandTest.php, .workflow/items/T-012/outputs/failed-target.patch
  Decision: none
  Next: Independently recheck all six T-012 criteria.

- 2026-10-21T05:30:31+00:00 · verifier · done
  Finding: All six acceptance criteria pass; prior malformed-child failures now reject atomically. All 151 tests and independent persistence/graph probes pass with frozen hashes unchanged.
  Artifacts: .workflow/items/T-012/outputs/verification-recheck.md
  Decision: none
  Next: Close T-012 and start lifecycle corrections.

- 2026-10-21T05:30:31+00:00 · closed as done
