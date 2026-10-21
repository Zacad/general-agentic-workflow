---
id: T-016
kind: task
parent: E-002
title: 'Make coordination projections complete and path-consistent'
status: done
phase: verify
priority: 2
depends_on:
  - T-014
owns:
  - cli/src/Console/Command/StatusCommand.php
  - cli/src/Validate/ItemValidator.php
  - cli/src/Read/ProjectView.php
  - cli/tests/Console/CoordinationProjectionTest.php
  - cli/tests/Validate/OwnedPathTest.php
artifacts:
  - cli/src/Console/Command/StatusCommand.php
  - cli/src/Validate/ItemValidator.php
  - cli/src/Read/ProjectView.php
  - cli/tests/Console/CoordinationProjectionTest.php
  - cli/tests/Validate/OwnedPathTest.php
  - .workflow/items/T-016/outputs/verification.md
approval:
  at: '2026-10-21T04:51:18+00:00'
  by: user
  scope_hash: d2dd3a26b016f1803d7634fd8269c25fb32cd0d54c227ab6febd9b3c2368d2ca
last_result:
  at: '2026-10-21T06:16:24+00:00'
  role: verifier
  status: done
  finding: 'All six criteria passed with frozen target hashes unchanged. Independent CLI/path probes passed 1735 checks and the full suite passed 693 tests with one test-runner deprecation.'
  decision: null
verified_at: '2026-10-21T06:16:24+00:00'
created: '2026-10-20T19:13:13+00:00'
updated: '2026-10-21T06:16:26+00:00'
outcome: 'Requested full status exposes every item and ready/validate agree on overlapping literal owned paths while default coordination reads stay bounded.'
scope:
  in:
    - 'Remove every status section cap only when --all is requested'
    - 'Include done and cancelled groups in status --all JSON'
    - 'One shared lexical owned-path comparator used by ready and validate'
  out:
    - 'Globs, realpath, symlink identity or filesystem-existence matching for ownership'
    - 'Stored owned-path rewrites or new ownership gates'
    - 'Changing default JSON behavior or introducing pagination'
acceptance:
  - 'Default text status keeps its current bounded section limits'
  - 'Status --all lists every draft, active, ready, waiting, decision and terminal item without hidden section caps'
  - 'Status --all JSON includes done and cancelled items as well as all existing working-set groups'
  - 'Equivalent slash, dot-segment and repeated-separator paths report overlap in both ready and validate even when paths do not yet exist'
  - 'Project-root ownership is distinct from empty ownership and component boundaries prevent src/a from overlapping src/ab'
  - 'Stored paths remain unchanged and dependency-ordered ownership behavior is preserved'
---

## Plan

Question: do coordination projections expose the requested working set and consistent overlap evidence?
Dimensions: bounded versus all status and lexical owned-path equivalence.
Sources: .workflow/items/E-002/outputs/delegation-output-discovery.md, StatusCommand, ItemValidator and ProjectView.
Stop: every section exceeds its default cap in fixtures and every agreed lexical alias has a table case.
Acceptance: compare all/default text and JSON plus ready/validate overlap findings without rewriting stored paths.

## Design

Files: adjust StatusCommand and the existing shared comparator in ItemValidator, keep ProjectView using it and add focused projection/path tests.
Decision: treat completeness and lexical overlap as one accurate coordination-read outcome, with no filesystem identity or new gates.
Checks: all status groups, default caps, terminal JSON, root versus empty ownership, component boundaries and future-path aliases.
Risks: lexical ownership is intentionally different from installer resolution, and the same writer must follow structure/lifecycle for ItemValidator.
Upstream: .workflow/items/E-002/outputs/delegation-output-discovery.md and parent Design define projection limits.

## Progress

- 2026-10-21T06:08:50+00:00 · worker · done
  Finding: Implemented complete status --all and consistent lexical dependency-aware ownership comparisons. Focused tests passed with 111 tests and 1208 assertions and full suite passed with 693 tests and 5372 assertions.
  Artifacts: cli/src/Console/Command/StatusCommand.php, cli/src/Validate/ItemValidator.php, cli/src/Read/ProjectView.php, cli/tests/Console/CoordinationProjectionTest.php, cli/tests/Validate/OwnedPathTest.php
  Decision: none
  Next: Independently verify the frozen owned targets.

- 2026-10-21T06:16:24+00:00 · verifier · done
  Finding: All six criteria passed with frozen target hashes unchanged. Independent CLI/path probes passed 1735 checks and the full suite passed 693 tests with one test-runner deprecation.
  Artifacts: .workflow/items/T-016/outputs/verification.md
  Decision: none
  Next: Close T-016.

- 2026-10-21T06:16:26+00:00 · closed as done
