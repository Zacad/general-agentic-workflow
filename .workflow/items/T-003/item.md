---
id: T-003
kind: task
parent: E-001
title: 'Extend brief, validate and close for plan/design'
status: done
phase: verify
priority: 2
depends_on:
  - T-002
owns:
  - cli/src/Console/Command/BriefCommand.php
  - cli/src/Validate/ItemValidator.php
  - cli/src/Console/Command/CloseCommand.php
  - cli/tests/Console/CommandTest.php
  - cli/tests/Validate/ItemValidatorTest.php
artifacts:
  - cli/src/Console/Command/BriefCommand.php
  - cli/src/Validate/ItemValidator.php
  - cli/src/Console/Command/CloseCommand.php
  - cli/tests/Console/CommandTest.php
  - cli/tests/Validate/ItemValidatorTest.php
approval:
  at: '2026-10-20T12:31:33+00:00'
  by: user
  scope_hash: f8501316a5a4dfa3b73f55bd98456d8d0f39ed9b735e4ea830015584bc9a80f1
last_result:
  at: '2026-10-20T12:47:38+00:00'
  role: verifier
  status: done
  finding: 'All five acceptance criteria pass: brief shows plan/design/parent design; validate warns on missing plan/design; close refuses without verifier but only warns on missing design; dependency-chain overlaps are ignored; suite passes 42 tests.'
  decision: null
verified_at: '2026-10-20T12:47:38+00:00'
created: '2026-10-20T12:30:59+00:00'
updated: '2026-10-20T12:47:39+00:00'
outcome: 'Briefs show plan and design including parent design, validate warns on missing plan/design, and close warns without refusing.'
scope:
  in:
    - 'BriefCommand design and parent design'
    - 'ItemValidator plan/design warnings'
    - 'CloseCommand soft design warning'
    - tests.
  out:
    - 'New hard gates'
    - 'review command'
    - 'approve output.'
acceptance:
  - 'brief --for explorer shows the plan and warns when empty.'
  - 'brief --for worker shows design and parent design and warns when design is empty.'
  - 'validate warns for an open item beyond plan with no plan and for implement/verify with no design.'
  - 'close --status done still requires a verifier receipt but only warns for a missing design.'
  - 'Tests pass.'
---

## Plan

Approach: show design and ancestor design in brief; warn when plan/design are missing; close warns but does not refuse.

Files: BriefCommand, ItemValidator, CloseCommand, tests.
Checks: explorer brief warns on missing plan; worker brief shows parent design; validate warnings fire; close still requires verifier receipt.
Risks: warning noise; long parent design output; keep the verifier blind to the producer claim.

## Progress

- 2026-10-20T12:45:00+00:00 · worker · done
  Finding: Brief shows design and parent design; validate warns on missing plan/design; close warns without refusing; dependency-chain overlaps are ignored; tests pass (42).
  Artifacts: cli/src/Console/Command/BriefCommand.php, cli/src/Validate/ItemValidator.php, cli/src/Console/Command/CloseCommand.php, cli/tests/Console/CommandTest.php, cli/tests/Validate/ItemValidatorTest.php
  Decision: none
  Next: Independent verification of T-003.

- 2026-10-20T12:47:38+00:00 · verifier · done
  Finding: All five acceptance criteria pass: brief shows plan/design/parent design; validate warns on missing plan/design; close refuses without verifier but only warns on missing design; dependency-chain overlaps are ignored; suite passes 42 tests.
  Artifacts: cli/src/Console/Command/BriefCommand.php, cli/src/Validate/ItemValidator.php, cli/src/Console/Command/CloseCommand.php, cli/tests/Console/CommandTest.php, cli/tests/Validate/ItemValidatorTest.php
  Decision: none
  Next: Close T-003 and start T-004.

- 2026-10-20T12:47:39+00:00 · closed as done

## Design

Brief shows plan, design and parent design; validate warns when plan/design are missing; close warns but does not refuse; ownership overlap is skipped across dependency chains. Checks: PHPUnit suite and smoke project.
