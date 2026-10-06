# Update, repeat and recover

Use an explicit incoming kit and the target-bound authority from [the entry skill](../SKILL.md). Update the **whole resource inventory**, not a remembered set of skill names: compare roles, full skill resources, four statics, merged AGENTS clauses and registration/model dependencies. Use [merge-guide](merge-guide.md) for meaning/configuration/model choices. A repeat installation uses the same comparison and recovery procedure.

## Establish a recoverable baseline before edits

A **baseline** is recoverable evidence of the previous source and local content. Find installation records using existing project conventions and their actual retention/accessibility. Distinguish:

- Known old source identity **with accessible original bytes or a durable recoverable revision**: supports a true comparison of upstream and local changes.
- Hash-only identity: identifies equality/difference but cannot reconstruct original text or support rollback by itself. Try to obtain the corresponding originals; otherwise treat affected provenance as unknown.
- Unknown source/provenance: snapshot current local bytes for recovery, labelled **current-local**, not an invented old kit version. A filename or protocol `v1` heading is not release identity.

Before the first target edit, choose and disclose an authorized durable source/target evidence location, reusing the installation unit's conventions when present. Preserve incoming source identity/path/date and exact resource hashes; recoverable incoming and old-base bytes or accessible revisions; current-local originals including config comments/order; relevant instruction/config contributors; planned merged bytes; source-to-target ownership/mapping and prior local merge decisions; exact approved plan/response; and before/after operation records. Snapshot approved deletions too. Record unavailable revisions honestly. Do not auto-commit or introduce a global installation registry. Disposable/ignored evidence alone is insufficient for consequential future updates; arrange durable retention through the project owner.

**First installation must leave this evidence too.** An absent target file has an explicit absent-original state; existing local files have saved bytes. Keep the incoming source originals separately from the merged installed result, so a later update can distinguish kit changes from deliberate local integration. Retain the resulting local baseline and unresolved dispositions, not only hashes of final files.

## Compare old base, local and incoming

A **three-way comparison** uses old source (base), current target (local), and incoming kit (new source). Compare both text and meaning, including dependency references and retained local clauses:

| Observed state | Proposed disposition |
| --- | --- |
| Local equals known base; incoming differs | Upstream-only refresh of positively owned kit content, after checking dependencies/approval. |
| Incoming equals base; local differs | Retain local-only changes; review whether they remain compatible with the current contract. |
| Local and incoming both differ, but changes are compatible | Deliberate combined merge, preserving local additions/resource references; show resulting meaning for review. |
| Both alter the same contract or incompatible dependencies | Show the conflict and meaningful alternatives; hold affected activation until explicitly resolved. Textual merge success does not establish semantic compatibility. |
| No recoverable base/uncertain ownership | Compare current-local intent with incoming intent, ask narrowly about ambiguous ownership and required local behavior. Do not classify differences as upstream-only or replace same-name resources automatically. |
| Desired meaning/content already present | Semantic no-op: retain it without duplicate AGENTS blocks, registration entries or references. Record no change rather than reapply a merge. |

Review every incoming addition/change/removal against the dynamic inventory. Use real source revisions when accessible plus measured content identities, not fabricated semantic release numbers. Preserve reviewed local configuration/model inheritance and instruction decisions unless the incoming contract makes them incompatible; then return a focused proposal.

## Activation, active units and obsolete resources

Read enough current active briefs/checkpoints/decisions and live project references to establish dependence on old protocol, templates, roles, skills or model/policy choices. Separate newly drafted workflow work from active units operating under an approved contract. Historical `.workflow` briefs, checkpoints, decisions and reports are immutable for retrofitting an update; new authorized installation artifacts may record the change and its effects.

If an incoming contract change affects active approved work, recommend **deferring affected activation until a stable handoff**, or a separately reviewed migration with its own required decision/revision/checkpoint and recheck. Installation approval does not reapprove other units. Keep unrelated units advancing within their authority. Stage/review the coupled contract as one compatible set; establish an activation point with no incompatible partial mixture visible to new invocations. If the available file-edit mechanism cannot provide that, hold dependent use/activation until the set is complete, and explain the staged state. Quit/restart OpenCode for the activated set rather than relying on hot reload. Follow the governing protocol for material changes, never silently rewrite approval history.

For legacy layouts, consult `<kit>/.agents/workflow/kit/install.md` old→new lookup **as migration support**, not as an installed runtime dependency. In particular `docs/protocol.md` maps to `.agents/workflow/protocol.md`, and `templates/{unit,decision,attempt}.md` to their same named `.agents/workflow/` template paths. Inspect ownership/edits first; migrate only reviewed **live** instructions/resources/config references. Keep historical old citations as written and interpretable through the retained installation evidence/source lookup. Do not blanket-remove project `docs`, `templates`, adapters, `.agents` or `.workflow` trees.

Before retiring any absent/renamed incoming resource, establish positive kit ownership, compare local edits, and search incoming/local live references, configuration, local variants and active-unit dependencies. Remove only a specifically approved, kit-owned obsolete file with no local edits or current dependencies. Ambiguous, locally edited or still-used files are retained with a reason and bounded migration/decision action; incoming absence alone does not authorize deletion. Preserve the original bytes/identity of a reviewed removal.

## Interruption and recovery

Use a per-operation journal tied to the exact source/target and approved plan. Each operation records path, intended action, original absent/bytes/hash, planned new bytes/hash, actual completion and observed post-write hash. Preserve old/new/current hashes and failed evidence; report operations as completed only after their actual write/check. A partial run is not a successful installation.

On resume or repeat:

1. Reinventory incoming source and current target, including symlinks/config overrides/models. Compare current hashes to both recorded originals and completed-operation results. A changed source requires renewed comparison and approval of affected changes, not silent continuation under the old identity.
2. If a completed file still matches the recorded new hash, keep it; if a pending file still matches its original, the approved operation can resume. Recheck cross-file dependencies and whether the desired semantic result is already satisfied. Do not append another workflow block or register another agent.
3. If current content differs from both expected states, treat it as intervening work. Stop that operation, preserve the new local content and ask for a narrow merge/resume decision. Never restore blindly over a user edit or another worker's change.
4. Restore **only this operation's own completed writes**, only with recoverable originals and no intervening edit (current hash equals the recorded own-write result), under applicable recovery approval. Restore saved originals; remove an own newly created file only if unchanged and safe for dependencies. Check symlink/parent state before restoring. A recorded original hash without bytes is not a rollback source.
5. Recheck the coherent contract after resume/restore. Keep failed/partial evidence, retained files and unrun checks visible; link the replacement outcome rather than erasing the original. If safe recovery cannot be established, hand off a held state with exact paths, owner and next decision.

Finish with the source/base/local and resulting manifests, preserved local choices, completed/held/retained operations, activation status, actual checks and a bounded next action. [Verification](verification.md) distinguishes recovered files, usable/discovered setup and live model proof.
