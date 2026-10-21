E-002 · Correct workflow inconsistencies and simplify coordination
kind: epic · status: in-progress · phase: design · parent: none · approval: current (user, 2026-10-20T18:48:14+00:00)
outcome: The kit reliably preserves records and agreed verification semantics while using a smaller consistent workflow and scaffold-only installation.
scope in: Repair record serialization and pre-save hierarchy/dependency validation, Unify completion rules and verification freshness with deliberate exceptions, Fix role-safe JSON and exact scope rendering plus path overlap and status visibility, Harden scaffold installation containment and preserve host rules and records, Consolidate protocol documentation and streamline phase/status bookkeeping without new gates, Keep discovery independent while permitting epic-hosted discovery and staged scope approval
scope out: Managed update engine or automatic host-document merging, Rigid phase transitions or additional approval gates, Per-artifact snapshot or content-hash infrastructure, Rewriting historical work records or modifying docs/handoff-compare.md
acceptance:
  1. Ordinary literal and multiline input round-trips without silent changes or unreadable records
  2. Supported writes reject invalid parent relationships and dependency cycles before persistence
  3. All completion routes require current successful independent verification or an explicit exception
  4. Verifier packets omit producer conclusions in text and JSON and scope review preserves exact values
  5. Equivalent owned paths report overlap and status --all exposes all items
  6. Scaffold installation rejects recursive destinations and preserves host rules and workflow records under documented manual merge semantics
  7. Runtime prerequisites and public examples match tested behavior
  8. Canonical operating rules document staged approval and independent discovery on epics with a concise small-work path
  9. Streamlined commands reduce bookkeeping without enforcing phase order and regression tests plus independent verification pass
depends: none · owns: none
plan: written · design: written
plan:
  Question: implement the agreed audit corrections while reducing coordination overhead without stronger process gates.
  Dimensions: record/lifecycle correctness; delegation/output/path correctness; scaffold installation and protocol simplification.
  Sources: current CLI and tests plus AGENTS and role/skill documentation; prior read-only audit findings are leads to recheck.
  Independent explorers run against this epic and write focused evidence under its outputs directory before implementation tasks are framed.
  Each explorer returns candidate deliverables with owned paths and regression checks; no implementation is authorized by discovery.
  Stop when every acceptance criterion has evidence and a candidate owner or a specific unresolved question requiring user choice.
  Design: synthesize one scope map with canonical decisions and integration contracts; then frame independently acceptable implementation children.
  Approval: review initial epic/discovery scope first and exact implementation child records after discovery; unchanged epic scope is not reapproved.
  Checks: focused regression tests followed by the full suite and workflow validation plus independent item and epic verification.
design:
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

## Decomposition
- T-009 · Preserve literal and multiline front matter
  kind: task · status: draft · phase: plan · parent: E-002 · approval: none
  outcome: Ordinary literal and multiline YAML values survive rendering and parsing without silent mutation or broken delimiters.
  scope in: Replace post-dump empty-array rewriting with the Symfony empty-sequence flag, Preserve YAML boundary and scalar terminal newlines while keeping closing delimiters separate, Add focused codec regressions for ordinary strings and nested data
  scope out: Changes to typed-item list trimming or Markdown-body normalization, Record migrations or edits to completed history
  acceptance:
    1. Scalar text containing brace-shaped JSON syntax remains unchanged after render and parse
    2. Final multiline scalars with zero, one or multiple trailing newlines retain their exact YAML values and parseable closing boundaries
    3. Unicode, colon text, delimiter-like content, nested unknown data and empty sequences round-trip correctly
    4. Empty arrays render as sequences without a global text replacement
    5. Existing body-normalization behavior and malformed-YAML rejection remain covered and passing
  depends: none · owns: cli/src/Domain/FrontMatter.php, cli/tests/Domain/FrontMatterTest.php
  plan: written · design: written
  plan:
    Question: how can literal YAML survive the existing front-matter boundary without schema changes?
    Dimensions: scalar preservation, delimiter framing and empty sequences.
    Sources: .workflow/items/E-002/outputs/record-lifecycle-discovery.md and FrontMatter with its current tests.
    Stop: each reported corruption has a focused failing regression and a minimal codec correction.
    Acceptance: run codec regressions and existing domain tests, distinguishing codec fidelity from typed-list normalization.
  design:
    Files: correct FrontMatter and extend FrontMatterTest only.
    Decision: use Symfony's empty-sequence flag, preserve the parse boundary newline and ensure a separate delimiter without trimming scalar data.
    Checks: literal text, final block scalars with terminal-newline variants, Unicode, nested values, empty arrays and invalid YAML.
    Risks: preserve existing body/list normalization and do not rewrite existing records.
    Upstream: .workflow/items/E-002/outputs/record-lifecycle-discovery.md and the parent Design are sufficient evidence, with no new broad exploration.
