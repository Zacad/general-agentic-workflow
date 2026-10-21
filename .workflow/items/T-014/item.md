---
id: T-014
kind: task
parent: E-002
title: 'Unify completion and preserve completed history'
status: done
phase: verify
priority: 2
depends_on:
  - T-012
owns:
  - cli/src/Domain/ItemLifecycle.php
  - cli/src/Store/ItemStore.php
  - cli/src/Validate/ItemValidator.php
  - cli/src/Console/Command/ResultCommand.php
  - cli/src/Console/Command/CloseCommand.php
  - cli/tests/Domain/ItemLifecycleTest.php
  - cli/tests/Store/LifecycleWriteTest.php
  - cli/tests/Console/LifecycleCommandTest.php
  - cli/tests/Console/CommandTest.php
artifacts:
  - cli/src/Domain/ItemLifecycle.php
  - cli/src/Store/ItemStore.php
  - cli/src/Validate/ItemValidator.php
  - cli/src/Console/Command/ResultCommand.php
  - cli/src/Console/Command/CloseCommand.php
  - cli/tests/Domain/ItemLifecycleTest.php
  - cli/tests/Store/LifecycleWriteTest.php
  - cli/tests/Console/LifecycleCommandTest.php
  - cli/tests/Console/CommandTest.php
  - .workflow/items/T-014/outputs/verification.md
approval:
  at: '2026-10-21T04:51:18+00:00'
  by: user
  scope_hash: 16f87eb2edc1fd33a2be7c20b933ab46cf6c8f9fcd67430431dbf0567b9d981b
last_result:
  at: '2026-10-21T05:57:49+00:00'
  role: verifier
  status: done
  finding: 'All eight criteria passed independent verification: 255 additional probes and the full 514-test suite passed. Frozen targets and completed/user records remained unchanged.'
  decision: null
verified_at: '2026-10-21T05:57:49+00:00'
created: '2026-10-20T19:13:13+00:00'
updated: '2026-10-21T05:57:49+00:00'
outcome: 'All supported completion routes require current successful verification or a deliberate exception, while material new work on completed items requires an explicit follow-up item.'
scope:
  in:
    - 'One shared completion and verification-invalidation rule at the store mutation boundary'
    - 'Clear verification for tracked open-item target changes and producer or unsuccessful verifier receipts'
    - 'Refuse material completed-item changes and reopening with explicit follow-up instructions'
    - 'Keep a deliberate recorded close-force exception without fabricated verifier success'
  out:
    - 'Automatic reopening, successor creation or batch migration of completed records'
    - 'New freshness hashes, versions, snapshots or artifact content scanning'
    - 'Mandatory phase order or additional approval, Plan or Design gates'
    - 'Combining result and close into a new command route'
acceptance:
  - 'After verifier success on an open item, scope, Plan, Design, Decisions, ownership or artifact-reference changes clear verified_at without changing scope-approval semantics'
  - 'New worker or explorer receipts and non-done verifier receipts clear prior verification on open items, including a failed verifier after earlier success'
  - 'Successful independent verifier receipts establish verification for their resulting frozen target, while routine phase, status, priority and non-material progress changes alone do not invalidate it'
  - 'Close, set status=done and store create/save/mutate cannot bypass the shared completion rule with absent or stale verification'
  - 'Explicit close --force records an unverified exception without inventing successful verification and cannot override structural or completed-history protection'
  - 'Material scope, Plan, Design, Decisions, owned-path or artifact-reference changes, reopening, new producer receipts and unsuccessful verifier receipts on done items fail before persistence with an explicit instruction to create a new follow-up item'
  - 'Rejected completed-item changes preserve original bytes, create no successor and do not touch existing done history, while non-material notes or priority corrections are not blanket-blocked'
  - 'Phase never infers status and same-path external artifact changes are documented as freeze/receipt-based rather than automatically detected'
---

## Plan

Question: how can one verification rule cover all writes while keeping completed work historical?
Dimensions: open-target invalidation, completion routes and explicit follow-up refusal.
Sources: .workflow/items/E-002/outputs/record-lifecycle-discovery.md, the user's done-history decision and the structure persistence seam.
Stop: each completion bypass and done-history mutation has a refusal or deliberate-exception regression.
Acceptance: exercise pure lifecycle cases and CLI/store rollback with current success, failed verifier, target changes and forced closure.

## Design

Files: add ItemLifecycle at the existing store seam, align Result/Close and validator diagnostics, and be the sole writer of existing CommandTest.
Decision: compare tracked before/after fields and receipt intent, clear existing verification on open-target changes, reject material done changes and require explicit follow-up creation.
Checks: all completion routes, successful versus unsuccessful receipts, artifact-reference changes, force exceptions, byte-preserving refusal and no phase-driven status.
Risks: no automatic same-path external change detection or new freshness metadata, and use the same writer as structure and subsequent coordination.
Upstream: .workflow/items/E-002/outputs/record-lifecycle-discovery.md and parent Design supersede earlier auto-reopen proposals.

## Progress

- 2026-10-21T05:49:09+00:00 · worker · done
  Finding: Implemented shared completion/invalidation rules and explicit-follow-up protection for completed work. Focused tests pass with 230 tests and 1520 assertions and the full suite passes with 514 tests and 3232 assertions; same-path artifact changes remain convention-based.
  Artifacts: cli/src/Domain/ItemLifecycle.php, cli/src/Store/ItemStore.php, cli/src/Validate/ItemValidator.php, cli/src/Console/Command/ResultCommand.php, cli/src/Console/Command/CloseCommand.php, cli/tests/Domain/ItemLifecycleTest.php, cli/tests/Store/LifecycleWriteTest.php, cli/tests/Console/LifecycleCommandTest.php, cli/tests/Console/CommandTest.php
  Decision: none
  Next: Independently verify all acceptance criteria on the frozen targets.

- 2026-10-21T05:57:49+00:00 · verifier · done
  Finding: All eight criteria passed independent verification: 255 additional probes and the full 514-test suite passed. Frozen targets and completed/user records remained unchanged.
  Artifacts: .workflow/items/T-014/outputs/verification.md
  Decision: none
  Next: Close T-014.

- 2026-10-21T05:57:49+00:00 · closed as done
