---
id: T-019
kind: task
parent: E-003
title: 'Restore removed operating guidance'
status: done
phase: verify
priority: 2
depends_on: []
owns:
  - AGENTS.md
  - .agents/roles/orchestrator.md
  - .agents/roles/explorer.md
  - .agents/roles/worker.md
  - .agents/roles/verifier.md
  - .agents/skills/workflow-cli/SKILL.md
  - .agents/skills/decompose-work/SKILL.md
  - .workflow/PRD.md
  - .workflow/Architecture.md
artifacts:
  - AGENTS.md
  - .agents/roles/explorer.md
  - .agents/roles/orchestrator.md
  - .agents/roles/worker.md
  - .agents/skills/workflow-cli/SKILL.md
  - .agents/skills/decompose-work/SKILL.md
  - .workflow/PRD.md
  - .workflow/Architecture.md
  - .workflow/items/T-019/outputs/verification.md
approval:
  at: '2026-10-21T17:53:54+00:00'
  by: user
  scope_hash: 67bb64b67fcd2a5ef66f91aa05cfe2c558e75715bd2cb8cc7ad4dd8a88aac4d7
last_result:
  at: '2026-10-21T18:02:03+00:00'
  role: verifier
  status: done
  finding: 'All six criteria pass against frozen targets and deleted hunks; validation is clean, focused tests pass 65/690 and the full suite passes 702/5608. Residual wording was independently assessed as non-material.'
  decision: null
verified_at: '2026-10-21T18:02:03+00:00'
created: '2026-10-21T17:52:01+00:00'
updated: '2026-10-21T18:02:03+00:00'
outcome: 'Operators and roles regain the unique boundaries, method cues and product constraints removed by the consolidation without duplicating canonical lifecycle rules.'
scope:
  in:
    - 'Restore explorer source-edit and design-decision prohibitions plus revision checks'
    - 'Restore explicit skill/context loading, project-fact reconciliation, worker cache exclusion and new-item phase default'
    - 'Restore product exclusions, lightweight CLI architecture constraints and advisory decomposition sizing with standalone-task fallback'
  out:
    - 'Duplicate the canonical lifecycle loop across prompts'
    - 'Revert agreed approval, discovery, completion or verification policies'
    - 'Edit installation guidance or target workspace'
acceptance:
  - 'Explorer prompt again forbids editing sources or deciding design and requires checking revision before review'
  - 'Roles again select relevant skills by context and require changed facts to reconcile into PRD or Architecture with worker cache noise excluded'
  - 'PRD restores the explicit non-goals and Architecture retains the no DI, bundle or HTTP-kernel boundary'
  - 'Decomposition restores focused-session, edit-volume, second-draft and standalone-task fallback guidance as advisory only'
  - 'Canonical lifecycle remains single-source and no removed approved policy returns'
  - 'workflow validate and focused plus full tests pass'
---

## Plan

Question: which unique operating guidance did the consolidation remove and where should it return?
Dimensions: role boundaries, context/skill loading, project-fact reconciliation, exclusions/architecture constraints, sizing cues.
Sources: deleted hunks in 3ec52c6..d9b1e6c for nine owned documents and current canonical AGENTS.
No new exploration is needed; the audit enumerated every loss against the actual diff. Restore in the owning document, not as another lifecycle copy.
Stop when each confirmed loss has an authoritative, actionable location.
Checks: diff review against every deleted hunk, reference/path validity, workflow validate, focused and full tests.

## Design

Artifact: small restorations in each owning document, no new protocol file.
Explorer: restore prohibitions on editing source or deciding design, plus a revision-check before review.
Context: restore explicit selection of relevant skills by task and reconciliation of changed project facts into PRD/Architecture; worker restores cache/build-noise exclusion.
CLI: document that new starts in phase plan, and restore the small context/skill loading cue where it belonged.
Product: restore the omitted feature exclusions in PRD. Architecture: restore the lightweight no-DI, no-bundles, no-HTTP-kernel boundary; keep current mechanism descriptions.
Decomposition: restore focused-session, edit-volume and second-draft sizing signals and standalone-task fallback as advisory, not gates.
Guard: keep one canonical lifecycle in AGENTS; re-read each deleted hunk and do not reintroduce accepted policies or duplicate loops.
Checks: diff each restored statement against source behaviour, run workflow validate and focused plus full tests.
Upstream: 3ec52c6..d9b1e6c differences and the current AGENTS canonical contract.

## Progress

- 2026-10-21T17:57:29+00:00 · worker · done
  Finding: Restored the confirmed role boundaries, context loading, fact reconciliation, cache exclusion, phase default, product exclusions, architecture constraint and advisory sizing cues. Focused and full suites pass; a few residual hunks still need independent materiality review.
  Artifacts: AGENTS.md, .agents/roles/explorer.md, .agents/roles/orchestrator.md, .agents/roles/worker.md, .agents/skills/workflow-cli/SKILL.md, .agents/skills/decompose-work/SKILL.md, .workflow/PRD.md, .workflow/Architecture.md
  Decision: none
  Next: Independently verify every deleted hunk and assess residual materiality.

- 2026-10-21T18:02:03+00:00 · verifier · done
  Finding: All six criteria pass against frozen targets and deleted hunks; validation is clean, focused tests pass 65/690 and the full suite passes 702/5608. Residual wording was independently assessed as non-material.
  Artifacts: .workflow/items/T-019/outputs/verification.md
  Decision: none
  Next: Close T-019.

- 2026-10-21T18:02:03+00:00 · closed as done
