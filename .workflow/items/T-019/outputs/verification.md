# T-019 verification

**Verdict: pass. Full verification (not a targeted recheck).**

Checked on 2026-10-21 against working-tree target files based on HEAD
`d9b1e6ccb17e24eba39d7df93c4e42f8fcfc23e5`. Deleted-hunk source was
`git diff 3ec52c6..d9b1e6c` for the nine owned paths, plus the related
canonical documents to check for replacement locations. No producer receipt,
worker/default brief or subagent report was read.

## Criteria

| # | Expected | Observed | Method / environment | Result | Limits |
| --- | --- | --- | --- | --- | --- |
| 1 | Explorer forbids source/deliverable edits and design decisions, and checks item/source revision before review. | `.agents/roles/explorer.md:17-19` requires confirming item and source revision and reporting changes after the brief. `:22-27` allows the assigned artifact path but forbids editing source, final deliverable, canonical documents, item records, and making solution-design decisions. Skills are selected by question type at `:8-11`. | Read the current role against the deleted hunks; checked skill names exist under `.agents/skills/`. | pass | Prose check only; no real LLM role startup. |
| 2 | Roles select relevant skills by context; changed project facts reconcile into PRD/Architecture; worker excludes cache/build noise. | Explorer `:8-11`, worker `:8-11` and orchestrator `:8-11` select methods conditionally. Orchestrator `:27-28` requires project-level facts to be reconciled into `PRD.md` or `Architecture.md`. Worker `:26-27` excludes caches, build output and dependency/vendor noise from deliverables and requested commits. Verifier keeps its required skill set at `:8-9`. | Read current roles against deleted role hunks and the owning-document rule. | pass | Static prose inspection; no runtime skill loader test. |
| 3 | PRD restores explicit non-goals; Architecture retains no-DI/no-bundle/no-HTTP-kernel boundary. | PRD `:26-32` restores the old exclusions: no database/daemon/scheduler/server, auth/multi-user/remote collaboration, rigid machine, search/comments/attachments/time tracking/auto-commit, broader web UI, migration, automatic reopen/successor, updater/host merger, fingerprint/snapshot storage, per-attempt copies. Architecture `:11-13` states the lightweight Console composition and explicitly no DI container, no bundles, no HTTP kernel. | Compared the old PRD non-goals and Architecture omissions/CLI component hunks to the current text. | pass | Product wording check; not a behavioural test of absent components. |
| 4 | Decomposition restores focused-session, edit-volume, second-draft and standalone-task fallback guidance as advisory only. | `decompose-work/SKILL.md:36-53` restores one focused session, dozens of edits/second full draft, artifact size, search count, and standalone-task fallback, and labels the cues advisory signals rather than gates. | Read the restored section against the deleted sizing/research-heavy hunks and AGENTS' advisory sizing policy. | pass | Judgment wording; no automated threshold test. |
| 5 | Canonical lifecycle stays single-source and no removed approved policy returns. | `AGENTS.md:81-113` is the only lifecycle sequence; roles delegate to AGENTS rather than repeating it, and the CLI skill says it is not a second loop (`:8-10`). Staged approval/discovery remains at `AGENTS.md:60-73`; explicit done follow-ups at `:155-162`; verification/freeze rules at `:146-162`; manual merge at `:3-6` and every role header. Search found no mandatory discovery task, warning-only completion, auto-reopen, or routine re-approval policy. | Read all nine owned docs and searched them for the superseded phrases; compared against the deleted AGENTS loop and E-002 accepted policy. | pass | Prose consistency check; not a formal proof over all history. |
| 6 | `workflow validate` and focused plus full tests pass. | `workflow validate` returned `OK (0 warnings)`. Focused run: 65 tests, 690 assertions, 1 PHPUnit deprecation. Full suite: 702 tests, 5608 assertions, 1 PHPUnit deprecation. | `TMPDIR=/tmp/opencode cli/bin/workflow validate`; `TMPDIR=/tmp/opencode cli/vendor/bin/phpunit --do-not-cache-result` with focused files, and from `cli/` for the configured full suite. PHP 8.5.10, PHPUnit 13.4.3, Linux. | pass | PHP 8.4.1 execution and real LLM/harness startup remain unrun; the deprecation is reported, not hidden. |

## Deleted-hunk restoration audit (nine owned paths)

