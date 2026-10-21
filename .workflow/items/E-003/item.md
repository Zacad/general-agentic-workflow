---
id: E-003
kind: epic
parent: null
title: 'Restore withdrawn guidance and complete OpenCode installation'
status: done
phase: verify
priority: 2
depends_on: []
owns: []
artifacts:
  - .workflow/items/T-019/outputs/verification.md
  - .workflow/items/T-020/outputs/verification.md
  - .workflow/items/E-003/outputs/integration-verification.md
approval:
  at: '2026-10-21T17:53:54+00:00'
  by: user
  scope_hash: 331430e7c84b0a8663bffab1b98a3e7ae43fd376f4ded7f822e985f3a3605b7c
last_result:
  at: '2026-10-21T18:13:42+00:00'
  role: verifier
  status: done
  finding: 'All five epic criteria pass: every audited document restores its unique guidance without a duplicate lifecycle, installer handoff preserves host state, and the live prasowka-bot catalog exposes four correctly moded workflow agents with the orchestrator default.'
  decision: null
verified_at: '2026-10-21T18:13:42+00:00'
created: '2026-10-21T17:51:50+00:00'
updated: '2026-10-21T18:13:43+00:00'
outcome: 'The kit retains its verified operating guidance, installations clearly hand off harness setup, and prasowka-bot exposes the four workflow agents.'
scope:
  in:
    - 'Restore unique operating guidance removed by the documentation consolidation'
    - 'Make installation handoff and tests distinguish scaffold copy from verified OpenCode integration'
    - 'Repair the installed prasowka-bot harness registration without altering unrelated settings'
  out:
    - 'Automatic overwrite or merging of existing host OpenCode configuration'
    - 'Reverting agreed lifecycle, discovery, approval, verification or scaffold policies'
    - 'New configuration framework or managed updater'
    - 'Editing completed E-002 and T-009 through T-018 records'
acceptance:
  - 'Confirmed content losses across all fourteen rewritten documents are restored in canonical or role-appropriate locations without restoring duplicate lifecycle loops'
  - 'Installer output states that OpenCode registration is a separate required step and tests assert the handoff without claiming integration completed'
  - 'prasowka-bot has active project OpenCode configuration and its scoped agent catalog exposes orchestrator primary/default and explorer, worker and verifier subagents'
  - 'Workflow validation, focused tests and the full suite pass'
  - 'Independent verification confirms the repaired guidance, handoff behavior and live prasowka-bot catalog with environment limits reported'
---

## Plan

Question: restore the unique guidance and installation handoff lost in the previous consolidation without undoing its verified fixes.
Dimensions: operator/role boundaries and method cues; installation guidance and handoff behavior; target OpenCode registration and catalog evidence.
Sources: the audited diff 3ec52c6..d9b1e6c for all fourteen documents, current CLI/source behavior, the unchanged OpenCode example, and V2 agent/config/catalog contract. No further discovery pass is needed because the removed hunks and replacements are directly inspectable.
Stop when every confirmed loss has a deliberate canonical location, installer output gives an actionable harness handoff, and target catalog checks are executable.
Acceptance mapping: T-019 covers restored operating knowledge; T-020 covers installation behavior and tests; epic verification covers the live prasowka-bot catalog.
Checks: compare every deleted hunk, run focused and full tests, validate records, inspect installed target, then independent review of canonical loading and live agent availability.

## Design

### Direction
Restore only unique facts and operating cues removed by consolidation; keep the verified lifecycle and one canonical AGENTS source. Treat harness registration as an installation step, not something the scaffold already accomplished.

### Scope map and coverage
- T-019 (restore guidance): AGENTS, roles, CLI/decomposition skills, PRD and Architecture. Covers epic criterion 1 and supports criteria 4-5.
- T-020 (installation handoff): README, install guide/skill, InstallCommand output and focused tests. Covers criteria 2 and 4.
- Operational target repair and catalog evidence are completed after T-020; epic criterion 3 and final criterion cover integration.

### Integration contracts
T-019 must not add a second lifecycle. Every restoration is placed in its owning document and checked against the deleted hunk. T-020 keeps scaffold/manual-merge semantics: no automatic host config creation by installer, but explicit clean-project and merge guidance. Live target registration uses reviewed project-local V2 config; do not copy secrets or unrelated settings.

### Checks and rationale
Two sibling tasks are independent: prose guidance can be accepted without installer output, and installer handoff without writing the target. No subtasks or new discovery pass are useful because the audits and source diff already locate the losses. Final verification rechecks all fourteen deleted-hunk areas, focused/full tests, workflow validation, and the target agent catalog.

Upstream evidence: git diff 3ec52c6..d9b1e6c, current docs/opencode.example.json and OpenCode V2 config/agent contract.

## Progress

- 2026-10-21T18:09:49+00:00 · orchestrator · done
  Finding: Both restoration tasks are independently verified and closed. prasowka-bot was refreshed without losing its config or E-001 record, and live catalog checks show the orchestrator primary/default plus all three subagents.
  Artifacts: .workflow/items/T-019/outputs/verification.md, .workflow/items/T-020/outputs/verification.md
  Decision: none
  Next: Independently verify the epic outcome and live target against all acceptance criteria.

- 2026-10-21T18:13:42+00:00 · verifier · done
  Finding: All five epic criteria pass: every audited document restores its unique guidance without a duplicate lifecycle, installer handoff preserves host state, and the live prasowka-bot catalog exposes four correctly moded workflow agents with the orchestrator default.
  Artifacts: .workflow/items/E-003/outputs/integration-verification.md
  Decision: none
  Next: Close E-003.

- 2026-10-21T18:13:43+00:00 · closed as done