- T-010 · Harden contained scaffold installation
  kind: task · status: draft · phase: plan · parent: E-002 · approval: none
  outcome: Scaffold installation rejects recursive destinations and unsafe collisions while preserving host rules, configuration and workflow history even when force is requested.
  scope in: Symlink-aware existing-component resolution with missing suffix support and separator-aware containment guards, Create-only root host documents and safe default or forced kit-file collision behavior, Initializer and board-destination protection against symlink and type collisions, Installer guidance for runtime-only dependency bootstrap and manual merges
  scope out: Managed updates, provenance manifests or automatic document merges, Deleting target-only files or importing source workflow records, A transactional installer resistant to concurrent hostile filesystem replacement
  acceptance:
    1. Equal, descendant and ancestor destinations including root are rejected before writes, including missing suffixes reached through existing symlinks and parent segments
    2. Dangling links and non-directory intermediates are rejected rather than lexically bypassed
    3. Root AGENTS, README and LICENSE are created only when absent and preserved byte-for-byte even with --force
    4. Ordinary kit-relative collisions skip by default and refresh only when force is explicit, without merging or deleting target-only files
    5. Source and destination symlinks or file-directory collisions are held or rejected without following them, including workflow initializer and board paths
    6. Existing host config, gitignore, PRD, Architecture, item records and outputs remain intact, source workflow history is never copied and only a safe derived board may regenerate
    7. Guidance describes manual AGENTS merging and composer install --no-dev for runtime, with isolated filesystem regressions covering default and force behavior
  depends: none · owns: cli/src/Console/Command/InstallCommand.php, cli/src/Store/ProjectInitializer.php, cli/tests/Console/InstallScaffoldTest.php, cli/tests/Store/ProjectInitializerSafetyTest.php
  plan: written · design: written
  plan:
    Question: how can scaffold copies remain outside the kit and preserve host authority/history?
    Dimensions: component resolution, collision policy and initializer preservation.
    Sources: .workflow/items/E-002/outputs/scaffold-discovery.md, InstallCommand and ProjectInitializer.
    Stop: every agreed containment/collision case has a safe isolated refusal or preservation test.
    Acceptance: use temporary files and real symlinks to check pre-write refusal, default/force behavior and byte-preserved host records.
  design:
    Files: correct InstallCommand and ProjectInitializer with dedicated safety tests, without changing shared test infrastructure.
    Decision: resolve existing components before parent traversal, reject recursive destinations and links/type collisions, preserve root documents even with force and never merge/delete history.
    Checks: root/ancestor/source aliases, missing suffixes, real symlinks, force/default collisions, target-only files and initializer/board safety.
    Risks: safely clean up test-owned links, avoid external-target traversal and do not claim race-proof transactions.
    Upstream: .workflow/items/E-002/outputs/scaffold-discovery.md and parent Design fix runtime guidance and the scaffold-only contract.
