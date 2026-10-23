---
name: start-project
description: Start a new project with the workflow kit — recover the goal, record PRD and architecture decisions, frame and approve the first epic.
---

# Start project

Use to begin a NEW project with the workflow kit. For an existing project,
resume with `workflow status` and a role brief instead.

**Inputs:** the goal in the requester's terms, constraints and context, the
`workflow` CLI (`cli/bin/workflow` or `workflow` on `PATH`), and root
`AGENTS.md`. Run `workflow init` when `.workflow/` templates are missing.

## Procedure

1. Recover the goal and run the requirements method
   (`.agents/skills/requirements/SKILL.md`). Record and refresh
   `.workflow/PRD.md` — purpose, users, goals, non-goals, requirements and
   product-level acceptance. Fill the PRD before presenting the first epic
   for approval.
2. Run the architecture method, route 1 — architecture design
   (`.agents/skills/code-architecture/SKILL.md`): drivers and
   characteristics, compared approaches, one recommendation with limits.
   Discuss it with the user, then record the durable decisions in
   `.workflow/Architecture.md`.
3. Frame the first epic with `.agents/skills/decompose-work/SKILL.md`:
   outcome, scope, acceptance, dependencies, owned paths and discovery plan.
4. Stage approvals per root `AGENTS.md`: epic scope first, then the exact
   child records after required evidence.

## Output

Filled `.workflow/PRD.md`, recorded `.workflow/Architecture.md` and an
approved first epic, ready for `workflow ready` and child dispatch.