| Document | Restored material facts | Result |
| --- | --- | --- |
| `AGENTS.md` | Bounded coordinator reads vs allowed mutations; item layout/history rules; kinds/dependencies/ownership; advisory phases; staged exact approval and epic Design scope map; plan/explore/design/implement/verify/close sequence; small-work and deliberate-force rules; sizing steering; delegation by role-specific brief; fixed receipt; verification invalidation and done-history follow-ups; resume behaviour. Command catalogue correctly lives in the CLI skill instead of being duplicated. | pass |
| `.agents/roles/orchestrator.md` | Context-specific methods; bounded reads and record mutations; exact/staged approval and one parent scope map; verifier receipt accuracy; PRD/Architecture reconciliation. | pass |
| `.agents/roles/explorer.md` | Context-specific methods; revision check; evidence-only artifact; explicit prohibitions on source/deliverable/canonical-record edits and solution design; one dimension. | pass |
| `.agents/roles/worker.md` | Context-specific methods; needs-decision for missing/wrong scope/design/evidence; report rather than replace a wrong design; smallest coherent change; owned-path and record boundaries; proportionate checks; frozen target; cache/build/dependency noise exclusion. | pass |
| `.agents/roles/verifier.md` | Explicit verifier brief, expectations before producer conclusions, frozen target, actual-artifact checks, full-vs-recheck distinction, per-criterion evidence, no repair, done/follow-up boundary, forced close is not success. The file is unchanged because the post-consolidation text already carried these facts. | pass |
| `.agents/skills/workflow-cli/SKILL.md` | Command/read/write contract; role packets and field-based blinding; new item starts in phase `plan`; receipt/close separation; no second lifecycle loop. | pass |
| `.agents/skills/decompose-work/SKILL.md` | Child outcome tests; evidence vs deliverable split; epic-only scope map; optional discovery child; decomposition rationale/deferred triggers; advisory focused-session/edit-volume/second-draft sizing; one writer; standalone-task fallback. | pass |
| `.workflow/PRD.md` | Explicit product exclusions restored (see criterion 3); mechanism/limit statements for approval, verification, packets, installation and runtime align with current CLI behaviour and tests. | pass |
| `.workflow/Architecture.md` | Lightweight Console component boundary and no-DI/no-bundle/no-HTTP-kernel statement; storage/write boundary; approval vs verification lifecycle; read projections; distribution/manual-merge/runtime limits; real source paths resolve. | pass |

## Related canonical documents checked read-only

The five rewritten documents outside T-019's ownership were inspected only for
replacement locations. `install-workflow` retains reviewed manual merges and
harness registration; `fresh-session-handoff` retains role-brief confirmation,
snapshot semantics and the no-proof rule; `verify-outcome` retains independent
derivation, frozen-target checks, per-criterion evidence and repair limits.
`README.md` and `docs/install.md` belong to T-020's scope, so they were not
treated as T-019 deliverables.

## Residual items assessed independently

None is material to T-019's approved outcome or acceptance.

- **Old PRD 50-item status check:** the exact 50-item acceptance sentence is not
  restored. The capability remains: `status` caps each section and `ready` caps
  its list (`StatusCommand.php:117,134,143,179-186`;
  `ReadyCommand.php:14,52`), and the PRD still requires bounded default views.
  Non-material documentation condensation; a measurable 50-item product check
  would be a separate PRD follow-up, not a T-019 acceptance failure.
- **Old PRD self-contained-board check:** the exact offline/self-contained
  acceptance sentence is not restored. `BoardRenderer.php` emits one HTML file
  with inline CSS and no external `link`, `script src`, `@import` or `url(...)`.
  Behaviour remains; the omitted sentence is non-material.
- **Old PRD one-sitting check:** the subjective "whole kit in one sitting"
  criterion is not restored. Current PRD acceptance evidence instead uses
  measured word-count comparisons with an explicit file set. Treated as an
  intentional, non-material replacement of an unverifiable slogan.
- **Architecture no-MCP note:** omitted from current Architecture. The PRD still
  excludes long-running servers, and `docs/design-notes.md:92` records MCP as a
  later wrapper, not a current daemon. Non-material; no MCP surface was added.
- **Explorer artifact-as-deliverable nuance:** the old "say so explicitly" sentence
  is not verbatim. Current explorer text permits "another owned path" while
  forbidding final-deliverable edits, solution design and silent merging. The
  operating boundary is retained; non-material wording difference.

## Target hashes

| Target | SHA-256 |
| --- | --- |
| `AGENTS.md` | `fc14adb23049f94a4b343949622bbdd40276a4d16aff72f7b872ffdbb1096f03` |
| `.agents/roles/orchestrator.md` | `1c33e0acc414d858a9fd2401ca0895e7386ddc668a3c73b105c34dde61b1de2a` |
| `.agents/roles/explorer.md` | `0fb9e682c102d0382995108b3c11bdf3575df0f1b342bfe53de513aaab6b635b` |
| `.agents/roles/worker.md` | `580a5d1945dff2a5efb4336a2eff689756cb2459910ad24b62f75c58b3d9491b` |
| `.agents/roles/verifier.md` | `1dc8d6e566ba6c875278f8f9d2d55f0a9eda27e21303d2c697f927e69ca235ac` |
| `.agents/skills/workflow-cli/SKILL.md` | `9c0eab4f3f3748cb749650a29a1d748d0f6ff96139da8c645f284619d17f37fc` |
| `.agents/skills/decompose-work/SKILL.md` | `08f4f4e46e1eb6f18c06c9bf41a07e96b56502adaf9ba48f7450db973dd0db76` |
| `.workflow/PRD.md` | `d69066a01a231419837a69b0f8b25f0025a530b9c0f4d3cfae9653a500a68933` |
| `.workflow/Architecture.md` | `cb3fdd5b17b5a31cf5eb5a2d88cd20f37cd7f61b3b2d8a0bdba58dc10c99e0ec` |

## Limits

- This is a documentation/prose verification plus the existing test suite. It
  does not prove real LLM role behaviour or harness startup.
- PHP 8.5.10 was used; the documented minimum 8.4.1 and native-mbstring-absent
  runtime were not executed here.
- Same-path target content was frozen by convention and recorded hashes; no
  content-fingerprint machinery exists or was assumed.
- The full suite reports one pre-existing PHPUnit deprecation; no failure.
