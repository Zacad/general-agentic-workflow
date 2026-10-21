---
name: install-workflow
description: Install or update the workflow kit in another project without overwriting its rules or records.
---

# Install the workflow

**Inputs:** kit root, target project, existing instructions/state and approval
for the proposed paths. Read root `AGENTS.md` and `docs/install.md` for the
canonical rules and exact scaffold/runtime contract.

## Procedure

1. Inspect target root/nested instructions, harness configuration, `.agents/`
   and `.workflow/`. Propose per-path add, manual merge, keep or hold actions.
2. Run the documented scaffold copy only after reviewing collisions. Default
   is add-only; force is a deliberate ordinary-kit-file refresh, not a managed
   updater. Review external customizations before using it. Never import source
   workflow history or follow held links/type conflicts.
3. Manually merge workflow rules into preserved host root `AGENTS.md`; review
   missing/incomplete/conflicting rules rather than replace host authority.
   Check every role still explicitly loads that root file when used separately.
4. Bootstrap with `composer install --no-dev` in target `cli/`, then initialize
   missing records and validate. Use full installation only for development.
   Use `./cli/bin/workflow` or the absolute target path (optionally add `cli/bin`
   to `PATH`). Mutating commands refresh the board; `workflow board` forces a
   rewrite after manual edits.
5. Register OpenCode manually and report it unverified. For a clean project,
   review `docs/opencode.example.json` and copy it to the target root as
   `opencode.json`; for an existing config, preserve it and merge the `agents`
   entries, permissions and default/model choices additively. Expect
   `workflow-orchestrator` primary/default and explorer/worker/verifier
   subagents; keep `{file:...}` role references root-relative. `.agents/skills/`
   is auto-discovered. Reload (`opencode api post /api/location/reload`) and
   check the target-scoped V2 `/api/agent` catalog for all four IDs/modes and
   reviewed default. `workflow validate` proves records only; this is not a paid
   LLM proof.
6. Use the isolated smoke example in `docs/install.md`; retain/cancel its
   history. Distinguish static prompt checks from real harness/LLM startup.

## Output

Source/target, reviewed path actions, copied/held paths, manual-merge state,
bootstrap checks and limits, OpenCode registration as REQUIRED/MANUAL/UNVERIFIED
with its target-scoped catalog result, plus the fixed AGENTS receipt.