- T-011 · Align runtime requirements and bootstrap guidance
  kind: task · status: draft · phase: plan · parent: E-002 · approval: none
  outcome: Composer platform metadata and bootstrap guidance accurately distinguish the locked runtime from development prerequisites without upgrading dependencies.
  scope in: Align PHP minimum with the locked Symfony runtime requirement, Runtime-only bootstrap guidance with separate development instructions, Narrow lock platform/content metadata refresh without package-version changes
  scope out: Dependency upgrades or broader dependency-graph changes, Duplicated runtime extension detectors or automatic package installation, Installer implementation or protocol prose owned by other children
  acceptance:
    1. composer.json and lock platform metadata agree on PHP at least 8.4.1 with all locked package versions unchanged
    2. Missing-autoload bootstrap guidance recommends composer install --no-dev for runtime and identifies full composer install as development setup
    3. Runtime checks account for Composer 2, iconv and polyfilled mbstring without falsely requiring native mbstring for runtime-only use
    4. A runtime-only installation and bootstrap are exercised separately from full development dependencies where environments are available
    5. Development verification records native mbstring, DOM, filter, JSON, libxml, tokenizer, XMLWriter and Phar requirements, and unavailable checks are explicitly unrun rather than claimed passing
  depends: none · owns: cli/composer.json, cli/composer.lock, cli/bin/workflow, cli/tests/Console/RuntimeBootstrapTest.php
  plan: written · design: written
  plan:
    Question: which prerequisites are actually required by the locked runtime versus development tooling?
    Dimensions: PHP platform floor, runtime extensions and bootstrap instructions.
    Sources: .workflow/items/E-002/outputs/scaffold-discovery.md, composer files and cli/bin/workflow.
    Stop: metadata and guidance agree with the existing lock graph without upgrades.
    Acceptance: compare package versions and platform metadata, then record runtime-only and development checks with honest environment limits.
  design:
    Files: narrowly update composer.json/lock platform metadata and bootstrap guidance, with a focused bootstrap test.
    Decision: require PHP at least 8.4.1 and Composer 2, use no-dev runtime setup and keep current locked package versions.
    Checks: Composer metadata validation, package-version equality, runtime-only bootstrap and separate full-suite development prerequisites.
    Risks: native development extensions exceed runtime needs, and missing clean environments must be reported unrun.
    Upstream: .workflow/items/E-002/outputs/scaffold-discovery.md and parent Design provide canonical requirements for installer and protocol writers.
- T-012 · Reject invalid record structure before persistence
  kind: task · status: draft · phase: plan · parent: E-002 · approval: none
  outcome: Create, save and mutate reject invalid parent relationships and dependency graphs atomically without introducing workflow gates.
  scope in: Extract shared pure structural checks from existing diagnostics, Validate candidates and affected graph relationships inside the existing write lock, Keep full-project diagnostics while allowing repairs despite unrelated legacy defects, Adapt invalid-record test fixtures to explicit raw fixture writes
  scope out: Approval, Plan, Design or phase-order gates on persistence, Lifecycle verification rules or automatic repair of legacy records, ID allocation redesign
  acceptance:
    1. Create, save and mutate reject invalid ID-kind identity, epic parents, task parents of the wrong kind and missing or wrong-kind subtask parents
    2. Missing references, self-parenting, self-dependencies and multi-item dependency cycles are rejected before persistence
    3. Changing a parent kind cannot silently invalidate existing child relationships
    4. Rejected store and command writes preserve the original item bytes and do not leave new item records
    5. Candidate and affected-graph checking allows a valid edit or structural repair when an unrelated legacy record has a structural defect
    6. Full validate still reports legacy defects and standalone-reason guidance remains a warning rather than a new gate
  depends: T-009 · owns: cli/src/Validate/StructureValidator.php, cli/src/Validate/ItemValidator.php, cli/src/Store/ItemStore.php, cli/src/Console/Command/NewCommand.php, cli/tests/Validate/ItemValidatorTest.php, cli/tests/Validate/StructureValidatorTest.php, cli/tests/Store/StructureWriteTest.php, cli/tests/Console/StructureCommandTest.php
  plan: written · design: written
  plan:
    Question: where can all supported writes share structural refusal without process gates?
    Dimensions: parent rules, reference/cycle checks and atomic candidate validation.
    Sources: .workflow/items/E-002/outputs/record-lifecycle-discovery.md, ItemStore and existing validator algorithms/tests.
    Stop: every supported persistence route and affected reverse relationship has a bounded regression.
    Acceptance: compare pure graph results with store/CLI refusal, unchanged bytes and unrelated-defect repair cases.
  design:
    Files: extract StructureValidator, integrate ItemStore and ItemValidator, adjust NewCommand as needed and own focused structure tests plus invalid-fixture adaptation.
    Decision: run pure candidate-plus-affected graph checks under the existing lock, not full workflow validation.
    Checks: every create/save/mutate route, cycles, parent-kind changes, raw legacy fixtures and rejection-before-write behavior.
    Risks: unrelated defects must not block repairs, standalone reasons stay guidance, and this writer continues through lifecycle then coordination on shared sources.
    Upstream: .workflow/items/E-002/outputs/record-lifecycle-discovery.md and parent Design define the seam and limits.
