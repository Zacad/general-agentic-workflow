---
id: E-002
kind: epic
parent: null
title: 'Correct workflow inconsistencies and simplify coordination'
status: done
phase: verify
priority: 2
depends_on: []
owns: []
artifacts:
  - .workflow/items/E-002/outputs/record-lifecycle-discovery.md
  - .workflow/items/E-002/outputs/delegation-output-discovery.md
  - .workflow/items/E-002/outputs/scaffold-discovery.md
  - .workflow/items/E-002/outputs/protocol-slimming-discovery.md
  - .workflow/items/E-002/outputs/baseline-checks.md
  - .workflow/items/E-002/outputs/design-check.md
  - .workflow/items/E-002/outputs/implementation-review.md
  - .workflow/items/T-009/outputs/verification.md
  - .workflow/items/T-010/outputs/verification.md
  - .workflow/items/T-011/outputs/verification.md
  - .workflow/items/T-012/outputs/verification-recheck.md
  - .workflow/items/T-013/outputs/verification.md
  - .workflow/items/T-014/outputs/verification.md
  - .workflow/items/T-015/outputs/verification.md
  - .workflow/items/T-016/outputs/verification.md
  - .workflow/items/T-017/outputs/verification.md
  - .workflow/items/T-018/outputs/verification.md
  - .workflow/items/E-002/outputs/integration-verification.md
approval:
  at: '2026-10-20T18:48:14+00:00'
  by: user
  scope_hash: a3a468176bfff80d5749524d1197a66ae75e990ca34ab3303598e2bf0031bf4b
last_result:
  at: '2026-10-21T07:03:50+00:00'
  role: verifier
  status: done
  finding: 'All nine epic criteria pass; the full suite passed 699 tests and 5567 assertions with frozen targets and historical/user files unchanged. Actual PHP 8.4.1, native-mbstring-absent execution and real LLM startup remain explicitly unrun.'
  decision: null
verified_at: '2026-10-21T07:03:50+00:00'
created: '2026-10-20T18:46:55+00:00'
updated: '2026-10-21T07:03:59+00:00'
outcome: 'The kit reliably preserves records and agreed verification semantics while using a smaller consistent workflow and scaffold-only installation.'
scope:
  in:
    - 'Repair record serialization and pre-save hierarchy/dependency validation'
    - 'Unify completion rules and verification freshness with deliberate exceptions'
    - 'Fix role-safe JSON and exact scope rendering plus path overlap and status visibility'
    - 'Harden scaffold installation containment and preserve host rules and records'
    - 'Consolidate protocol documentation and streamline phase/status bookkeeping without new gates'
    - 'Keep discovery independent while permitting epic-hosted discovery and staged scope approval'
  out:
    - 'Managed update engine or automatic host-document merging'
    - 'Rigid phase transitions or additional approval gates'
    - 'Per-artifact snapshot or content-hash infrastructure'
    - 'Rewriting historical work records or modifying docs/handoff-compare.md'
acceptance:
  - 'Ordinary literal and multiline input round-trips without silent changes or unreadable records'
  - 'Supported writes reject invalid parent relationships and dependency cycles before persistence'
  - 'All completion routes require current successful independent verification or an explicit exception'
  - 'Verifier packets omit producer conclusions in text and JSON and scope review preserves exact values'
  - 'Equivalent owned paths report overlap and status --all exposes all items'
  - 'Scaffold installation rejects recursive destinations and preserves host rules and workflow records under documented manual merge semantics'
  - 'Runtime prerequisites and public examples match tested behavior'
  - 'Canonical operating rules document staged approval and independent discovery on epics with a concise small-work path'
  - 'Streamlined commands reduce bookkeeping without enforcing phase order and regression tests plus independent verification pass'
---

## Plan

Question: implement the agreed audit corrections while reducing coordination overhead without stronger process gates.
Dimensions: record/lifecycle correctness; delegation/output/path correctness; scaffold installation and protocol simplification.
Sources: current CLI and tests plus AGENTS and role/skill documentation; prior read-only audit findings are leads to recheck.
Independent explorers run against this epic and write focused evidence under its outputs directory before implementation tasks are framed.
Each explorer returns candidate deliverables with owned paths and regression checks; no implementation is authorized by discovery.
Stop when every acceptance criterion has evidence and a candidate owner or a specific unresolved question requiring user choice.
Design: synthesize one scope map with canonical decisions and integration contracts; then frame independently acceptable implementation children.
Approval: review initial epic/discovery scope first and exact implementation child records after discovery; unchanged epic scope is not reapproved.
Checks: focused regression tests followed by the full suite and workflow validation plus independent item and epic verification.

