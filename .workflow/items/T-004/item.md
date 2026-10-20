---
id: T-004
kind: task
parent: E-001
title: 'Add exact review packet and approval output'
status: done
phase: verify
priority: 2
depends_on:
  - T-003
owns:
  - cli/src/Console/Command/ReviewCommand.php
  - cli/src/Console/Application.php
  - cli/src/Console/Command/ApproveCommand.php
  - cli/tests/Console/CommandTest.php
artifacts:
  - cli/src/Console/Command/ReviewCommand.php
  - cli/src/Console/Application.php
  - cli/src/Console/Command/ApproveCommand.php
  - cli/tests/Console/CommandTest.php
approval:
  at: '2026-10-20T12:31:33+00:00'
  by: user
  scope_hash: 960e7f8f4569007415ff86d7ffa3fd53653b30982b8335ac3c2a0258467c942b
last_result:
  at: '2026-10-20T12:52:06+00:00'
  role: verifier
  status: done
  finding: 'All acceptance criteria pass: review prints epic plan/design and every child field; approve prints actual scope values before a matching hash; review is read-only; suite passes 44 tests.'
  decision: null
verified_at: '2026-10-20T12:52:06+00:00'
created: '2026-10-20T12:30:59+00:00'
updated: '2026-10-20T12:52:06+00:00'
outcome: 'The orchestrator can print an exact review packet for an epic and approval shows the actual scope values.'
scope:
  in:
    - ReviewCommand
    - 'Application registration'
    - 'ApproveCommand output'
    - tests.
  out:
    - 'Changing approval semantics or the scope hash.'
acceptance:
  - 'workflow review E-001 prints the epic plan/design and every child outcome, scope, acceptance, dependencies, owned paths and approval state.'
  - 'workflow approve ID prints outcome, scope in/out, acceptance, dependencies and owned paths before the hash.'
  - 'Tests pass.'
---

## Plan

Approach: add ReviewCommand that prints an epic packet and descendants; extend Approve output with actual scope values.

Files: ReviewCommand, Application, ApproveCommand, tests.
Checks: review shows plan/design and every child field; approve prints values before hash; hash unchanged.
Risks: long output for many children; read-only command must not mutate state.

## Progress

- 2026-10-20T12:50:11+00:00 · worker · done
  Finding: Added read-only workflow review packet and exact approval output; tests pass (44).
  Artifacts: cli/src/Console/Command/ReviewCommand.php, cli/src/Console/Application.php, cli/src/Console/Command/ApproveCommand.php, cli/tests/Console/CommandTest.php
  Decision: none
  Next: Independent verification of T-004.

- 2026-10-20T12:52:06+00:00 · verifier · done
  Finding: All acceptance criteria pass: review prints epic plan/design and every child field; approve prints actual scope values before a matching hash; review is read-only; suite passes 44 tests.
  Artifacts: cli/src/Console/Command/ReviewCommand.php, cli/src/Console/Application.php, cli/src/Console/Command/ApproveCommand.php, cli/tests/Console/CommandTest.php
  Decision: none
  Next: Close T-004 and start T-005.

- 2026-10-20T12:52:06+00:00 · closed as done

## Design

workflow review prints the epic and its descendants with outcome, scope, acceptance, dependencies, owned paths and plan/design state; approve prints the actual scope values before the hash. Review is read-only. Checks: PHPUnit and smoke project.
