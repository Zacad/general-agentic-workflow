---
id: T-020
kind: task
parent: E-003
title: 'Make installation handoff complete and truthful'
status: done
phase: verify
priority: 2
depends_on: []
owns:
  - README.md
  - docs/install.md
  - .agents/skills/install-workflow/SKILL.md
  - cli/src/Console/Command/InstallCommand.php
  - cli/tests/Console/InstallScaffoldTest.php
  - cli/tests/Console/InstallHandoffTest.php
artifacts:
  - README.md
  - docs/install.md
  - .agents/skills/install-workflow/SKILL.md
  - cli/src/Console/Command/InstallCommand.php
  - cli/tests/Console/InstallScaffoldTest.php
  - cli/tests/Console/InstallHandoffTest.php
  - .workflow/items/T-020/outputs/verification.md
approval:
  at: '2026-10-21T17:53:54+00:00'
  by: user
  scope_hash: 27dedbd62511e41ec6005abe0ccc94d209073e8db43815326c2b3b6c872759a2
last_result:
  at: '2026-10-21T18:09:26+00:00'
  role: verifier
  status: done
  finding: 'All five criteria pass: clean and existing registration paths are actionable, installer output is scaffold-only with registration required/manual/unverified, and probes preserved host files. Focused 36/255 and full 702/5608 checks pass; live-session proof remains unrun.'
  decision: null
verified_at: '2026-10-21T18:09:26+00:00'
created: '2026-10-21T17:52:03+00:00'
updated: '2026-10-21T18:09:27+00:00'
outcome: 'Installation guidance and output clearly distinguish a scaffold copy from a usable harness integration and direct verified project registration without overwriting existing configuration.'
scope:
  in:
    - 'Restore clean-project and existing-configuration installation guidance, model/default selection, skill discovery, CLI PATH and board refresh'
    - 'Make install output explicitly separate scaffold copy from OpenCode integration verification and give actionable registration next steps'
    - 'Add focused installer-output and guidance regressions plus README link'
  out:
    - 'Automatically creating or overwriting host OpenCode configuration from the installer'
    - 'Managed merging, new installer framework or automatic provider setup'
    - 'Editing the target workspace inside installer tests'
acceptance:
  - 'Install guide clearly covers reviewed clean-project registration from the example and existing-configuration manual merge without overwrite'
  - 'Guide covers four agent names and modes, default selection, reload and scoped catalog verification using OpenCode V2 semantics'
  - 'Installer output clearly states scaffold-only status and that harness registration remains required and unverified'
  - 'Focused tests assert the handoff wording and preservation behavior'
  - 'README links to actionable installation guidance and focused plus full tests pass'
---

## Plan

Question: how should installation honestly hand off the OpenCode step and restore usable setup guidance?
Dimensions: clean vs existing configuration, V2 agent registration/catalog check, installer output, regression coverage.
Sources: installation hunks in 3ec52c6..d9b1e6c, current InstallCommand output, unchanged docs/opencode.example.json, OpenCode V2 agent/config/docs evidence.
No further exploration needed; prasowka-bot observation confirms missing active config and prior installs confirm the example was copied manually.
Stop when clean and existing paths are actionable, output cannot imply integration succeeded, and tests cover the handoff.
Checks: wording inspection, output behavior, preservation tests, focused and full suite.

## Design

Artifact: concise install guide/skill/README guidance plus truthful installer output and focused tests.
Clean project: review then copy the shipped OpenCode V2 example to root opencode.json or equivalent; register orchestrator as primary/default, explorer/worker/verifier as subagents, with role-file references relative to config.
Existing project: preserve host configuration and report reviewed manual merge; never overwrite.
Verification: reload OpenCode and query the target-scoped V2 agent catalog for all four names, modes and default; distinguish this from workflow validate.
Runtime/path: retain composer install --no-dev, target CLI PATH or absolute binary, and board refresh guidance.
Installer output: call it a scaffold copy and list registration as a required unverified next step; keep the proof commands and existing preservation guarantees.
Tests: assert handoff wording and preservation behavior in focused tests; keep README as a short pointer.
Risks: do not turn the installer into a config generator/merger, and do not use stale V1 syntax or paid LLM calls as proof.
Upstream: installation deletions in 3ec52c6..d9b1e6c, docs/opencode.example.json and current InstallCommand tests.

## Progress

- 2026-10-21T18:02:03+00:00 · worker · done
  Finding: Restored the install guide, skill and README guidance and made installer output report scaffold-only status with OpenCode registration required, manual and unverified. Focused 36/255 and full 702/5608 checks pass; live LLM startup remains out of scope.
  Artifacts: README.md, docs/install.md, .agents/skills/install-workflow/SKILL.md, cli/src/Console/Command/InstallCommand.php, cli/tests/Console/InstallScaffoldTest.php, cli/tests/Console/InstallHandoffTest.php
  Decision: none
  Next: Independently verify the frozen installation handoff.

- 2026-10-21T18:09:26+00:00 · verifier · done
  Finding: All five criteria pass: clean and existing registration paths are actionable, installer output is scaffold-only with registration required/manual/unverified, and probes preserved host files. Focused 36/255 and full 702/5608 checks pass; live-session proof remains unrun.
  Artifacts: .workflow/items/T-020/outputs/verification.md
  Decision: none
  Next: Close T-020.

- 2026-10-21T18:09:27+00:00 · closed as done