## Design

### Direction and evidence

Correct the existing seams rather than add a state machine or updater. Priorities are record integrity, independent verification with explicit exceptions, and low coordination overhead. Evidence: `.workflow/items/E-002/outputs/record-lifecycle-discovery.md`, `.workflow/items/E-002/outputs/delegation-output-discovery.md`, `.workflow/items/E-002/outputs/scaffold-discovery.md`, `.workflow/items/E-002/outputs/protocol-slimming-discovery.md`. These source-checked reports distinguish inherited experiments from checks actually run; they are not implementation verification.

Baseline evidence: `.workflow/items/E-002/outputs/baseline-checks.md` records the pre-implementation run at commit `3ec52c68cd683cee8c7a5ca4efd74dbdb769ec3c`: 48 tests, 173 assertions, exit 0, with one deprecated-option warning for `--do-not-cache-result`; workflow validation exited 0 with 0 warnings. These observations do not verify the proposed implementation and were not rerun for this refinement.

This epic Design owns the canonical scope map, not another deliverable to copy into every child. The ten allocated child records below are framed but not approved; their actual records own exact scopes, acceptance, paths, dependencies and Plan/Design. Present `workflow review E-002` for approval of those child scopes. Initial epic scope remains approved. Later evidence can revise affected scopes explicitly; an unchanged epic is not reapproved.

### Children and coverage

| Item | Independently acceptable outcome | Depends on | Epic acceptance |
| --- | --- | --- | --- |
| T-009 (codec) | Literal and multiline front matter survives round trips | none | 1 |
| T-010 (scaffold) | Contained scaffold copying preserves host rules and records | none | 6 |
| T-011 (runtime) | Locked runtime requirements and bootstrap guidance agree | none | 7 |
| T-012 (structure) | Supported writes reject invalid hierarchy and cycles atomically | T-009 | 2 |
| T-013 (delegation) | Role-safe literal text/JSON delegation packets | T-009 | 4 |
| T-014 (lifecycle) | One current-verification completion rule preserves done history | T-012 | 3,9 |
| T-015 (scope-output) | Exact review and approval values match approved scope/hash | T-013 | 4,7 |
| T-016 (coordination) | Complete requested status and consistent lexical overlap findings | T-014 | 5 |
| T-017 (bookkeeping) | Optional atomic plan/design state flags reduce calls | T-014 | 9 |
| T-018 (protocol) | One concise operating contract and executable public examples | T-015, T-016, T-010, T-011, T-017 | 7,8,9 |

Each child has one writer and a focused regression surface. T-012, T-014 and T-016 must use the same writer sequentially for ItemValidator; T-012 then T-014 likewise share ItemStore. T-014 alone owns the existing CommandTest; all other code children use new focused test files. No Domain/Item persistence or scope-hash change is planned. T-010 owns InstallCommand/ProjectInitializer; T-011 owns composer files/bin; T-018 owns all operating prose. Exact ownership is defined by the actual child records.

### Integration contracts

**Records:** Codec uses Symfony's empty-sequence dump flag instead of regex replacement, preserves YAML's boundary newline and separates the closing delimiter without trimming scalar data. Existing typed-list trimming/body normalization is not a new schema migration. Structure extracts a pure parent/dependency validator shared by full diagnostics and locked create/save/mutate, checking the proposed item and affected graph so unrelated legacy defects do not block repairs. No full workflow gate on writes.

**Lifecycle:** A shared before/after persistence rule clears verification on tracked open-item scope, Plan/Design/Decisions, owned/artifact-reference changes, producer receipts and unsuccessful verifier receipts. Successful independent verifier receipts establish verification for their resulting frozen target. All supported routes entering done, including set and store writes, check that rule. Only deliberate `close --force` permits unverified completion, records an exception and never fabricates a verifier success or bypasses structural/history protection. Phase never implies status. Scope approval continues to use existing scope_hash; verification invalidation does not prompt scope reapproval by itself.

Done items stay historical. Refuse material scope/design/target changes, reopening, new producer receipts and unsuccessful verifier receipts with a clear instruction to create an explicit follow-up item. Never auto-reopen, auto-create successors or batch-migrate history. Non-material notes and priority corrections are not a blanket new freeze gate. Same-path external artifact replacement remains convention/receipt-based: freeze named paths, report changes and reverify before closure. No new freshness fingerprint, version or snapshot metadata.

