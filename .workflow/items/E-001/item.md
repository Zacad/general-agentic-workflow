---
id: E-001
kind: epic
parent: null
title: 'Workflow v5: plan/design flow and approval integrity'
status: done
phase: verify
priority: 1
depends_on: []
owns: []
artifacts:
  - .workflow/items/E-001/item.md
  - cli/src/Console/Command/ApproveCommand.php
  - cli/tests/Console/CommandTest.php
  - .workflow/PRD.md
  - .workflow/Architecture.md
  - docs/design-notes.md
approval:
  at: '2026-10-20T12:31:19+00:00'
  by: user
  scope_hash: d08c178b3bbefbb6c1aa1cfc753af87bab94f9eef04efcbc61013d14bb58a0ac
last_result:
  at: '2026-10-20T13:23:01+00:00'
  role: verifier
  status: done
  finding: 'Targeted recheck after the needs-decision pass: approve prints kind/parent/title plus all scope values, E-001 design maps all 13 criteria, and PRD/Architecture/design-notes describe the five-phase loop; suite passes 44 tests/155 assertions and the whole-flow smoke test passes.'
  decision: null
verified_at: '2026-10-20T13:23:01+00:00'
created: '2026-10-20T12:30:59+00:00'
updated: '2026-10-20T13:23:01+00:00'
outcome: 'The workflow kit supports Plan -> Explore -> Design -> Implement -> Verify at every level, exact-record approval, and granularity steering without new hard gates; all tests pass.'
scope:
  in:
    - 'Design phase and ## Design section'
    - 'workflow design command'
    - 'new items default to plan'
    - 'plan/design warnings'
    - 'parent design in brief'
    - 'review command'
    - 'exact approval output'
    - 'set duplicate-key fix'
    - 'item-local artifact path fix'
    - 'prompts, docs and skills'
    - tests.
  out:
    - 'Changing attempts 1/2'
    - 'rewriting the CLI architecture'
    - 'strict phase gates'
    - 'automatic decomposition'
    - 'running the travel-guide pilot inside this epic.'
acceptance:
  - 'workflow design writes ## Design'
  - 'design is a valid phase'
  - 'new items default to plan.'
  - 'set rejects duplicate list keys and accepts semicolon-separated values'
  - 'no silent overwrite.'
  - 'items/<id>/outputs/... artifact paths validate without warnings.'
  - 'brief shows plan, design and parent design'
  - 'validate warns on missing plan/design'
  - 'close warns but does not refuse.'
  - 'workflow review <epic> prints actual scope/acceptance/dependencies for all children'
  - 'approve prints actual scope values.'
  - 'Docs, role prompts and skills describe the new loop, discovery task and granularity steering.'
  - 'All existing and new PHPUnit tests pass.'
---

## Plan

Approach: implement the new loop in the CLI first, then update prompts/docs, then verify with tests.

Decomposition:
- T-001 correctness: set duplicate keys and artifact path resolution.
- T-002 design model: phase, section, command, default plan.
- T-003 brief/validate/close: show design and parent design, soft warnings.
- T-004 review/approve: exact packet and actual scope output.
- T-005 docs/prompts/skills: Plan -> Explore -> Design -> Implement -> Verify, discovery task, granularity steering.

Artifacts: CLI source, PHPUnit tests, AGENTS/README, role prompts, skills.
Checks: full PHPUnit suite; smoke test in a temp project; grep for old loop wording; verify no new hard gates.
Risks: backward compatibility of existing phases; warning noise; review packet length; docs drift.

## Progress

- 2026-10-20T13:23:01+00:00 · verifier · done
  Finding: Targeted recheck after the needs-decision pass: approve prints kind/parent/title plus all scope values, E-001 design maps all 13 criteria, and PRD/Architecture/design-notes describe the five-phase loop; suite passes 44 tests/155 assertions and the whole-flow smoke test passes.
  Artifacts: .workflow/items/E-001/item.md, cli/src/Console/Command/ApproveCommand.php, cli/tests/Console/CommandTest.php, .workflow/PRD.md, .workflow/Architecture.md, docs/design-notes.md
  Decision: none
  Next: Close E-001 and run final validation.

- 2026-10-20T13:23:01+00:00 · closed as done

## Design

Architecture: Plan is the pre-exploration approach; Explore produces evidence; Design resolves the evidence into an outline, canonical values, decisions and coverage; Implement follows the design; Verify checks acceptance and design. The CLI exposes workflow design and workflow review, shows plan/design/parent design in briefs, warns (does not gate) on missing plan/design, rejects duplicate list-field assignments, and resolves item-local artifact paths under .workflow. Canonical decisions: one writer per deliverable; one discovery/design task before multi-dimensional child framing; exact records reviewed via workflow review; approval prints actual values. Coverage: 1 design writes ## Design -> DesignCommand/tests; 2 design phase -> Phase/New/Set/tests; 3 new defaults plan -> NewCommand/tests; 4 duplicate list keys/semicolon -> SetCommand/tests; 5 no silent overwrite -> SetCommand/tests; 6 item-local artifact paths -> ItemValidator/tests; 7 brief plan/design/parent design -> BriefCommand/tests; 8 validate warnings -> ItemValidator/tests; 9 close soft design warning -> CloseCommand/tests; 10 review packet -> ReviewCommand/tests; 11 approve actual values -> ApproveCommand/tests; 12 docs/roles/skills -> AGENTS/README/.agents; 13 suite -> PHPUnit 44 tests. Risks: old items may lack design; warnings are soft by design; no travel-guide pilot yet.
