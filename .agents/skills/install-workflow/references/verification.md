# Check installation guidance and target outcomes

Use [the entry skill](../SKILL.md)'s exact source/target/cwd, approved plan and stable manifest. Checks must detect wrong resources, effective conflicts and incomplete updates. Inspecting the instructions is useful source-level evidence; it is not an executed installation, native loader result or live model call.

## Static target contract

Compare the actual dynamic incoming inventory with approved target dispositions, including whole role/skill bodies and every reference/support file, root instruction merge and the four statics. For unchanged transfers compare bytes/hashes; for reviewed merges compare saved originals, proposed result and preserved local clauses/dependencies. Check skill frontmatter `name`/`description`, directory/name agreement, local relative links and resource paths. The copied installer must work without installed source-only `kit/`, while asking for an explicit kit on future update.

Check all five canonical registrations against [merge-guide](merge-guide.md#canonical-registration-and-task-contract): name/mode/complete prompt/path resolution; explicit/inherited effective model IDs and supported variants/options; enabled primary/default choice; ordered global/per-agent edit/bash/task/external-directory/skill policy. Validate proposed fields against the official schema and installed version. JSONC comments/unrelated fields/order must survive. Inspect directory/global/custom/env/managed override sources; an unknown effective layer remains an unknown, not a static pass. A discovered name/count cannot detect a wrong source, truncated prompt or missing resource.

Confirm recoverable incoming/base/local originals and operation hashes, actual approval and local merge decisions are accessible at the disclosed durable location. Inspect active-unit impact, retained obsolete resources and activation/resume status. Preserve existing historical authority. Record completed versus held operations and actual checks separately; mandatory held contracts/mappings leave setup incomplete.

## Scenario walkthroughs

For source-level review, independently follow each concrete state through the instructions, naming the exact passage that determines the action and expected result. Do not merely count scenario headings. For future real trials, use separately approved disposable targets, original snapshots and an actual installer invocation; record actual asks/writes/results. These walkthrough expectations apply to all routes and their fresh → customized → update transitions:

| Input/state and failure to detect | Required action / expected observation |
| --- | --- |
| Accessible kit, absent target, one eligible catalog candidate | Direct bootstrap/full common-skill reads work without installed workers; inspect ancestors; propose complete inventory and exact five-role mapping (one model allowed), record access limits, ask, save first-install baseline, change only approved target paths, restart handoff. |
| Missing/inaccessible kit or copied installer with no incoming source | Request exact accessible source; no fabricated installed `kit/` dependency, download or target setup claim. |
| Target becomes nonempty, symlink/dangling symlink or changed parent after approval | Revalidate destination/ownership, stop affected write and revise plan; no old-empty overwrite assumption. |
| User declines proposed default/model/permission change | No undisclosed mutation; preserve compatible choices, or hold mandatory unresolved configuration with a focused next action. |
| Project test clause, nested instructions, CLAUDE-only fallback | Preserve effective required rules and scope when adding AGENTS; retaining suppressed fallback bytes alone fails. |
| Same-name unrelated skill/role, edited protocol, mandatory no-delegation/no-report-write rule | Establish ownership/intent and deliberate compatible resolution, or held incomplete installation. No overwrite, gate stripping or silent broadening. |
| Commented JSONC, directory agent, global/custom/env/managed override | Preserve comments/unrelated values/order; edit agreed contributor; detect actual override/prompt/default mismatch. No flattening or sibling config bypass. |
| Global strict deny, later per-agent allow, or deny-before-broad-allow | Evaluate last matching effective rule and surface broadening; canonical orchestrator task allows follow broad deny, workers deny task. No example-default auto-copy. |
| Existing explicit and inherited models; known/unknown tool/context/cost evidence | Preserve suitable intentional mapping with exact effective IDs/inheritance source; label unknowns and ask only unresolved priorities. No memory-based model tiers. |
| No candidate, blocked catalog/auth evidence, deprecated or listed-but-unusable model, unsupported variant | Hold/request evidence or return revised exact mapping for review. Candidate/credential presence never equals authenticated inference; no placeholder config or implicit login/live test. |
| Known pristine base versus local edits and incoming changes | Upstream-only refresh versus meaningful three-way merge; retain local clauses/references, hold incompatible changes and preserve recoverable originals. |
| Hash-only/unknown baseline or legacy paths with unrelated docs/templates | Seek originals or use current-local recovery baseline without inventing provenance; ask about ambiguous ownership, migrate reviewed live paths only; preserve unrelated trees and old historical citations. |
| Active approved unit depends on changed contract; incoming removes a used/local resource | Defer affected coherent activation/retirement or request separately scoped migration/checkpoint; preserve historical records and continue unrelated authorized work. |
| Reinstall, interrupted completed writes, changed source or intervening user edits | Semantic no-op/no duplicate blocks or agents; reconcile journal/current hashes, resume only known unchanged states, restore only own unchanged writes from originals; altered paths held, failed evidence retained. |
| Missing copied reference, duplicate skill loaded elsewhere, truncated/wrong role prompt | Full resource/path/body comparison fails; counts or registration names alone cannot pass. |

## Native prerequisites and command shape

Before model/config/skill/agent discovery, inspect actual installed CLI help, version and permitted startup effects in the intended target context. The v1.18.34 [config loader](https://raw.githubusercontent.com/anomalyco/opencode/v1.18.34/packages/opencode/src/config/config.ts) can add missing schema/global/support files, fetch remote/managed settings and start background dependency installation. Check existing config/support/dependency/plugin/provider/network prerequisites and whether those effects are within the actual target operation's authorization. If they are prohibited, missing or unknown, hold the command and report the native check **pending-unrun/blocked** with a specific owner/action; static contract inspection remains a different result. Do not provision to manufacture a pass.

Neither `--pure` nor isolated environment overrides guarantee read-only operation. A deliberately prepared isolated variant proves only that variant, not the normal target's multi-layer context. Do not dump resolved keys: inspect secret-bearing inputs without copying values; redact any config/debug/auth output before persisting or relaying it.

The following shapes were confirmed by installed v1.18.34 help on 2026-10-06. Run commands separately using the **actual target cwd**, only after prerequisites/effects are acceptable; recheck help for a changed version. Help checks establish syntax, not loader success:

```text
opencode --version
opencode models --help
opencode auth list --help
opencode debug --help
opencode debug agent --help
```

Conditional discovery/check commands:

```text
opencode auth list
opencode models --verbose
opencode debug config
opencode debug skill
opencode debug agent workflow-orchestrator
opencode debug agent workflow-investigator
opencode debug agent workflow-planner
opencode debug agent workflow-producer
opencode debug agent workflow-verifier
```

Auth list supplies non-secret provider/credential-presence metadata; model listing supplies catalog/config candidates, not inference. Do not add `--refresh` implicitly. For debug agents omit `--tool` and `--params`: [the v1.18.34 handler](https://raw.githubusercontent.com/anomalyco/opencode/v1.18.34/packages/opencode/src/cli/cmd/debug/agent.handler.ts) can execute a tool/create a session with `--tool`. Ordinary debug resolution still has prerequisites/effects.

Inspect complete debug skill bodies and source locations against approved files, including both common skills and the installer; detect duplicate/shadowed definitions. Separately compare its references/support resources as full files; discovery does not return or prove every referenced resource. Inspect complete role prompts and declaring-config path resolution, models/default/modes and effective ordered policies. Resolve results back to actual contributors; command exit 0 is not proof every contract item matches. A fresh process for a debug check does not update a running interactive session: tell the user to **quit and restart OpenCode** after edits.

## Record proof and completion honestly

For every material check record expected behavior from actual approved plan/current canonical sources; stable source/target/producer identity; method; date/environment/version/prerequisites; cwd and exact command/input or passage walkthrough; observed result/exit and redacted artifact; executed-pass/fail or pending-unrun/blocked; supported/contradicted/unverified claim and bounded next action. Producer selfchecks are not independent proof. If a controlling workflow applies, assign an actual distinct verifier with own result/report/concise outcome-summary paths, then follow its actual read/relay and sole-writer reconciliation gates. Freeze checked bytes; repairs name a new target and get affected independent rechecks.

Separate the resulting claims:

- **Instructions/source contract reviewed:** resources, scenarios and command shapes inspected; no target execution implied.
- **Files/config installed:** actual approved writes compared to originals/manifests; held mandatory choices prevent a complete setup claim.
- **Native discovery/resolution checked:** exact executed context, bodies/paths/policies observed; unrun native work remains explicitly unrun.
- **Live model use checked:** only an explicitly approved actual invocation supports dated model-access evidence. Catalog/auth metadata, native discovery or static review cannot substitute.

No check here guarantees arbitrary-project semantic merges, future agent compliance, independent invocations merely from different models, or user effectiveness. Actual agent-driven trials are stronger bounded behavioral evidence when separately scoped; do not label walkthroughs as those trials.
