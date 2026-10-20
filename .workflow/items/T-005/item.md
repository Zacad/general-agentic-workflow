---
id: T-005
kind: task
parent: E-001
title: 'Document the new loop and granularity steering'
status: done
phase: verify
priority: 3
depends_on:
  - T-004
owns:
  - AGENTS.md
  - README.md
  - .agents/roles/orchestrator.md
  - .agents/roles/explorer.md
  - .agents/roles/worker.md
  - .agents/roles/verifier.md
  - .agents/skills/workflow-cli/SKILL.md
  - .agents/skills/decompose-work/SKILL.md
  - .agents/skills/verify-outcome/SKILL.md
  - .agents/skills/fresh-session-handoff/SKILL.md
artifacts:
  - AGENTS.md
  - README.md
  - .agents/roles/orchestrator.md
  - .agents/roles/explorer.md
  - .agents/roles/worker.md
  - .agents/roles/verifier.md
  - .agents/skills/workflow-cli/SKILL.md
  - .agents/skills/decompose-work/SKILL.md
  - .agents/skills/verify-outcome/SKILL.md
  - .agents/skills/fresh-session-handoff/SKILL.md
approval:
  at: '2026-10-20T12:31:33+00:00'
  by: user
  scope_hash: dc0f31e6f4b33a217d440b95a0c54f69ec58648023a6a9d6d01bc55a45c89b32
last_result:
  at: '2026-10-20T13:06:39+00:00'
  role: verifier
  status: done
  finding: 'All acceptance criteria pass: docs describe the new loop, design/review, discovery task, exact-record approval and granularity steering; role briefs match the CLI; no stale old-loop wording; suite passes 44 tests.'
  decision: null
verified_at: '2026-10-20T13:06:39+00:00'
created: '2026-10-20T12:30:59+00:00'
updated: '2026-10-20T13:06:39+00:00'
outcome: 'AGENTS.md, README, role prompts and skills describe Plan -> Explore -> Design -> Implement -> Verify, the discovery task and granularity steering.'
scope:
  in:
    - 'AGENTS.md loop and task sizing'
    - README
    - 'orchestrator/explorer/worker/verifier prompts'
    - 'workflow-cli, decompose-work, verify-outcome and fresh-session-handoff skills.'
  out:
    - 'Code changes'
    - 'the travel-guide pilot.'
acceptance:
  - 'The new loop and phase semantics are documented in AGENTS.md and README.'
  - 'The orchestrator prompt requires a discovery task, exact-record review and decomposition rationale.'
  - 'Explorer, worker and verifier prompts use plan and design correctly.'
  - 'Skills mention workflow design and workflow review.'
---

## Plan

Approach: rewrite the loop and task-sizing guidance; update roles and skills for plan/explore/design/implement/verify, discovery and granularity steering.

Files: AGENTS.md, README.md, four role prompts, four skills.
Checks: grep for the old Frame -> Explore -> Plan wording; every role mentions its input and output phase; skills mention design and review.
Risks: docs drift; keep the guidance short and operational.

## Progress

- 2026-10-20T13:04:50+00:00 · worker · done
  Finding: Updated AGENTS, README, four role prompts and four skills for the new loop, design/review commands, discovery task, exact-record approval and granularity steering; no stale old-loop wording found.
  Artifacts: AGENTS.md, README.md, .agents/roles/orchestrator.md, .agents/roles/explorer.md, .agents/roles/worker.md, .agents/roles/verifier.md, .agents/skills/workflow-cli/SKILL.md, .agents/skills/decompose-work/SKILL.md, .agents/skills/verify-outcome/SKILL.md, .agents/skills/fresh-session-handoff/SKILL.md
  Decision: none
  Next: Independent verification of T-005.

- 2026-10-20T13:06:39+00:00 · verifier · done
  Finding: All acceptance criteria pass: docs describe the new loop, design/review, discovery task, exact-record approval and granularity steering; role briefs match the CLI; no stale old-loop wording; suite passes 44 tests.
  Artifacts: AGENTS.md, README.md, .agents/roles/orchestrator.md, .agents/roles/explorer.md, .agents/roles/worker.md, .agents/roles/verifier.md, .agents/skills/workflow-cli/SKILL.md, .agents/skills/decompose-work/SKILL.md, .agents/skills/verify-outcome/SKILL.md, .agents/skills/fresh-session-handoff/SKILL.md
  Decision: none
  Next: Close T-005 and verify E-001.

- 2026-10-20T13:06:39+00:00 · closed as done

## Design

Documentation design: AGENTS and README describe Plan -> Explore -> Design -> Implement -> Verify, design/review commands, the discovery/design task, exact-record approval and granularity steering. Role prompts describe the plan/design inputs and outputs for each role; skills cover design, review, full-vs-recheck verification and the new handoff fields. Checks: grep for old loop wording; PHPUnit unaffected.