- T-013 · Make role packets safe in text and JSON
  kind: task · status: draft · phase: plan · parent: E-002 · approval: none
  outcome: Verifier delegation omits producer conclusions and arbitrary metadata in both output formats while preserving required inputs and literal JSON values.
  scope in: Allowlisted role-aware BriefCommand packet shared by text and JSON, Raw JSON output through BaseCommand, Repository-relative explorer output targets and focused role-isolation tests
  scope out: Changes to Item persistence or arbitrary-metadata storage, A general serialization or redaction framework, Semantic filtering of approved narrative content
  acceptance:
    1. Verifier text and JSON omit last_result, progress and unknown metadata, including producer-claim sentinel values
    2. Verifier packets retain acceptance, scope, Plan, Design, parent design, decisions, artifacts, dependencies and owned deliverable paths
    3. Worker and explorer packets retain appropriate role inputs and bounded last-result information without serializing the entire Item
    4. Console-style tags and Unicode in JSON string values survive exact decoding with decorated and undecorated output
    5. Explorer output targets use the repository-relative .workflow/items prefix and no unknown metadata is silently deleted from stored records
  depends: T-009 · owns: cli/src/Console/Command/BriefCommand.php, cli/src/Console/Command/BaseCommand.php, cli/tests/Console/BriefPacketTest.php, cli/tests/Console/JsonLiteralOutputTest.php
  plan: written · design: written
  plan:
    Question: which role inputs can be projected safely without exposing producer claims?
    Dimensions: field allowlisting, format parity and literal JSON output.
    Sources: .workflow/items/E-002/outputs/delegation-output-discovery.md, BriefCommand, BaseCommand and role contracts.
    Stop: all roles have explicit fields and sentinel regressions in both formats.
    Acceptance: decode exact JSON values and compare verifier exclusion with retained design, acceptance and artifact inputs.
  design:
    Files: keep a small allowlisted packet inside BriefCommand, emit raw JSON centrally in BaseCommand and add focused packet tests.
    Decision: share role-aware data between formats, omit verifier receipt/progress/unknown metadata and leave Item storage unchanged.
    Checks: role sentinels, text/JSON parity, decorated tags, Unicode and repository-relative explorer output paths.
    Risks: field-level blinding does not censor conclusions manually placed in approved design or decisions.
    Upstream: .workflow/items/E-002/outputs/delegation-output-discovery.md and parent Design define required fields and integration.
- T-014 · Unify completion and preserve completed history
  kind: task · status: draft · phase: plan · parent: E-002 · approval: none
  outcome: All supported completion routes require current successful verification or a deliberate exception, while material new work on completed items requires an explicit follow-up item.
  scope in: One shared completion and verification-invalidation rule at the store mutation boundary, Clear verification for tracked open-item target changes and producer or unsuccessful verifier receipts, Refuse material completed-item changes and reopening with explicit follow-up instructions, Keep a deliberate recorded close-force exception without fabricated verifier success
  scope out: Automatic reopening, successor creation or batch migration of completed records, New freshness hashes, versions, snapshots or artifact content scanning, Mandatory phase order or additional approval, Plan or Design gates, Combining result and close into a new command route
  acceptance:
    1. After verifier success on an open item, scope, Plan, Design, Decisions, ownership or artifact-reference changes clear verified_at without changing scope-approval semantics
    2. New worker or explorer receipts and non-done verifier receipts clear prior verification on open items, including a failed verifier after earlier success
    3. Successful independent verifier receipts establish verification for their resulting frozen target, while routine phase, status, priority and non-material progress changes alone do not invalidate it
    4. Close, set status=done and store create/save/mutate cannot bypass the shared completion rule with absent or stale verification
    5. Explicit close --force records an unverified exception without inventing successful verification and cannot override structural or completed-history protection
    6. Material scope, Plan, Design, Decisions, owned-path or artifact-reference changes, reopening, new producer receipts and unsuccessful verifier receipts on done items fail before persistence with an explicit instruction to create a new follow-up item
    7. Rejected completed-item changes preserve original bytes, create no successor and do not touch existing done history, while non-material notes or priority corrections are not blanket-blocked
    8. Phase never infers status and same-path external artifact changes are documented as freeze/receipt-based rather than automatically detected
  depends: T-012 · owns: cli/src/Domain/ItemLifecycle.php, cli/src/Store/ItemStore.php, cli/src/Validate/ItemValidator.php, cli/src/Console/Command/ResultCommand.php, cli/src/Console/Command/CloseCommand.php, cli/tests/Domain/ItemLifecycleTest.php, cli/tests/Store/LifecycleWriteTest.php, cli/tests/Console/LifecycleCommandTest.php, cli/tests/Console/CommandTest.php
  plan: written · design: written
  plan:
    Question: how can one verification rule cover all writes while keeping completed work historical?
    Dimensions: open-target invalidation, completion routes and explicit follow-up refusal.
    Sources: .workflow/items/E-002/outputs/record-lifecycle-discovery.md, the user's done-history decision and the structure persistence seam.
    Stop: each completion bypass and done-history mutation has a refusal or deliberate-exception regression.
    Acceptance: exercise pure lifecycle cases and CLI/store rollback with current success, failed verifier, target changes and forced closure.
  design:
    Files: add ItemLifecycle at the existing store seam, align Result/Close and validator diagnostics, and be the sole writer of existing CommandTest.
    Decision: compare tracked before/after fields and receipt intent, clear existing verification on open-target changes, reject material done changes and require explicit follow-up creation.
    Checks: all completion routes, successful versus unsuccessful receipts, artifact-reference changes, force exceptions, byte-preserving refusal and no phase-driven status.
    Risks: no automatic same-path external change detection or new freshness metadata, and use the same writer as structure and subsequent coordination.
    Upstream: .workflow/items/E-002/outputs/record-lifecycle-discovery.md and parent Design supersede earlier auto-reopen proposals.
