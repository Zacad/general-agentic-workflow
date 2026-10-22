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
5. OpenCode registration: for a clean project the installer creates
   `opencode.json` from `docs/opencode.example.json` itself; for an existing
   config it is never overwritten — the installer reports exactly which
   `workflow-*` agents are missing and you merge those `agents` entries,
   permissions and default/model choices additively. Expect
   `workflow-orchestrator` primary/default and explorer/worker/verifier
   subagents; keep `{file:...}` role references root-relative. `.agents/skills/`
   is auto-discovered. The installer also queries the target-scoped V2
   `/api/agent` catalog when `opencode` is on `PATH` and reports registered
   agents (graceful skip otherwise); reload first
   (`opencode api post /api/location/reload`) if agents are missing. All
   registration checks are advisory. `workflow validate` proves records only;
   this is not a paid LLM proof.
6. Use the isolated smoke example in `docs/install.md`; retain/cancel its
   history. Distinguish static prompt checks from real harness/LLM startup.

## Output

Source/target, reviewed path actions, copied/held paths, manual-merge state,
bootstrap checks and limits, OpenCode registration state (created for clean
projects, per-agent merge guidance for existing configs) with the target-scoped
catalog result, plus the fixed AGENTS receipt.