**Packets:** BriefCommand builds an allowlisted role packet for both formats; verifier packets exclude receipt/progress/unknown metadata while retaining acceptance, design, parent design, decisions and artifacts. BaseCommand emits JSON raw. Review/Approve share a small exact scope projection/literal renderer, not a generic serializer; Approve captures values and hash under the same mutation. Preserve scopeHash semantics and distinct list/empty/multiline/tag values. Review JSON reuses the same packet. Role blinding is field-based, not censorship of approved narrative.

**Coordination:** Preserve bounded defaults. Status all removes every section cap and adds terminal JSON groups. One lexical comparator handles literal project-relative ownership aliases and component-boundary ancestry, with project root distinct from empty ownership. No realpath, glob or existence semantics; stored paths remain unchanged.

**Scaffold/runtime:** Resolve existing destination components symlink-aware before subsequent parent segments, retaining missing suffixes; reject equality/ancestors/descendants including root before writing. Hold/reject symlink/type collisions. Root AGENTS/README/LICENSE are create-only even with force; kit-relative ordinary files skip unless explicitly forced. Never overwrite host config/gitignore or target workflow history; never import source records; only a safe derived board may regenerate. No merge/delete engine or manifest. PHP at least 8.4.1, Composer 2, runtime iconv/polyfilled mbstring, runtime-only no-dev installation; document native development extensions separately. Preserve locked package versions. Installer owns its own guidance strings; runtime owns bootstrap guidance.

**Commands/protocol:** Add optional phase and start flags only to plan/design, validated before one atomic mutation and followed by board refresh. No flags means content-only/no rewind; explicit phase alone never changes status. Keep set and the two-call verifier receipt/close path; defer result-close combination to avoid duplicated completion logic. Keep AGENTS canonical instead of adding another protocol file. Every role explicitly loads root AGENTS; manual installation merges those rules into preserved host instructions. README introduces and demonstrates; skills provide procedures; PRD/Architecture describe product/mechanisms. Fix stale discovery-task requirements, examples and warning-only closure claims. Historical design notes and docs/handoff-compare.md remain untouched.

### Sizing and verification

No split would mix four evidence dimensions and conflicting writers; splitting every defect would create paragraph-sized bureaucracy. Ten sibling tasks follow independently testable safety/output/install/protocol boundaries; no subtasks are needed. Coordination's status/ownership checks form one accurate read-projection outcome. Protocol intentionally spans 14 prose files plus one focused example test because one operating contract needs one voice; the evidence is already separated. Discovery stays on this approved epic, not an extra retrospective task.

Prefer pure edge-case tables plus temporary-project store/command tests over brittle output snapshots alone. Require rejection-before-write checks, legacy-defect repair tests, done-history byte preservation, decorated/undecorated exact packets, all status sections, real isolated symlink cases and runtime/development prerequisite checks. Workers run focused tests; independent verifiers check frozen deliverables against each child's acceptance/design. At epic verification, run the full suite, workflow validation, safe isolated scaffold/bootstrap/quickstart checks and coverage review; report preexisting warnings and unrun environments honestly. Existing done-item histories and the user's handoff comparison must be unchanged. No implementation is authorized until exact child scopes are approved.

## Progress

- 2026-10-20T19:12:38+00:00 · explorer · blocked
  Finding: Independent record/lifecycle discovery confirmed serialization and verification defects plus missing pre-save structural checks. The read-only explorer could not persist its report.
  Artifacts: none
  Decision: none
  Next: Persist discovery through a write-enabled agent.

- 2026-10-20T19:12:38+00:00 · explorer · blocked
  Finding: Independent delegation/output discovery confirmed verifier JSON leakage and ambiguous scope output plus incomplete status and path comparisons. The read-only explorer could not persist its report.
  Artifacts: none
  Decision: none
  Next: Persist discovery through a write-enabled agent.

- 2026-10-20T19:12:39+00:00 · explorer · blocked
  Finding: Independent scaffold discovery confirmed symlink and root-ancestor containment bypasses and established the scaffold/manual-merge contract. The read-only explorer could not persist its report.
  Artifacts: none
  Decision: none
  Next: Persist discovery through a write-enabled agent.