- T-015 · Render and capture exact reviewed scope
  kind: task · status: draft · phase: plan · parent: E-002 · approval: none
  outcome: Review and approval preserve distinct scope values and approval output identifies the exact values and hash captured by the approved mutation.
  scope in: Small shared exact scope projection and literal output for Review and Approve, Capture actual approved scope and hash together under the existing mutation lock, Add Review JSON using the same scope and decomposition packet
  scope out: Changes to scopeHash identity or approval invalidation rules, Generic rendering frameworks or changes to stored list normalization, Changes to unrelated bounded summaries
  acceptance:
    1. Text scope output distinguishes separate list values from comma-containing values and represents empty arrays, empty strings, null and multiline strings unambiguously
    2. Kind, parent, title, outcome, scope-in, scope-out, acceptance and dependencies retain exact values without truncation or Console-tag interpretation
    3. Review and Approve use the same small scope projection and preserve owned paths as contextual data outside the approval hash
    4. Approve text and JSON report the actual scope and hash captured together in the locked mutation rather than a later reload
    5. Review --json includes the epic and descendants with exact scope, dependency, ownership, approval and Plan/Design information
    6. Existing scope-hash stability and acceptance-order semantics remain unchanged
  depends: T-013 · owns: cli/src/Support/ScopeOutput.php, cli/src/Console/Command/ReviewCommand.php, cli/src/Console/Command/ApproveCommand.php, cli/tests/Console/ScopeOutputTest.php
  plan: written · design: written
  plan:
    Question: how can a human and script compare the exact reviewed and approved scope?
    Dimensions: literal values, list boundaries and atomic approval capture.
    Sources: .workflow/items/E-002/outputs/delegation-output-discovery.md, Review/Approve and existing ItemScope tests.
    Stop: adversarial literal fixtures remain distinguishable and approval returns the mutation's captured packet.
    Acceptance: verify text/JSON exactness, Review JSON tree coverage and unchanged scope-hash semantics.
  design:
    Files: introduce one small ScopeOutput helper consumed by Review and Approve plus a focused command test file.
    Decision: use unambiguous literal field/list rendering and capture scope plus hash inside approval mutation, reusing raw JSON from delegation.
    Checks: tags, Unicode, multiline/empty/null values, distinct list boundaries, descendant packets and existing scope-hash tests.
    Risks: display order may change but scopeHash semantics and Item persistence must not.
    Upstream: .workflow/items/E-002/outputs/delegation-output-discovery.md and parent Design are the exact-output contract.
