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
5. Register the harness agents. For OpenCode V2, copy
   `docs/opencode.example.json` to `<target>/opencode.json` when the target has
   no config. If it already has one, merge the `agents` entries additively and
   keep its `default_agent` unless the user asks otherwise. Keep the config at
   the project root: `{file:...}` paths resolve from the config file. The
   example omits `model`, so every agent inherits the session model; add
   `"model": "provider/model"` only when the user wants a pinned model.
   Use V2 fields (`agents`, `system`, ordered `permissions`); do not use the V1
   `agent`, `prompt`, `permission`, `bash` or `task` fields. Skills under
   `.agents/skills/` are discovered automatically; no `skills` entry is needed.
6. Review the merged `AGENTS.md` for contradictions with local rules. Verify
   the CLI reads the target's records and, for OpenCode, that
   `opencode debug agents` lists the four `workflow-*` agents.
7. Restart the agent harness after prompt or config changes.
8. Never copy the kit's `.workflow/`, its board, its lock file or its history.

## Output

The source and target, the per-path plan, what was copied or held, the harness
registration result, the verification result and the next action.
