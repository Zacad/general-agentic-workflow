---
id: T-001
kind: task
parent: E-001
title: 'Fix set list overwrite and artifact path resolution'
status: done
phase: verify
priority: 1
depends_on: []
owns:
  - cli/src/Console/Command/SetCommand.php
  - cli/src/Validate/ItemValidator.php
  - cli/tests/Console/CommandTest.php
  - cli/tests/Validate/ItemValidatorTest.php
artifacts:
  - cli/src/Console/Command/SetCommand.php
  - cli/src/Validate/ItemValidator.php
  - cli/tests/Console/CommandTest.php
  - cli/tests/Validate/ItemValidatorTest.php
approval:
  at: '2026-10-20T12:31:19+00:00'
  by: user
  scope_hash: 77734693a4c62796569c66e4a27842e1d34d206ea3a41d88e9bd51d22c2981cd
last_result:
  at: '2026-10-20T12:37:37+00:00'
  role: verifier
  status: done
  finding: 'All acceptance criteria pass independently: duplicate list fields rejected with no partial write, semicolon values store two entries, and items/<id>/outputs paths resolve under .workflow; full suite passes 36 tests/121 assertions.'
  decision: null
verified_at: '2026-10-20T12:37:37+00:00'
created: '2026-10-20T12:30:59+00:00'
updated: '2026-10-20T12:47:38+00:00'
outcome: 'set no longer silently truncates list fields and item-local artifact paths validate correctly.'
scope:
  in:
    - 'SetCommand duplicate-key detection'
    - 'semicolon-separated list fields'
    - 'list counts'
    - 'Validator artifact candidates under .workflow'
    - tests.
  out:
    - 'Changing the item file format'
    - 'changing result/close semantics.'
acceptance:
  - 'workflow set with repeated scope_in or acceptance fails with a clear duplicate-field error.'
  - 'workflow set with scope_in=a'
  - 'b stores two values.'
  - 'workflow validate accepts items/<id>/outputs/x.md when the file exists under .workflow/items/<id>/outputs/.'
  - 'Tests cover both behaviors and the full suite passes.'
---

## Plan

Approach: reject duplicate list keys in one set call; add a .workflow candidate to artifact validation.

Files: SetCommand, ItemValidator, CommandTest, ItemValidatorTest.
Checks: repeated scope_in fails; semicolon values work; items/<id>/outputs path validates; full suite.
Risks: existing callers using repeated keys; document the semicolon form in the error and help.

## Progress

- 2026-10-20T12:35:30+00:00 · worker · done
  Finding: Set rejects duplicate list fields, accepts semicolon values, prints list counts; artifact validation resolves item-local paths under .workflow; tests pass (36).
  Artifacts: cli/src/Console/Command/SetCommand.php, cli/src/Validate/ItemValidator.php, cli/tests/Console/CommandTest.php, cli/tests/Validate/ItemValidatorTest.php
  Decision: none
  Next: Independent verification of T-001.

- 2026-10-20T12:37:37+00:00 · verifier · done
  Finding: All acceptance criteria pass independently: duplicate list fields rejected with no partial write, semicolon values store two entries, and items/<id>/outputs paths resolve under .workflow; full suite passes 36 tests/121 assertions.
  Artifacts: cli/src/Console/Command/SetCommand.php, cli/src/Validate/ItemValidator.php, cli/tests/Console/CommandTest.php, cli/tests/Validate/ItemValidatorTest.php
  Decision: none
  Next: Close T-001 and start T-002.

- 2026-10-20T12:37:37+00:00 · closed as done

## Design

Set rejects duplicate list fields in one call; semicolon-separated scope/acceptance values; artifact validation also resolves items/<id>/outputs/... under .workflow. Checks: PHPUnit and smoke project.