- 2026-10-20T19:12:39+00:00 · explorer · blocked
  Finding: Independent protocol discovery identified canonical rule ownership and explicit content/state flags plus stale examples. The read-only explorer could not persist its report.
  Artifacts: none
  Decision: none
  Next: Persist discovery through a write-enabled agent.

- 2026-10-20T19:12:39+00:00 · explorer · done
  Finding: Persisted four source-checked reports and prepared ten candidate scopes and an epic Design. Candidate schema and dependency/ownership checks pass without implementation changes.
  Artifacts: .workflow/items/E-002/outputs/record-lifecycle-discovery.md, .workflow/items/E-002/outputs/delegation-output-discovery.md, .workflow/items/E-002/outputs/scaffold-discovery.md, .workflow/items/E-002/outputs/protocol-slimming-discovery.md
  Decision: none
  Next: Frame the candidate children for exact-record scope review and approval.

- 2026-10-20T19:12:39+00:00 · explorer · done
  Finding: Existing suite passed with 48 tests and 173 assertions and one deprecated-option warning; workflow validation passed with zero warnings. Minimum PHP runtimes remain untested.
  Artifacts: .workflow/items/E-002/outputs/baseline-checks.md
  Decision: none
  Next: Use the baseline to define regression and integration checks.

- 2026-10-20T19:17:23+00:00 · explorer · done
  Finding: Independent pre-implementation scope/design review found no blockers and two implementation advisories. This is not outcome verification and does not mark the epic verified.
  Artifacts: .workflow/items/E-002/outputs/design-check.md
  Decision: none
  Next: Present exact child scopes for approval and retain documented writer ordering and test ownership.

- 2026-10-20T19:17:33+00:00 · orchestrator · needs-decision
  Finding: Discovery and the independent scope/design check are complete; ten implementation children have exact scope and owned paths with Plans and Designs. Their generated review packet awaits user approval and no implementation files have changed.
  Artifacts: .workflow/items/E-002/outputs/implementation-review.md
  Decision: Approve the exact T-009 through T-018 scopes in the implementation review packet.
  Next: Record child approvals after confirmation, then dispatch dependency-safe workers.

- 2026-10-21T04:51:18+00:00 · orchestrator · done
  Finding: User confirmed the exact T-009 through T-018 implementation packet. All child scopes are approved; implementation proceeds with dependency-safe ownership and independent verification.
  Artifacts: .workflow/items/E-002/outputs/implementation-review.md
  Decision: none
  Next: Start independent front-matter, scaffold and runtime workers.

- 2026-10-21T06:48:35+00:00 · orchestrator · done
  Finding: All ten approved implementation children are independently verified and closed. The consolidated protocol and corrected CLI now await the independent frozen integration check against epic acceptance.
  Artifacts: .workflow/items/T-009/outputs/verification.md, .workflow/items/T-010/outputs/verification.md, .workflow/items/T-011/outputs/verification.md, .workflow/items/T-012/outputs/verification-recheck.md, .workflow/items/T-013/outputs/verification.md, .workflow/items/T-014/outputs/verification.md, .workflow/items/T-015/outputs/verification.md, .workflow/items/T-016/outputs/verification.md, .workflow/items/T-017/outputs/verification.md, .workflow/items/T-018/outputs/verification.md
  Decision: none
  Next: Independently verify the frozen integrated outcome against all epic acceptance criteria.

- 2026-10-21T07:03:50+00:00 · verifier · done
  Finding: All nine epic criteria pass; the full suite passed 699 tests and 5567 assertions with frozen targets and historical/user files unchanged. Actual PHP 8.4.1, native-mbstring-absent execution and real LLM startup remain explicitly unrun.
  Artifacts: .workflow/items/E-002/outputs/integration-verification.md
  Decision: none
  Next: Close E-002 as independently verified.

- 2026-10-21T07:03:59+00:00 · closed as done

## Decisions

- 2026-10-20T18:47:17+00:00 · User agreed: discovery must be an independent run but may be recorded on the epic; installation stays scaffold-based with manual merges; the other audit corrections and simplification proposals are accepted. Preserve scope-only staged approval and default independent verification with deliberate exceptions. Discovery runs on this epic before implementation children are framed.

- 2026-10-20T19:02:33+00:00 · Completed-item policy confirmed by user: preserve the completed item as history; material scope/design changes and new producer or unsuccessful verifier work belong in a new item. Do not reopen automatically or generate successor drafts. Refuse material changes to completed work with a clear instruction to create an explicit follow-up item; keep creation and scope approval explicit.
