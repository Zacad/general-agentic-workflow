---
id: T-015
kind: task
parent: E-002
title: 'Render and capture exact reviewed scope'
status: done
phase: verify
priority: 2
depends_on:
  - T-013
owns:
  - cli/src/Support/ScopeOutput.php
  - cli/src/Console/Command/ReviewCommand.php
  - cli/src/Console/Command/ApproveCommand.php
  - cli/tests/Console/ScopeOutputTest.php
artifacts:
  - cli/src/Support/ScopeOutput.php
  - cli/src/Console/Command/ReviewCommand.php
  - cli/src/Console/Command/ApproveCommand.php
  - cli/tests/Console/ScopeOutputTest.php
  - .workflow/items/T-015/outputs/verification.md
approval:
  at: '2026-10-21T04:51:18+00:00'
  by: user
  scope_hash: c2d7e9a5ccde3ae7c063d2ace969238b8deb6342c95439fc829f9cddf129c724
last_result:
  at: '2026-10-21T06:08:50+00:00'
  role: verifier
  status: done
  finding: 'All six criteria pass against unchanged frozen targets with 514 regression tests and 50 independent tests passing. Only the known test-option deprecation remains.'
  decision: null
verified_at: '2026-10-21T06:08:50+00:00'
created: '2026-10-20T19:13:13+00:00'
updated: '2026-10-21T06:08:50+00:00'
outcome: 'Review and approval preserve distinct scope values and approval output identifies the exact values and hash captured by the approved mutation.'
scope:
  in:
    - 'Small shared exact scope projection and literal output for Review and Approve'
    - 'Capture actual approved scope and hash together under the existing mutation lock'
    - 'Add Review JSON using the same scope and decomposition packet'
  out:
    - 'Changes to scopeHash identity or approval invalidation rules'
    - 'Generic rendering frameworks or changes to stored list normalization'
    - 'Changes to unrelated bounded summaries'
acceptance:
  - 'Text scope output distinguishes separate list values from comma-containing values and represents empty arrays, empty strings, null and multiline strings unambiguously'
  - 'Kind, parent, title, outcome, scope-in, scope-out, acceptance and dependencies retain exact values without truncation or Console-tag interpretation'
  - 'Review and Approve use the same small scope projection and preserve owned paths as contextual data outside the approval hash'
  - 'Approve text and JSON report the actual scope and hash captured together in the locked mutation rather than a later reload'
  - 'Review --json includes the epic and descendants with exact scope, dependency, ownership, approval and Plan/Design information'
  - 'Existing scope-hash stability and acceptance-order semantics remain unchanged'
---

## Plan

Question: how can a human and script compare the exact reviewed and approved scope?
Dimensions: literal values, list boundaries and atomic approval capture.
Sources: .workflow/items/E-002/outputs/delegation-output-discovery.md, Review/Approve and existing ItemScope tests.
Stop: adversarial literal fixtures remain distinguishable and approval returns the mutation's captured packet.
Acceptance: verify text/JSON exactness, Review JSON tree coverage and unchanged scope-hash semantics.

## Design

Files: introduce one small ScopeOutput helper consumed by Review and Approve plus a focused command test file.
Decision: use unambiguous literal field/list rendering and capture scope plus hash inside approval mutation, reusing raw JSON from delegation.
Checks: tags, Unicode, multiline/empty/null values, distinct list boundaries, descendant packets and existing scope-hash tests.
Risks: display order may change but scopeHash semantics and Item persistence must not.
Upstream: .workflow/items/E-002/outputs/delegation-output-discovery.md and parent Design are the exact-output contract.

## Progress

- 2026-10-21T05:49:09+00:00 · worker · done
  Finding: Implemented exact literal scope rendering and locked approval snapshots plus Review JSON. Full suite passed with 313 tests and 1851 assertions at its check point; independent verification remains pending.
  Artifacts: cli/src/Support/ScopeOutput.php, cli/src/Console/Command/ReviewCommand.php, cli/src/Console/Command/ApproveCommand.php, cli/tests/Console/ScopeOutputTest.php
  Decision: none
  Next: Independently verify all acceptance criteria on the frozen targets.

- 2026-10-21T06:08:50+00:00 · verifier · done
  Finding: All six criteria pass against unchanged frozen targets with 514 regression tests and 50 independent tests passing. Only the known test-option deprecation remains.
  Artifacts: .workflow/items/T-015/outputs/verification.md
  Decision: none
  Next: Close T-015.

- 2026-10-21T06:08:50+00:00 · closed as done
