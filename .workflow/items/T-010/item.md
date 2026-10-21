---
id: T-010
kind: task
parent: E-002
title: 'Harden contained scaffold installation'
status: done
phase: verify
priority: 2
depends_on: []
owns:
  - cli/src/Console/Command/InstallCommand.php
  - cli/src/Store/ProjectInitializer.php
  - cli/tests/Console/InstallScaffoldTest.php
  - cli/tests/Store/ProjectInitializerSafetyTest.php
artifacts:
  - cli/src/Console/Command/InstallCommand.php
  - cli/src/Store/ProjectInitializer.php
  - cli/tests/Console/InstallScaffoldTest.php
  - cli/tests/Store/ProjectInitializerSafetyTest.php
  - .workflow/items/T-010/outputs/verification.md
approval:
  at: '2026-10-21T04:51:17+00:00'
  by: user
  scope_hash: 5db9a49536cf8725d0620724beae7dca20614a7e03784906381322a3be9470a2
last_result:
  at: '2026-10-21T05:07:47+00:00'
  role: verifier
  status: done
  finding: 'All seven criteria passed independent verification: 60 tests and 334 assertions plus 93 independent probes and 957 checks. All four frozen target hashes remained unchanged.'
  decision: null
verified_at: '2026-10-21T05:07:47+00:00'
created: '2026-10-20T19:13:13+00:00'
updated: '2026-10-21T05:07:47+00:00'
outcome: 'Scaffold installation rejects recursive destinations and unsafe collisions while preserving host rules, configuration and workflow history even when force is requested.'
scope:
  in:
    - 'Symlink-aware existing-component resolution with missing suffix support and separator-aware containment guards'
    - 'Create-only root host documents and safe default or forced kit-file collision behavior'
    - 'Initializer and board-destination protection against symlink and type collisions'
    - 'Installer guidance for runtime-only dependency bootstrap and manual merges'
  out:
    - 'Managed updates, provenance manifests or automatic document merges'
    - 'Deleting target-only files or importing source workflow records'
    - 'A transactional installer resistant to concurrent hostile filesystem replacement'
acceptance:
  - 'Equal, descendant and ancestor destinations including root are rejected before writes, including missing suffixes reached through existing symlinks and parent segments'
  - 'Dangling links and non-directory intermediates are rejected rather than lexically bypassed'
  - 'Root AGENTS, README and LICENSE are created only when absent and preserved byte-for-byte even with --force'
  - 'Ordinary kit-relative collisions skip by default and refresh only when force is explicit, without merging or deleting target-only files'
  - 'Source and destination symlinks or file-directory collisions are held or rejected without following them, including workflow initializer and board paths'
  - 'Existing host config, gitignore, PRD, Architecture, item records and outputs remain intact, source workflow history is never copied and only a safe derived board may regenerate'
  - 'Guidance describes manual AGENTS merging and composer install --no-dev for runtime, with isolated filesystem regressions covering default and force behavior'
---

## Plan

Question: how can scaffold copies remain outside the kit and preserve host authority/history?
Dimensions: component resolution, collision policy and initializer preservation.
Sources: .workflow/items/E-002/outputs/scaffold-discovery.md, InstallCommand and ProjectInitializer.
Stop: every agreed containment/collision case has a safe isolated refusal or preservation test.
Acceptance: use temporary files and real symlinks to check pre-write refusal, default/force behavior and byte-preserved host records.

## Design

Files: correct InstallCommand and ProjectInitializer with dedicated safety tests, without changing shared test infrastructure.
Decision: resolve existing components before parent traversal, reject recursive destinations and links/type collisions, preserve root documents even with force and never merge/delete history.
Checks: root/ancestor/source aliases, missing suffixes, real symlinks, force/default collisions, target-only files and initializer/board safety.
Risks: safely clean up test-owned links, avoid external-target traversal and do not claim race-proof transactions.
Upstream: .workflow/items/E-002/outputs/scaffold-discovery.md and parent Design fix runtime guidance and the scaffold-only contract.

## Progress

- 2026-10-21T04:59:26+00:00 · worker · done
  Finding: Implemented scaffold containment and host-preservation/collision protections. Focused checks passed: 57 tests and 320 assertions plus 4 existing command tests and 39 assertions; full suite remains unrun.
  Artifacts: cli/src/Console/Command/InstallCommand.php, cli/src/Store/ProjectInitializer.php, cli/tests/Console/InstallScaffoldTest.php, cli/tests/Store/ProjectInitializerSafetyTest.php
  Decision: none
  Next: Independently verify the frozen artifacts.

- 2026-10-21T05:07:47+00:00 · verifier · done
  Finding: All seven criteria passed independent verification: 60 tests and 334 assertions plus 93 independent probes and 957 checks. All four frozen target hashes remained unchanged.
  Artifacts: .workflow/items/T-010/outputs/verification.md
  Decision: none
  Next: Close T-010.

- 2026-10-21T05:07:47+00:00 · closed as done
