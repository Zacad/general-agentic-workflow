---
standalone_reason: 'Single coherent change set from one independent review; contract wording, role/skill guidance and CLI advisories are interdependent surfaces written by one writer, so splitting would double coordination without adding an independently acceptable outcome.'
id: T-021
kind: task
parent: null
title: 'Kit fixes from prasowka-bot process review'
status: done
phase: implement
priority: 2
depends_on: []
owns:
  - AGENTS.md
  - .agents/roles/orchestrator.md
  - .agents/roles/worker.md
  - .agents/skills/verify-outcome/SKILL.md
  - .agents/skills/decompose-work/SKILL.md
  - .agents/skills/workflow-cli/SKILL.md
  - cli/src
  - cli/tests
  - cli/templates
artifacts:
  - AGENTS.md
  - .agents/roles/orchestrator.md
  - .agents/roles/worker.md
  - .agents/skills/verify-outcome/SKILL.md
  - .agents/skills/decompose-work/SKILL.md
  - .agents/skills/workflow-cli/SKILL.md
  - cli/src
  - cli/tests
  - cli/templates
  - README.md
  - .agents/roles
  - .agents/skills
approval:
  at: '2026-10-22T05:46:07+00:00'
  by: adam
  scope_hash: d1d5610229ae9fdfaf60e0aa5ff5ed3dbc343ca6c145b9661c533fbc82fc0040
  confirmation: 'proceed (user reply of 2026-10-22 to the consolidated nine-finding propositions)'
last_result:
  at: '2026-10-22T05:46:07+00:00'
  role: verifier
  status: done
  finding: "All six acceptance criteria verified independently: AGENTS.md wording complete and internally consistent (approval-hash semantics, anti-fragmentation, warnings-not-gates all preserved); role files and three skill files carry every specified bullet/rule; scratch-project exercise confirmed all six new validate advisories fire as warnings (exit 0), brief parent_design_present + decision field, review Decisions + PRD excerpt with state label, --confirmation persisted in item YAML, bare approve honestly records by=unspecified (attestation line mildly overstates on the bare case), result/decide/init nudges print, legacy template detection works. phpunit green at 718 tests / 5674 assertions with new cases per diagnostic; git diff shows additive-only CLI changes. Limits: attestation wording overstates on bare approve (cosmetic); two pre-existing out-of-scope working-tree issues observed (deleted done-T-007 artifacts docs/pilot-compare-3.md and docs/handoff-compare-3.md — NOT deleted by this item's producer; README.md edit outside owns, logged by producer with reason)."
  decision: null
verified_at: '2026-10-22T05:46:07+00:00'
created: '2026-10-22T05:05:29+00:00'
updated: '2026-10-22T05:46:20+00:00'
outcome: "The workflow kit's contract, roles, skills and CLI consistently implement the nine findings from the prasowka-bot process review (D1-D6, PRD/Architecture lifecycle placement, decomposition guidance, explicit-approval discipline), keeping all new diagnostics advisory-only and the test suite passing."
scope:
  in:
    - 'AGENTS.md wording: D1 pre-approval discovery blessing'
    - 'D2 design-section durability'
    - 'project-documents lifecycle (PRD before first-epic approval, Architecture at epic design, decide/close reconciliation)'
    - 'deliverable/step examples + bundling symmetry + boundedness test'
    - 'phase/milestone + non-functional goals guidance'
    - 'answers-are-not-approval rules'
    - 'done-with-limits clarification'
    - '.agents/roles/orchestrator.md and worker.md bullets covering the same findings'
    - '.agents/skills/verify-outcome/SKILL.md done-with-limits rule'
    - '.agents/skills/decompose-work/SKILL.md boundedness test promotion'
    - '.agents/skills/workflow-cli/SKILL.md row updates (validate, approve, decide/status, brief, review, init)'
    - 'cli/src PHP: ItemValidator advisories (explorer receipt predates approval, parent design missing, template-marker project documents, artifact outside owns), BriefCommand parent-design warning + parent_design_present + decision in compact last_result, ReadyCommand parent-design advisory, ReviewCommand decisions section + PRD goals/non-goals excerpt, ApproveCommand --confirmation + attestation echo + honest --by default, ResultCommand decide nudge, DecideCommand PRD/Architecture nudge, InitCommand fill-moment hint, template markers'
    - 'cli/tests additions for each new behavior'
    - 'phpunit suite passing'
  out:
    - 'prasowka-bot records (separate project — its PRD/Architecture fill is a direct user-approved action outside this item)'
    - 'board.html beyond CLI-generated refresh'
    - 'new CLI commands or statuses'
    - 'blocking gates or exit-code changes for any new check (all new checks are warnings)'
    - 'restructuring of existing validate/approve semantics'
