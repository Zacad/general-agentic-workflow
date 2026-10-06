# Install, integrate or update a project

This repository is a **source kit**, the incoming workflow files for a chosen project. Use [install-workflow](../../skills/install-workflow/SKILL.md) for new projects, integration with existing instructions/resources/OpenCode configuration, and current or legacy updates. It analyzes the actual setup, recommends changes and role models, asks about material choices, then guides approved project-local changes and checks. No plugin or global installation is needed.

## Load from the explicit kit

The kit directory, target project/worktree and command working directory are separate inputs. The target need not already discover the skill or have workflow workers. For example, give an agent this request, replacing the absolute paths:

```text
Read /abs/kit/.agents/skills/install-workflow/SKILL.md in full and use it
to install the workflow into /abs/project. The source kit is /abs/kit;
the target and intended OpenCode cwd are /abs/project. Analyze first,
recommend exact changes and role models, and ask before target changes.
```

For an update:

```text
Read /abs/incoming-kit/.agents/skills/install-workflow/SKILL.md in full
and use it to update the installed workflow in /abs/project from
/abs/incoming-kit. Inspect the existing baseline, local changes and
active units; recommend a recoverable change plan, then ask.
```

Read both common skills in full from that source when discovery is missing, as the entry skill directs. Respect any existing governing workflow's actual investigation/checkpoint/ownership gates; bootstrap does not invent target checkpoints. Record target-operation approvals and recoverable evidence in a disclosed durable location appropriate to the controlling source/target project. Source authoring approval does not authorize a target operation. The copied installer has its own portable references, but future updates still require an explicit separate incoming kit; this source-only `kit/` directory is not assumed installed.

## Manual project-local fallback

Follow the same analyze → recommend/ask → approved changes → check/recovery sequence manually:

1. Resolve paths/symlinks and inspect the target, ancestors, nested instructions and current OpenCode contributors before writes. Inventory this source's entire `.agents/roles/` and complete `.agents/skills/` directories, including every reference/support resource. Copy/merge only that reviewed portable inventory, root AGENTS rules and `.agents/workflow/{protocol,unit,decision,attempt}.md`. These four statics are the installed workflow contract. Source `kit/`, `.workflow/`, caches and blanket `.gitignore` are not payload. Use exact path/content identities, not a fixed skill count; record a real source revision when available or its honest absence.
2. Merge root AGENTS rules retaining stricter/domain clauses and nested scope. Inspect CLAUDE fallback and configured `instructions`: adding AGENTS can suppress formerly effective CLAUDE guidance. Resolve actual meaning/ownership collisions in roles/skills/statics and same-name agents; preserving conflicting bytes is not complete integration. [Merge-guide](../../skills/install-workflow/references/merge-guide.md) covers the deliberate rule/config merge, canonical role mapping and model procedure.
3. Inspect current target-context version/help, provider filters and model evidence. When startup prerequisites/effects are acceptable, `opencode models --verbose` supplies candidates/metadata and `opencode auth list` supplies provider/credential-presence metadata. Neither proves live inference. Recommend exact IDs for every canonical role, compatible variants/options, explicit versus inherited choices, evidence/unknowns and alternatives; preserve suitable existing choices and ask before changing them. One eligible model can serve all roles. Never copy `provider/REPLACE_WITH_...` placeholders or log in/probe live models implicitly.
4. Use the source [OpenCode example](opencode.example.json) as a reference, not wholesale config. Register `workflow-orchestrator` as primary and `workflow-{investigator,planner,producer,verifier}` as subagents, using the matching `.agents/roles/{orchestrator,investigator,planner,producer,verifier}.md` prompts. Preserve JSONC comments, unrelated fields, providers, compatible existing default/models and effective ordered permissions across project/directory/global/custom/env/managed layers. Prompt substitutions are relative to the **declaring config**: root config uses `{file:./.agents/roles/<role>.md}`, `.opencode/opencode.json` uses `{file:../.agents/roles/<role>.md}`. Do not add a competing sibling config to bypass a conflict. OpenCode does not auto-register plain role files; Pi can load them via `--append-system-prompt` instead.
5. Review policy/default consequences and ask. The example's per-agent edit/bash allows can broaden stricter global policy; do not adopt them automatically. The orchestrator task contract denies `*` then allows the four canonical workers; workers deny delegation. Last matching permission rule wins. Workers need permitted assigned-report writes; incompatible mandatory no-delegation/no-write rules require an explicit compatible resolution or held incomplete setup. Role ownership instructions and tool permissions do not replace workflow checkpoints. Preserve external-directory boundaries. Non-interactive runs may reject `ask` rules; do not silently broaden them to get a pass.
6. Before approved edits preserve recoverable incoming/base/local content, actual approval/merge decisions and per-operation before/new/current hashes at the disclosed durable location. Revalidate paths/source/target/config/model context before writes; stop affected drift or new collisions. First installation must retain enough evidence for future updates. Use [update-guide](../../skills/install-workflow/references/update-guide.md) for known/unknown provenance, active-unit impact, no-op repeats, interrupted writes and safe restoration of only unchanged own writes. No automatic commits or global registry.
7. Check the full approved resource inventory, actual merged rules, exact full role prompts and effective configuration using [verification](../../skills/install-workflow/references/verification.md). Native debug/model commands may write support/schema/global files, fetch settings or start dependency installation: inspect prerequisites/effects before running them; `--pure` is not a read-only guarantee. Report unrun native checks distinctly from static contract review. Quit and restart OpenCode after config/role/skill edits, then start a named project request and follow actual framing/checkpoint rules. Discovery does not prove live model access or future agent behavior.
8. When drafting the project's first unit, create its own `.workflow/units/`, `.workflow/decisions/` and `.workflow/attempts/`, using installed templates for **new** files and replacing examples. Preserve existing state; keep durable briefs/decisions and baseline evidence according to project practice. If reports are locally ignored, retain evidence needed by unresolved checks/lasting decisions. **Do not copy the source kit's blanket `.workflow/` ignore rule.**