- T-016 · Make coordination projections complete and path-consistent
  kind: task · status: draft · phase: plan · parent: E-002 · approval: none
  outcome: Requested full status exposes every item and ready/validate agree on overlapping literal owned paths while default coordination reads stay bounded.
  scope in: Remove every status section cap only when --all is requested, Include done and cancelled groups in status --all JSON, One shared lexical owned-path comparator used by ready and validate
  scope out: Globs, realpath, symlink identity or filesystem-existence matching for ownership, Stored owned-path rewrites or new ownership gates, Changing default JSON behavior or introducing pagination
  acceptance:
    1. Default text status keeps its current bounded section limits
    2. Status --all lists every draft, active, ready, waiting, decision and terminal item without hidden section caps
    3. Status --all JSON includes done and cancelled items as well as all existing working-set groups
    4. Equivalent slash, dot-segment and repeated-separator paths report overlap in both ready and validate even when paths do not yet exist
    5. Project-root ownership is distinct from empty ownership and component boundaries prevent src/a from overlapping src/ab
    6. Stored paths remain unchanged and dependency-ordered ownership behavior is preserved
  depends: T-014 · owns: cli/src/Console/Command/StatusCommand.php, cli/src/Validate/ItemValidator.php, cli/src/Read/ProjectView.php, cli/tests/Console/CoordinationProjectionTest.php, cli/tests/Validate/OwnedPathTest.php
  plan: written · design: written
  plan:
    Question: do coordination projections expose the requested working set and consistent overlap evidence?
    Dimensions: bounded versus all status and lexical owned-path equivalence.
    Sources: .workflow/items/E-002/outputs/delegation-output-discovery.md, StatusCommand, ItemValidator and ProjectView.
    Stop: every section exceeds its default cap in fixtures and every agreed lexical alias has a table case.
    Acceptance: compare all/default text and JSON plus ready/validate overlap findings without rewriting stored paths.
  design:
    Files: adjust StatusCommand and the existing shared comparator in ItemValidator, keep ProjectView using it and add focused projection/path tests.
    Decision: treat completeness and lexical overlap as one accurate coordination-read outcome, with no filesystem identity or new gates.
    Checks: all status groups, default caps, terminal JSON, root versus empty ownership, component boundaries and future-path aliases.
    Risks: lexical ownership is intentionally different from installer resolution, and the same writer must follow structure/lifecycle for ItemValidator.
    Upstream: .workflow/items/E-002/outputs/delegation-output-discovery.md and parent Design define projection limits.
- T-017 · Combine optional plan and design state updates
  kind: task · status: draft · phase: plan · parent: E-002 · approval: none
  outcome: Plan and design can optionally update phase and start status atomically without changing content-only defaults or enforcing a phase sequence.
  scope in: Optional --phase and --start flags on existing plan and design commands, Validate content and flags before one atomic store mutation, Refresh the derived board after successful updates
  scope out: Automatic status inference from phase or implicit phase rewinds, Mandatory phase order, readiness or additional approval gates, Result-close combination or a generic command framework, Bypassing completed-history protections
  acceptance:
    1. Without new flags plan/design replace content only and leave current status and phase unchanged
    2. An explicit valid --phase changes phase in the same mutation as content and never infers status
    3. Only explicit --start requests in-progress status and existing set remains available for manual corrections
    4. Invalid phase, invalid content source or unsupported input leaves content, status and phase unchanged
    5. Successful changes refresh the derived board and preserve approval unless a scope field actually changed
    6. Done-item material edits and --start reopening are refused by the shared lifecycle rule with follow-up instructions
  depends: T-014 · owns: cli/src/Console/Command/PlanCommand.php, cli/src/Console/Command/DesignCommand.php, cli/tests/Console/PlanDesignStateTest.php
  plan: written · design: written
  plan:
    Question: can existing content commands remove routine bookkeeping calls without becoming a state machine?
    Dimensions: optional flags, atomic validation and derived-board refresh.
    Sources: .workflow/items/E-002/outputs/protocol-slimming-discovery.md, PlanCommand, DesignCommand and lifecycle protection.
    Stop: no-flag compatibility and flagged success/refusal cases have focused regressions.
    Acceptance: compare persisted content/state/approval and board after valid, invalid and completed-item calls.
  design:
    Files: add optional phase/start flags directly to PlanCommand and DesignCommand with one focused test file.
    Decision: validate before a single content/state mutation, never infer status from phase and retain content-only defaults and manual set.
    Checks: absent flags, explicit phase, explicit start, rollback, board refresh, approval stability and done-history refusal.
    Risks: reuse lifecycle enforcement rather than duplicating completion logic, and deliberately defer result-close combination.
    Upstream: .workflow/items/E-002/outputs/protocol-slimming-discovery.md and parent Design contain the accepted bounded streamlining.
