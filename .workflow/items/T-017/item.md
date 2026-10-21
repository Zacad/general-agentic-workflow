---
id: T-017
kind: task
parent: E-002
title: 'Combine optional plan and design state updates'
status: done
phase: verify
priority: 2
depends_on:
  - T-014
owns:
  - cli/src/Console/Command/PlanCommand.php
  - cli/src/Console/Command/DesignCommand.php
  - cli/tests/Console/PlanDesignStateTest.php
artifacts:
  - cli/src/Console/Command/PlanCommand.php
  - cli/src/Console/Command/DesignCommand.php
  - cli/tests/Console/PlanDesignStateTest.php
  - .workflow/items/T-017/outputs/verification.md
approval:
  at: '2026-10-21T04:51:18+00:00'
  by: user
  scope_hash: a8396ed2252a63d67e52a1c655f73f0d5a48223d35c3c9c11e77be6b7f482b5c
last_result:
  at: '2026-10-21T06:17:44+00:00'
  role: verifier
  status: done
  finding: 'All six criteria passed independent full verification with 338 real-CLI scenarios and the 693-test suite passing. Frozen hashes remained unchanged.'
  decision: null
verified_at: '2026-10-21T06:17:44+00:00'
created: '2026-10-20T19:13:13+00:00'
updated: '2026-10-21T06:17:46+00:00'
outcome: 'Plan and design can optionally update phase and start status atomically without changing content-only defaults or enforcing a phase sequence.'
scope:
  in:
    - 'Optional --phase and --start flags on existing plan and design commands'
    - 'Validate content and flags before one atomic store mutation'
    - 'Refresh the derived board after successful updates'
  out:
    - 'Automatic status inference from phase or implicit phase rewinds'
    - 'Mandatory phase order, readiness or additional approval gates'
    - 'Result-close combination or a generic command framework'
    - 'Bypassing completed-history protections'
acceptance:
  - 'Without new flags plan/design replace content only and leave current status and phase unchanged'
  - 'An explicit valid --phase changes phase in the same mutation as content and never infers status'
  - 'Only explicit --start requests in-progress status and existing set remains available for manual corrections'
  - 'Invalid phase, invalid content source or unsupported input leaves content, status and phase unchanged'
  - 'Successful changes refresh the derived board and preserve approval unless a scope field actually changed'
  - 'Done-item material edits and --start reopening are refused by the shared lifecycle rule with follow-up instructions'
---

## Plan

Question: can existing content commands remove routine bookkeeping calls without becoming a state machine?
Dimensions: optional flags, atomic validation and derived-board refresh.
Sources: .workflow/items/E-002/outputs/protocol-slimming-discovery.md, PlanCommand, DesignCommand and lifecycle protection.
Stop: no-flag compatibility and flagged success/refusal cases have focused regressions.
Acceptance: compare persisted content/state/approval and board after valid, invalid and completed-item calls.

## Design

Files: add optional phase/start flags directly to PlanCommand and DesignCommand with one focused test file.
Decision: validate before a single content/state mutation, never infer status from phase and retain content-only defaults and manual set.
Checks: absent flags, explicit phase, explicit start, rollback, board refresh, approval stability and done-history refusal.
Risks: reuse lifecycle enforcement rather than duplicating completion logic, and deliberately defer result-close combination.
Upstream: .workflow/items/E-002/outputs/protocol-slimming-discovery.md and parent Design contain the accepted bounded streamlining.

## Progress

- 2026-10-21T06:08:51+00:00 · worker · done
  Finding: Implemented atomic optional phase/start updates with content-only defaults and shared lifecycle protection. Focused tests passed with 68 tests and 932 assertions and the shared-tree suite passed with 582 tests and 4164 assertions at its check point.
  Artifacts: cli/src/Console/Command/PlanCommand.php, cli/src/Console/Command/DesignCommand.php, cli/tests/Console/PlanDesignStateTest.php
  Decision: none
  Next: Independently verify the frozen owned targets.

- 2026-10-21T06:17:44+00:00 · verifier · done
  Finding: All six criteria passed independent full verification with 338 real-CLI scenarios and the 693-test suite passing. Frozen hashes remained unchanged.
  Artifacts: .workflow/items/T-017/outputs/verification.md
  Decision: none
  Next: Close T-017.

- 2026-10-21T06:17:46+00:00 · closed as done