acceptance:
  - '1) AGENTS.md contains the six wording changes with no contradiction to existing approval-hash or anti-fragmentation principles'
  - '2) orchestrator.md and worker.md carry the specified bullets'
  - '3) verify-outcome, decompose-work and workflow-cli SKILL.md are updated as specified'
  - '4) CLI implements the eight read-view/validator behaviors and all new checks are warnings (validate exit code unchanged for them)'
  - '5) phpunit passes with new test cases covering each new diagnostic and affordance'
  - '6) no existing command behavior regresses'
---

## Plan

Apply the already-reviewed fix backlog directly: (1) contract wording in AGENTS.md, (2) role-file bullets, (3) skill updates, (4) PHP CLI advisories + tests, (5) phpunit green. Evidence source: six+three read-only subagent analyses of the prasowka-bot run recorded in this conversation. Stop condition: acceptance criteria 1-6 met; no exploration needed - the review itself was the independent evidence pass.

## Design

Order: AGENTS.md wording first, then roles/skills mirroring it, then CLI advisories (warnings only, no gates), then tests. Key invariants to preserve: approval hashes only scope fields (Plan/Design/docs never invalidate approval); warnings are not gates; artifacts != owns semantics; verifier blinding field-based; anti-fragmentation stance (bundling symmetry, not micro-task mandates). PRD/Architecture placement: PRD before first-epic approval, Architecture at epic design, decide/close reconciliation, template marker for detection. Approval discipline: --confirmation records user's literal confirming words, --by default removed, attestation echo.

## Progress

- 2026-10-22T05:06:04+00:00 · User confirmed scope explicitly in the orchestrator conversation: after receiving the consolidated nine-finding propositions (PRD/Architecture placement, decomposition guidance, answers-are-not-approval), the user replied 'proceed' on 2026-10-22. Answers to prior review questions were not treated as approval; this log records the literal confirming word.

- 2026-10-22T05:06:09+00:00 · Re-approval after scope_out separator fix (list separator swallowed a semicolon). No substantive scope change; recorded under the same user confirmation of 2026-10-22.

- 2026-10-22T05:37:32+00:00 · worker · done
  Finding: All nine review findings implemented across the four surfaces: AGENTS.md contract wording (D1 blessing, answers-not-approval, phases/goals guidance, per-child decomposition rationale + design-section durability, new Project documents lifecycle subsection, deliverable/step examples + bundling symmetry, done-with-limits rule); orchestrator.md rewritten with the specified bullets and worker.md owns-coverage rule; verify-outcome, decompose-work and workflow-cli SKILL.md rows updated; PHP CLI implements template markers + five validate advisories (explorer-predates-approval, parent-design-missing, template PRD/Architecture via marker or legacy text, artifact-outside-owns for producer receipts, missing confirmation quote), brief parent_design_present + decision field, ready warnings, review Decisions + PRD excerpt, approve --confirmation + attestation + --by=unspecified default, result/decide/init advisories. phpunit 718 tests / 5674 assertions OK; scratch-project smoke confirmed all four validate warnings, brief warning + JSON field, and attestation echo. prasowka-bot PRD/Architecture filled separately per user direction (outside this item).
  Artifacts: AGENTS.md, .agents/roles/orchestrator.md, .agents/roles/worker.md, .agents/skills/verify-outcome/SKILL.md, .agents/skills/decompose-work/SKILL.md, .agents/skills/workflow-cli/SKILL.md, cli/src, cli/tests, cli/templates, README.md
  Decision: none
  Next: Independent verification of the frozen target against acceptance criteria 1-6

- 2026-10-22T05:37:38+00:00 · Explicit out-of-owns write surfaced per the new worker rule: README.md (not in owns) was modified by the implementation because the new template-state warning would have broken the README quickstart's asserted 'OK (0 warnings)' validate output; the edit extends the quickstart with PRD/Architecture fill steps. Reversible if the user disagrees; flagged for the verifier.

- 2026-10-22T05:46:07+00:00 · verifier · done
  Finding: All six acceptance criteria verified independently: AGENTS.md wording complete and internally consistent (approval-hash semantics, anti-fragmentation, warnings-not-gates all preserved); role files and three skill files carry every specified bullet/rule; scratch-project exercise confirmed all six new validate advisories fire as warnings (exit 0), brief parent_design_present + decision field, review Decisions + PRD excerpt with state label, --confirmation persisted in item YAML, bare approve honestly records by=unspecified (attestation line mildly overstates on the bare case), result/decide/init nudges print, legacy template detection works. phpunit green at 718 tests / 5674 assertions with new cases per diagnostic; git diff shows additive-only CLI changes. Limits: attestation wording overstates on bare approve (cosmetic); two pre-existing out-of-scope working-tree issues observed (deleted done-T-007 artifacts docs/pilot-compare-3.md and docs/handoff-compare-3.md — NOT deleted by this item's producer; README.md edit outside owns, logged by producer with reason).
  Artifacts: AGENTS.md, cli/src, cli/tests, cli/templates, .agents/roles, .agents/skills, README.md
  Decision: none
  Next: None for this item; user decision pending on the deleted T-007 artifacts

- 2026-10-22T05:46:20+00:00 · closed as done
