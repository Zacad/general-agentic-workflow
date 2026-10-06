# General agentic workflow

A domain-neutral, recursive way to work on an endeavor or a bounded request in an existing endeavor. A work unit may describe a software feature, a book chapter, a trip plan, or any smaller piece with a clear outcome. Work one decision horizon at a time; do not plan and execute the whole hierarchy in one session.

The primary agent coordinates workers and asks the user for scoped checkpoints. Workers investigate, plan, produce, and independently check outcomes using on-demand skills. The durable source of truth is in the endeavor workspace, not the chat transcript.

Every role, including the coordinator, loads [clear-communication](.agents/skills/clear-communication/SKILL.md) alongside `critical-reasoning`. It gives equal attention to well-organized, useful writing and explaining unfamiliar terms in the reader's current problem. The rules guide behavior through instructions; actual loading and output quality still need checking.

## Components

- `AGENTS.md`: short, portable always-on rules and the state entry point.
- `.agents/roles/`: canonical role prompts, including the orchestrator; these are **not** automatically registered as agents by OpenCode or pi.
- `.agents/skills/`: portable Agent Skills discoverable from a project workspace by OpenCode and, when project resources are trusted, Pi.
- `.agents/workflow/{protocol,unit,decision,attempt}.md`: portable static protocol and starting templates installed alongside roles and skills.
- `.agents/workflow/kit/`: source-only [installation](.agents/workflow/kit/install.md), [Pi guidance](.agents/workflow/kit/pi.md), [trial guidance](.agents/workflow/kit/trials.md), results, fixtures and [OpenCode example](.agents/workflow/kit/opencode.example.json); the example registers roles and models without a plugin.

## Install or update an endeavor workspace

Use [install-workflow](.agents/skills/install-workflow/SKILL.md) for a new project, integration with existing AGENTS/`.agents`/OpenCode configuration, or an installed/legacy workflow update. It loads from an explicit source kit, analyzes effective instructions/configuration and current model evidence, recommends exact changes, then asks before applying them. See [source-loading examples and manual fallback](.agents/workflow/kit/install.md) and the focused [update/recovery guide](.agents/skills/install-workflow/references/update-guide.md).

The target does not need to have the installer registered. For example:

```text
Read /abs/kit/.agents/skills/install-workflow/SKILL.md in full and use it
to install the workflow into /abs/project from /abs/kit. Analyze first,
recommend changes and role models, then ask before target changes.
```

For an update, use the same explicit source-loading request with **update the installed workflow** and the chosen incoming kit/target. Keep source, target and command cwd explicit. Copied installer references remain portable; future updates still require a separate incoming source kit. Transfer the whole dynamically inventoried roles/skills/resources, merged AGENTS rules and four statics—not source-only `kit/`, source state, caches or the blanket source ignore rule. Preserve local rules/configuration and recoverable before/after evidence from the first installation; unresolved mandatory collisions mean incomplete setup.

[Verification](.agents/skills/install-workflow/references/verification.md) distinguishes instruction/resource review, actual installed files, native discovery and live model use. Native debug/model initialization is conditional on permitted startup effects; catalog or credential presence does not prove inference. Quit and restart OpenCode after configuration/role/skill changes. The target workspace stores its own evolving state under `.workflow/`:

```text
.workflow/
  units/U-0001/brief.md
  decisions/D-0001.md
  attempts/U-0001/A-0001/report.md
```

Use `start-new-endeavor` for a new root, or `start-existing-work` for an existing issue/request. Investigation to draft a root brief is allowed before initial approval; advancing that root requires its checkpoint. Each child requires an explicit checkpoint naming it and its material revision. Routine work within an approved unit is delegated without another phase-by-phase approval.

For substantial nonsoftware or materially changing nonsoftware goals, use on-demand [endeavor-requirements](.agents/skills/endeavor-requirements/SKILL.md) for substantive discovery and reviewable breadth/depth/exception coverage before dependent delivery. Reuse adequate specifically reviewed current input and add gaps; tiny exact issues can use concise sourced brief/report coverage. Software retains [product-requirements](.agents/skills/product-requirements/SKILL.md) discovery. The [shared coverage/review gate](.agents/workflow/protocol.md#requirements-review-and-coverage) separates actual scoped agreement and dependent readiness from artifact adequacy and unit authorization; a separate requirements child is conditional, not a universal hierarchy.

Before affected substantial software commitments, use [code-architecture](.agents/skills/code-architecture/SKILL.md) when design matters or [choose-approach](.agents/skills/choose-approach/SKILL.md)'s conditional testing method for material same-scenario strategy alternatives, critical coverage/evidence limits and version/prerequisite feasibility. The orchestrator actually presents an owned concise strategy artifact and retains exact scoped response; stored plans/generic stack approval are not review, and review is not execution. Reuse adequate specifically reviewed unchanged strategy; tiny exact issues retain meaningful focused regression/honest limits without forced stages or runner microapproval. See the [testing review gate](.agents/workflow/protocol.md#testing-strategy-and-scoped-review).

For material interface work, surface desired visual quality/taste and audience/task/platform/brand/use-context early, then use [ui-ux-design](.agents/skills/ui-ux-design/SKILL.md) for two substantive contextual directions with representative states/narrow-wide **viewable previews**. The orchestrator reads and actually relays an owned concise review artifact and accessible visual assets; one writer retains exact version/presentation/actual scoped response. Private previews, raw source or artist descriptions are not visual presentation. Consume sufficient review before affected material readiness/production; validate adequate unchanged reviewed reuse, and keep tiny in-direction corrections to sourced acceptance/focused checks. Missing brand may be a proposed contextual default for review, not agreed plain styling; missing access/context stays pending with owner/action while unrelated authorized work continues. Later independent direction comparison remains separate from rendered/functional/accessibility checks and participant-based usability evidence; mockup approval proves none of those outcomes. See the [visual review gate](.agents/workflow/protocol.md#visual-direction-and-scoped-review).

This repository supplies the workflow; do not confuse its static templates with an active endeavor's approved briefs. See [the protocol](.agents/workflow/protocol.md) for how to resume from files, resolve changed decisions, and reconcile outcomes.

The source kit's `.workflow/` is ignored by Git and used only for disposable live trials; the installed endeavor's `.workflow/units/` and `.workflow/decisions/` should be kept durably. See [trial observations](.agents/workflow/kit/trial-results.md) and the [old→new path lookup](.agents/workflow/kit/install.md#oldnew-source-path-lookup) for historical citations.
