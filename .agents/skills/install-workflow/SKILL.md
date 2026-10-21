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
5. Review/merge harness registration without overwriting existing settings.
   Restart after prompt/config changes and check actual role availability.
6. Use the isolated smoke example in `docs/install.md`; retain/cancel its
   history. Distinguish static prompt checks from real harness/LLM startup.

## Output

Source/target, reviewed path actions, copied/held paths, manual-merge state,
bootstrap/harness checks and limits, plus the fixed AGENTS receipt.