- T-018 · Consolidate the operating protocol and public examples
  kind: task · status: draft · phase: plan · parent: E-002 · approval: none
  outcome: One concise canonical protocol and consistent executable examples explain staged approval, independent discovery, small work, verification exceptions, completed history and scaffold-only installation.
  scope in: Keep AGENTS canonical and shorten repeated operating rules across roles, skills and introductory docs, Document epic-hosted independent discovery, staged child approval and one scope map in epic Design, Make small-work and deliberate verification-exception paths explicit without new gates, Align completion-history, command, runtime and scaffold examples with implemented behavior, Verify isolated public examples and installed-role canonical loading
  scope out: A new default protocol document or managed host-document merger, Changes to historical design notes, handoff comparisons or completed item records, OpenCode schema redesign, host harness replacement or additional protocol gates
  acceptance:
    1. AGENTS is the single operating-rule source and every installed role explicitly loads the project's root AGENTS, while missing or conflicting host rules are surfaced rather than overwritten
    2. Independent discovery may be recorded on an epic and a separate discovery child is optional only when useful as its own deliverable
    3. Approval is staged epic/discovery then exact children after evidence, unchanged epic scope is not reapproved and later evidence reapproves only affected changed scopes
    4. Epic Design holds the sole canonical scope map and child records reference parent design rather than duplicating decomposition
    5. Small work uses one item, short exact approval, one-line Plan/Design and justified no-exploration where appropriate, with independent verification normally required and an explicit deliberate exception available
    6. Completed items remain historical and material changes or new producer/unsuccessful verifier work require an explicit new follow-up item, with no automatic reopen, successor generation or batch migration
    7. README quickstart and install smoke examples run in isolated roots with valid hierarchy, repository-relative artifact paths, verifier/close flow and no deletion of item history
    8. Runtime versus development prerequisites, safe force/manual-merge semantics, optional phase/start flags, exhaustive all output and exact role packets match implemented commands
    9. PRD/Architecture describe product mechanisms and limits rather than duplicate the operating loop, stale fake paths and warning-only closure claims are corrected, and historical/user-owned files remain untouched
  depends: T-015, T-016, T-010, T-011, T-017 · owns: AGENTS.md, README.md, docs/install.md, .agents/roles/orchestrator.md, .agents/roles/explorer.md, .agents/roles/worker.md, .agents/roles/verifier.md, .agents/skills/workflow-cli/SKILL.md, .agents/skills/decompose-work/SKILL.md, .agents/skills/install-workflow/SKILL.md, .agents/skills/fresh-session-handoff/SKILL.md, .agents/skills/verify-outcome/SKILL.md, .workflow/PRD.md, .workflow/Architecture.md, cli/tests/Console/ProtocolExamplesTest.php
  plan: written · design: written
  plan:
    Question: what is the smallest consistently loadable operating contract for the agreed workflow?
    Dimensions: canonical rules, role/skill duplication and executable public examples.
    Sources: all four .workflow/items/E-002/outputs discovery reports, the implemented child contracts and the parent Design.
    Stop: every accepted policy and public example has one canonical home and no contradictory active instruction.
    Acceptance: inspect canonical loading and stale language, run isolated quickstart/install examples and verify preserved historical/user-owned paths.
  design:
    Files: one writer owns 14 related prose files and one focused example test, leaving generic templates, historical design notes and docs/handoff-compare.md untouched.
    Decision: keep AGENTS canonical, roles short, skills procedural, README introductory and PRD/Architecture product-focused, with manual host-rule merging.
    Checks: staged exact approval, optional discovery children, small work, explicit verification exceptions, done-history follow-ups, valid examples and implemented runtime/scaffold/command contracts.
    Risks: this broad prose ownership is justified by one operating contract and one voice, not multiple independent writers or a new protocol source.
    Upstream: .workflow/items/E-002/outputs/record-lifecycle-discovery.md, .workflow/items/E-002/outputs/delegation-output-discovery.md, .workflow/items/E-002/outputs/scaffold-discovery.md and .workflow/items/E-002/outputs/protocol-slimming-discovery.md plus parent Design.

counts: 10 task(s), 0 subtask(s), 10 open item(s); review the exact scope above before approval.
