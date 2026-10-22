---
standalone_reason: 'Single focused fix from the prasowka-bot reinstall failure: the installer never creates opencode.json and never verifies agent registration, so fresh installs are functionally incomplete; one deliverable, one writer, no exploration needed beyond the root-cause analysis already recorded.'
id: T-022
kind: task
parent: null
title: 'Installer creates and verifies OpenCode agent configuration'
status: done
phase: plan
priority: 2
depends_on: []
owns:
  - cli/src
  - cli/tests
  - docs/install.md
  - .agents/skills/install-workflow/SKILL.md
  - .agents/skills/workflow-cli/SKILL.md
artifacts:
  - cli/src
  - cli/tests
  - docs/install.md
  - .agents/skills/install-workflow/SKILL.md
  - .agents/skills/workflow-cli/SKILL.md
  - cli/src/Console/Command/InstallCommand.php
  - cli/tests/Console/InstallScaffoldTest.php
  - cli/tests/Console/InstallHandoffTest.php
approval:
  at: '2026-10-22T08:27:30+00:00'
  by: adam
  scope_hash: 67d7c73adb19fc92120090eb165f445c688be0c9b60c696b16211d36706e3cbe
  confirmation: 'proceed (user reply of 2026-10-22 to the presented root-cause + fix plan for the installer/OpenCode gap)'
last_result:
  at: '2026-10-22T08:46:28+00:00'
  role: verifier
  status: done
  finding: 'All acceptance criteria verified independently with the real kit binary and real opencode v2.1.2: clean install creates a byte-identical opencode.json with all four workflow-* agents (cmp-verified); host configs preserved byte-identically with/without --force including a secrets-containing config with zero leakage and per-agent merge guidance; catalog check reports registered agents for the target and skips gracefully without the CLI; unparseable configs warn untouched; exit 0 in every case; docs and skill rows match with no stale language; phpunit 723/5695 green with new tests for all four config cases plus both catalog paths. Limit: catalog registration proven, not a live LLM session.'
  decision: null
verified_at: '2026-10-22T08:46:28+00:00'
created: '2026-10-22T08:25:58+00:00'
updated: '2026-10-22T08:46:28+00:00'
outcome: 'A clean-project workflow install creates the OpenCode agent configuration (opencode.json with the four workflow-* agents) itself, verifies agent registration through the target-scoped catalog when the opencode CLI is available, and keeps existing host configs preserved with precise merge guidance — making fresh installs functionally complete without manual steps.'
scope:
  in:
    - 'InstallCommand: clean project (no opencode.json) → create it from docs/opencode.example.json automatically with a created-report'
    - 'existing opencode.json → never overwritten, parsed to check all four workflow-* agent entries (workflow-orchestrator, workflow-explorer, workflow-worker, workflow-verifier), confirmation message when present, exact merge guidance + warning when missing'
    - 'best-effort post-install verification via opencode api agent.list scoped to the target when the opencode CLI exists, reporting registered workflow-* agents (catalog proof only, graceful skip, non-gating)'
    - 'updated Next-steps output'
    - 'docs/install.md, install-workflow SKILL.md and workflow-cli SKILL.md install row updated to match'
    - 'InstallScaffoldTest cases for clean-created, existing-preserved, agents-missing-warning, agents-present-confirmed'
    - 'phpunit green'
  out:
    - 'modifying or merging content into an existing host opencode.json'
    - 'changing agent definitions in the example config'
    - 'gating install success on opencode CLI presence or catalog results (verification stays advisory)'
    - 'OpenCode app-side behavior'
    - 'other harness integrations'
acceptance:
  - '1) clean-project install creates opencode.json with all four workflow-* agent entries from the kit example without user action'
  - '2) existing opencode.json is never modified and gets precise per-agent merge guidance when entries are missing'
  - '3) install output reports catalog verification results when the opencode CLI is available and skips gracefully when not'
  - '4) install still exits 0 in all these cases'
  - '5) documentation and skill rows match implemented behavior'
  - '6) phpunit green with new tests covering the four cases'
  - '7) fresh prasowka-bot reinstall (after kit push) shows opencode.json created and the four workflow-* agents in the target-scoped catalog'
---

## Plan

Root cause already established in the prasowka-bot reinstall session: InstallCommand copies the scaffold but only prints manual instructions for OpenCode registration (REQUIRED, MANUAL, UNVERIFIED), so the agent harness is never configured. Fix in place: automate the clean-project case, verify the existing-config case, best-effort catalog check. No further exploration needed.

## Design

Clean project = opencode.json absent: copy docs/opencode.example.json verbatim (atomic, created-report, failure is a warning not fatal). Existing config: JSON-decode, check agents for the four workflow-* keys, compare modes minimally; never write. Catalog check: run opencode api agent.list --param location[directory]=<target> only if opencode is on PATH, parse workflow-* IDs, report found/missing; advisory. Preserve scaffold-philosophy: add-only, warnings not gates, exit 0. Tests: four cases in InstallScaffoldTest with a fake opencode shim on PATH for the catalog case where needed.

## Progress

- 2026-10-22T08:41:36+00:00 · worker · done
  Finding: Root cause (installer never creates opencode.json; registration printed as REQUIRED/MANUAL/UNVERIFIED) fixed: clean projects now get opencode.json created automatically from docs/opencode.example.json; existing configs are parsed and report exactly which workflow-* agents are missing (never overwritten, no content printed); the installer verifies the target-scoped agent catalog when opencode is on PATH (graceful skip otherwise, advisory only); Next-steps text, docs/install.md, install-workflow and workflow-cli SKILL.md updated to the new contract. Five new InstallScaffoldTest cases (clean-created, all-agents-confirmed, missing-agents-warned, unparseable-warns, catalog-ok/missing via PATH-shimmed runner) and the two handoff tests updated to the new contract. phpunit 723 tests / 5695 assertions OK.
  Artifacts: cli/src, cli/tests, docs/install.md, .agents/skills/install-workflow/SKILL.md, .agents/skills/workflow-cli/SKILL.md
  Decision: none
  Next: Independent verification against acceptance 1-6, then fresh prasowka-bot reinstall for acceptance 7

- 2026-10-22T08:46:28+00:00 · verifier · done
  Finding: All acceptance criteria verified independently with the real kit binary and real opencode v2.1.2: clean install creates a byte-identical opencode.json with all four workflow-* agents (cmp-verified); host configs preserved byte-identically with/without --force including a secrets-containing config with zero leakage and per-agent merge guidance; catalog check reports registered agents for the target and skips gracefully without the CLI; unparseable configs warn untouched; exit 0 in every case; docs and skill rows match with no stale language; phpunit 723/5695 green with new tests for all four config cases plus both catalog paths. Limit: catalog registration proven, not a live LLM session.
  Artifacts: cli/src/Console/Command/InstallCommand.php, cli/tests/Console/InstallScaffoldTest.php, cli/tests/Console/InstallHandoffTest.php, docs/install.md, .agents/skills/install-workflow/SKILL.md, .agents/skills/workflow-cli/SKILL.md
  Decision: none
  Next: Fresh prasowka-bot reinstall (operational step for acceptance 7)

- 2026-10-22T08:46:28+00:00 · closed as done
