---
id: T-018
kind: task
parent: E-002
title: 'Consolidate the operating protocol and public examples'
status: done
phase: verify
priority: 2
depends_on:
  - T-015
  - T-016
  - T-010
  - T-011
  - T-017
owns:
  - AGENTS.md
  - README.md
  - docs/install.md
  - .agents/roles/orchestrator.md
  - .agents/roles/explorer.md
  - .agents/roles/worker.md
  - .agents/roles/verifier.md
  - .agents/skills/workflow-cli/SKILL.md
  - .agents/skills/decompose-work/SKILL.md
  - .agents/skills/install-workflow/SKILL.md
  - .agents/skills/fresh-session-handoff/SKILL.md
  - .agents/skills/verify-outcome/SKILL.md
  - .workflow/PRD.md
  - .workflow/Architecture.md
  - cli/tests/Console/ProtocolExamplesTest.php
artifacts:
  - AGENTS.md
  - README.md
  - docs/install.md
  - .agents/roles/orchestrator.md
  - .agents/roles/explorer.md
  - .agents/roles/worker.md
  - .agents/roles/verifier.md
  - .agents/skills/workflow-cli/SKILL.md
  - .agents/skills/decompose-work/SKILL.md
  - .agents/skills/install-workflow/SKILL.md
  - .agents/skills/fresh-session-handoff/SKILL.md
  - .agents/skills/verify-outcome/SKILL.md
  - .workflow/PRD.md
  - .workflow/Architecture.md
  - cli/tests/Console/ProtocolExamplesTest.php
  - .workflow/items/T-018/outputs/verification.md
approval:
  at: '2026-10-21T04:51:18+00:00'
  by: user
  scope_hash: 2f641b554bc080bad9bde9b7b359d6a4c0219295d4183ceff29db11136481906
last_result:
  at: '2026-10-21T06:48:16+00:00'
  role: verifier
  status: done
  finding: 'All nine criteria and the Design pass; 699 tests with 5567 assertions and read-only workflow validation passed. Actual PHP 8.4.1, mbstring-absent deployment and real LLM startup remain unrun rather than claimed.'
  decision: null
verified_at: '2026-10-21T06:48:16+00:00'
created: '2026-10-20T19:13:13+00:00'
updated: '2026-10-21T06:48:22+00:00'
outcome: 'One concise canonical protocol and consistent executable examples explain staged approval, independent discovery, small work, verification exceptions, completed history and scaffold-only installation.'
scope:
  in:
    - 'Keep AGENTS canonical and shorten repeated operating rules across roles, skills and introductory docs'
    - 'Document epic-hosted independent discovery, staged child approval and one scope map in epic Design'
    - 'Make small-work and deliberate verification-exception paths explicit without new gates'
    - 'Align completion-history, command, runtime and scaffold examples with implemented behavior'
    - 'Verify isolated public examples and installed-role canonical loading'
  out:
    - 'A new default protocol document or managed host-document merger'
    - 'Changes to historical design notes, handoff comparisons or completed item records'
    - 'OpenCode schema redesign, host harness replacement or additional protocol gates'
acceptance:
  - "AGENTS is the single operating-rule source and every installed role explicitly loads the project's root AGENTS, while missing or conflicting host rules are surfaced rather than overwritten"
  - 'Independent discovery may be recorded on an epic and a separate discovery child is optional only when useful as its own deliverable'
  - 'Approval is staged epic/discovery then exact children after evidence, unchanged epic scope is not reapproved and later evidence reapproves only affected changed scopes'
  - 'Epic Design holds the sole canonical scope map and child records reference parent design rather than duplicating decomposition'
  - 'Small work uses one item, short exact approval, one-line Plan/Design and justified no-exploration where appropriate, with independent verification normally required and an explicit deliberate exception available'
  - 'Completed items remain historical and material changes or new producer/unsuccessful verifier work require an explicit new follow-up item, with no automatic reopen, successor generation or batch migration'
  - 'README quickstart and install smoke examples run in isolated roots with valid hierarchy, repository-relative artifact paths, verifier/close flow and no deletion of item history'
  - 'Runtime versus development prerequisites, safe force/manual-merge semantics, optional phase/start flags, exhaustive all output and exact role packets match implemented commands'
  - 'PRD/Architecture describe product mechanisms and limits rather than duplicate the operating loop, stale fake paths and warning-only closure claims are corrected, and historical/user-owned files remain untouched'
---

## Plan

Question: what is the smallest consistently loadable operating contract for the agreed workflow?
Dimensions: canonical rules, role/skill duplication and executable public examples.
Sources: all four .workflow/items/E-002/outputs discovery reports, the implemented child contracts and the parent Design.
Stop: every accepted policy and public example has one canonical home and no contradictory active instruction.
Acceptance: inspect canonical loading and stale language, run isolated quickstart/install examples and verify preserved historical/user-owned paths.

## Design

Files: one writer owns 14 related prose files and one focused example test, leaving generic templates, historical design notes and docs/handoff-compare.md untouched.
Decision: keep AGENTS canonical, roles short, skills procedural, README introductory and PRD/Architecture product-focused, with manual host-rule merging.
Checks: staged exact approval, optional discovery children, small work, explicit verification exceptions, done-history follow-ups, valid examples and implemented runtime/scaffold/command contracts.
Risks: this broad prose ownership is justified by one operating contract and one voice, not multiple independent writers or a new protocol source.
Upstream: .workflow/items/E-002/outputs/record-lifecycle-discovery.md, .workflow/items/E-002/outputs/delegation-output-discovery.md, .workflow/items/E-002/outputs/scaffold-discovery.md and .workflow/items/E-002/outputs/protocol-slimming-discovery.md plus parent Design.

## Progress

- 2026-10-21T06:34:23+00:00 · worker · done
  Finding: Consolidated the protocol across 15 frozen targets; owned prose decreased from 9831 to 6778 words. Focused checks passed with 6 tests and 195 assertions and the full suite passed with 699 tests and 5567 assertions; role loading is checked statically and the known runner deprecation remains.
  Artifacts: AGENTS.md, README.md, docs/install.md, .agents/roles/orchestrator.md, .agents/roles/explorer.md, .agents/roles/worker.md, .agents/roles/verifier.md, .agents/skills/workflow-cli/SKILL.md, .agents/skills/decompose-work/SKILL.md, .agents/skills/install-workflow/SKILL.md, .agents/skills/fresh-session-handoff/SKILL.md, .agents/skills/verify-outcome/SKILL.md, .workflow/PRD.md, .workflow/Architecture.md, cli/tests/Console/ProtocolExamplesTest.php
  Decision: none
  Next: Independently verify all nine criteria on the frozen documentation and example tests.

- 2026-10-21T06:48:16+00:00 · verifier · done
  Finding: All nine criteria and the Design pass; 699 tests with 5567 assertions and read-only workflow validation passed. Actual PHP 8.4.1, mbstring-absent deployment and real LLM startup remain unrun rather than claimed.
  Artifacts: .workflow/items/T-018/outputs/verification.md
  Decision: none
  Next: Close T-018 and verify epic integration.

- 2026-10-21T06:48:22+00:00 · closed as done
