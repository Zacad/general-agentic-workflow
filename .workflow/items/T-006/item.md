---
id: T-006
kind: task
parent: E-001
title: 'Complete approval output and align project docs'
status: done
phase: verify
priority: 1
depends_on:
  - T-004
  - T-005
owns:
  - cli/src/Console/Command/ApproveCommand.php
  - .workflow/PRD.md
  - .workflow/Architecture.md
  - docs/design-notes.md
  - cli/tests/Console/CommandTest.php
artifacts:
  - cli/src/Console/Command/ApproveCommand.php
  - .workflow/PRD.md
  - .workflow/Architecture.md
  - docs/design-notes.md
  - cli/tests/Console/CommandTest.php
approval:
  at: '2026-10-20T13:13:25+00:00'
  by: user
  scope_hash: c761c4d2a4921a535f76ceed16c6869b978ee9ca7517df5a2706a1d7f1e01eeb
last_result:
  at: '2026-10-20T13:20:59+00:00'
  role: verifier
  status: done
  finding: 'Approval now prints kind/parent/title plus all scope values; PRD, Architecture and design-notes describe the five-phase loop and design/review; suite passes 44 tests.'
  decision: null
verified_at: '2026-10-20T13:20:59+00:00'
created: '2026-10-20T13:13:08+00:00'
updated: '2026-10-20T13:20:59+00:00'
outcome: 'workflow approve prints the full scope identity, and the project-level PRD, Architecture and design notes describe the five-phase loop.'
scope:
  in:
    - 'ApproveCommand kind parent title output'
    - 'PRD phase and command requirements'
    - 'Architecture phase, command list and approval notes'
    - 'design-notes loop and approval notes'
    - tests.
  out:
    - 'New CLI features'
    - 'the travel-guide pilot.'
acceptance:
  - 'workflow approve prints kind, parent, title, outcome, scope in, scope out, acceptance, dependencies and owned paths before the hash.'
  - 'PRD, Architecture and design-notes describe plan, explore, design, implement and verify and mention design and review.'
  - 'Tests pass.'
---

## Plan

Approach: extend approve output with kind/parent/title; update PRD, Architecture and design notes to the five-phase loop and design/review commands; add/adjust tests.

## Design

Design: approval output mirrors the scope fields in the AGENTS definition (kind, parent, title, outcome, scope, acceptance, dependencies, owns) before the hash. Project docs describe plan -> explore -> design -> implement -> verify, the discovery task, exact-record review and soft warnings. Checks: PHPUnit plus grep for old four-phase wording.

## Progress

- 2026-10-20T13:18:56+00:00 · worker · done
  Finding: Approve now prints kind, parent and title alongside the rest of the scope; PRD, Architecture and design-notes describe plan/explore/design/implement/verify and design/review. Tests pass 44/155.
  Artifacts: cli/src/Console/Command/ApproveCommand.php, .workflow/PRD.md, .workflow/Architecture.md, docs/design-notes.md, cli/tests/Console/CommandTest.php
  Decision: none
  Next: Independent verification of T-006.

- 2026-10-20T13:20:59+00:00 · verifier · done
  Finding: Approval now prints kind/parent/title plus all scope values; PRD, Architecture and design-notes describe the five-phase loop and design/review; suite passes 44 tests.
  Artifacts: cli/src/Console/Command/ApproveCommand.php, cli/tests/Console/CommandTest.php, .workflow/PRD.md, .workflow/Architecture.md, docs/design-notes.md
  Decision: none
  Next: Close T-006 and recheck E-001.

- 2026-10-20T13:20:59+00:00 · closed as done
