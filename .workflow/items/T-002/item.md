---
id: T-002
kind: task
parent: E-001
title: 'Add design phase, section and command'
status: done
phase: verify
priority: 1
depends_on:
  - T-001
owns:
  - cli/src/Domain/Phase.php
  - cli/src/Console/Command/DesignCommand.php
  - cli/src/Console/Application.php
  - cli/src/Console/Command/NewCommand.php
  - cli/src/Console/Command/SetCommand.php
  - cli/src/Board/BoardRenderer.php
  - cli/tests/Console/CommandTest.php
  - cli/tests/Domain/ItemScopeTest.php
artifacts:
  - cli/src/Domain/Phase.php
  - cli/src/Console/Command/DesignCommand.php
  - cli/src/Console/Application.php
  - cli/src/Console/Command/NewCommand.php
  - cli/tests/Console/CommandTest.php
approval:
  at: '2026-10-20T12:31:33+00:00'
  by: user
  scope_hash: b38f3b51949e1582981da5d94d21337f375fee1676929fb80954f0ec962edaef
last_result:
  at: '2026-10-20T12:47:38+00:00'
  role: verifier
  status: done
  finding: 'All five acceptance criteria pass: Phase::Design accepted by new/set; new items default to plan with a ## Design section; workflow design replaces the section; show --section Design returns it; suite passes 37 tests.'
  decision: null
verified_at: '2026-10-20T12:47:38+00:00'
created: '2026-10-20T12:30:59+00:00'
updated: '2026-10-20T12:47:38+00:00'
outcome: 'The workflow has a design phase, a ## Design section and a workflow design command, and new items start in plan.'
scope:
  in:
    - 'Phase enum'
    - DesignCommand
    - 'Application registration'
    - 'NewCommand default and help'
    - 'item template'
    - 'SetCommand phase validation'
    - BoardRenderer
    - tests.
  out:
    - 'Brief/validate/close changes (T-003)'
    - 'review/approve (T-004).'
acceptance:
  - 'Phase::Design exists and is accepted by new and set.'
  - 'workflow design ID --text ... replaces ## Design.'
  - 'New items default to phase=plan and have a ## Design section.'
  - 'workflow show ID --section Design returns the content.'
  - 'Tests pass.'
---

## Plan

Approach: add Design to Phase; add DesignCommand mirroring PlanCommand; insert ## Design into the item template; default new items to plan.

Files: Phase, DesignCommand, Application, NewCommand, SetCommand, BoardRenderer, tests.
Checks: new/set accept design; design command writes the section; show --section Design returns it; old phases still load.
Risks: old items have no ## Design; the section is optional until implement/verify warnings.

## Progress

- 2026-10-20T12:40:50+00:00 · worker · done
  Finding: Added design phase, ## Design section and workflow design command; new items default to plan; tests pass (37).
  Artifacts: cli/src/Domain/Phase.php, cli/src/Console/Command/DesignCommand.php, cli/src/Console/Application.php, cli/src/Console/Command/NewCommand.php, cli/tests/Console/CommandTest.php
  Decision: none
  Next: Independent verification of T-002.

- 2026-10-20T12:47:38+00:00 · verifier · done
  Finding: All five acceptance criteria pass: Phase::Design accepted by new/set; new items default to plan with a ## Design section; workflow design replaces the section; show --section Design returns it; suite passes 37 tests.
  Artifacts: cli/src/Domain/Phase.php, cli/src/Console/Command/DesignCommand.php, cli/src/Console/Application.php, cli/src/Console/Command/NewCommand.php, cli/tests/Console/CommandTest.php
  Decision: none
  Next: Close T-002.

- 2026-10-20T12:47:38+00:00 · closed as done

## Design

Design phase and ## Design section; workflow design command mirrors plan; new items default to plan; legacy items append the section; tests cover new/set/show/design.