## Upgrade an older installation

Use the update request above and focused [update-guide](../../skills/install-workflow/references/update-guide.md). Compare known old source, current local and incoming source; a hash-only/unknown baseline is not recoverable original content. Snapshot current local recovery content without inventing old provenance. Inspect active units before changed-contract activation; defer affected activation until a stable handoff or request separately reviewed migration with required revision/decision/checkpoint. Historical authority is not rewritten by installation approval.

When adding `clear-communication`, merge its directory and common-loading clauses into AGENTS and all canonical roles, preserving compatible local methods. Check the **current source inventory** and exact full contents, not an old expected skill count. Quit/restart, then check discovery/prompt sources; actual full loading of both common skills for every role, including the coordinator, is a separate observation from discovery. A portable skill needs no new agent/plugin by itself; review any actual configuration/policy dependency.

Use the [old→new path lookup](#oldnew-source-path-lookup) to identify references in existing files and historical records. Inventory the installed `docs/protocol.md`, `templates/{unit,decision,attempt}.md`, `AGENTS.md`, role/skill files, configuration and `.workflow/` first. Compare old files to their known kit originals or inspect project-specific edits; do not presume a same-named file belongs to the kit. Merge the four static files into `.agents/workflow/` and update **live** project instructions, roles, skills and registration paths only after reviewing collisions. Keep historical `.workflow/` briefs, checkpoints, decisions and reports as written; the lookup explains old citations. Preserve the project's other `docs/`, `templates/`, adapters and state. Remove an obsolete installed workflow file only when positively identified as kit-owned and no active references or local edits depend on it; never delete an entire project directory or an existing `.workflow/` tree as a shortcut. Review existing OpenCode agent entries before adapting the example; keep role prompts pointed to `.agents/roles/*.md`.

## Old→new source path lookup

Use this table to interpret pre-migration citations without rewriting historical evidence. The `kit/` destinations below are **source-only**; installed workspaces need just the four static workflow files in the first two rows, plus roles/skills and merged root instructions.

| Former source path | Current source path |
| --- | --- |
| `docs/protocol.md` | `.agents/workflow/protocol.md` |
| `templates/unit.md`, `templates/decision.md`, `templates/attempt.md` | `.agents/workflow/unit.md`, `.agents/workflow/decision.md`, `.agents/workflow/attempt.md` respectively |
| `docs/install.md` | `.agents/workflow/kit/install.md` |
| `docs/pi.md` | `.agents/workflow/kit/pi.md` |
| `docs/trials.md` | `.agents/workflow/kit/trials.md` |
| `docs/trial-results.md` | `.agents/workflow/kit/trial-results.md` |
| `adapters/opencode/opencode.example.json` | `.agents/workflow/kit/opencode.example.json` |
| `trials/fixtures/existing-code/` | `.agents/workflow/kit/fixtures/existing-code/` (including nested `AGENTS.md`, `converter.py`, `test_converter.py`) |
| `trials/fixtures/new-software/converter.py` | `.agents/workflow/kit/fixtures/new-software/converter.py` |

Ignored generated `existing-code/__pycache__/*.pyc` bytes were retained locally with the moved fixture; they are not portable installation files. Pre-migration unit briefs' literal old fixture boundaries remain historical scopes, not authorization for new work at the relocated paths.

For Pi, see [Pi compatibility](pi.md); for trials, see [trial guidance](trials.md) and [historical results](trial-results.md). OpenCode's [agents](https://opencode.ai/docs/agents/), [skills](https://opencode.ai/docs/skills/), [rules](https://opencode.ai/docs/rules/), and [config schema](https://opencode.ai/config.json) document supported configuration. Check installed-version behavior when it changes.
