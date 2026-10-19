---
name: install-workflow
description: Install or update the workflow kit in another project without overwriting its rules or records.
---

# Install the workflow

**Inputs:** the kit root, the target project, its existing instructions and
workflow state, and the actual approval for the change.

## Procedure

1. Inspect the target's root and nested `AGENTS.md`, harness config, `.agents/`
   and `.workflow/` before writing anything.
2. Propose a per-path plan: add, merge, keep or hold. Never replace a stricter
   local rule; merge additively. Hold same-name collisions for review.
3. Copy the kit:
   - `AGENTS.md`
   - `.agents/roles/` and `.agents/skills/`
   - `cli/` (source, not `vendor/` or `tools/`)
   - `docs/`
4. Run `composer install` in the target's `cli/`, then:
   ```
   cli/bin/workflow --project <target> init
   cli/bin/workflow --project <target> validate
   ```
5. Review the merged `AGENTS.md` for contradictions with local rules. Verify
   the CLI reads the target's records.
6. Never copy the kit's `.workflow/`, its board, its lock file or its history.
7. Restart the agent harness after prompt changes.

## Output

The source and target, the per-path plan, what was copied or held, the
verification result and the next action.
