# General Agentic Workflow — architecture

## Shape and boundaries

Markdown is truth. A project-local Symfony Console application reads records,
projects role-safe views, serializes mutations and generates a static board.
Root `AGENTS.md` owns operating rules; roles define responsibilities, skills
provide methods, and README/install docs introduce executable use. There is no
second canonical protocol or default mandatory artifact.

Key source locations (relative to the repository root):

| Component | Source |
| --- | --- |
| Application/commands | `cli/src/Console/Application.php`, `cli/src/Console/Command/` |
| Typed item, enums, approval | `cli/src/Domain/Item.php`, `cli/src/Domain/` |
| YAML framing | `cli/src/Domain/FrontMatter.php` |
| Completion/invalidation policy | `cli/src/Domain/ItemLifecycle.php` |
| Persistence/project discovery | `cli/src/Store/ItemStore.php`, `cli/src/Store/Project.php` |
| Candidate graph/full diagnostics | `cli/src/Validate/StructureValidator.php`, `cli/src/Validate/ItemValidator.php` |
| Coordination projection | `cli/src/Read/ProjectView.php` |
| Exact scope presentation | `cli/src/Support/ScopeOutput.php` |
| Derived board | `cli/src/Board/BoardRenderer.php`, `cli/src/Board/BoardWriter.php` |
| Scaffold initialization | `cli/src/Store/ProjectInitializer.php`, `cli/templates/` |

There is no separate Receipt persistence layer: ResultCommand builds the receipt
and ItemStore appends it to Progress while updating typed result/artifact fields.

## Storage and write boundary

Project documents are `.workflow/PRD.md` and `Architecture.md`. Each item has
`items/<ID>/item.md` and optional `outputs/`; board and `.locks/` are derived/
transient. IDs use E/T/S prefixes allocated by scanning existing item directories.
YAML carries identity, hierarchy, status, phase, priority, dependencies, ownership,
artifacts, approval, result/verification timestamps, outcome, scope and acceptance.
Markdown carries Plan, Design, Decisions and Progress.

FrontMatter uses Symfony YAML with empty-sequence dump flags rather than scalar
rewriting. Parse framing retains the YAML boundary newline; the closing delimiter
is separated without trimming scalar data. Unknown keys survive; existing typed
list normalization and Markdown-body normalization are separate contracts.

Create/save/mutate share a locked persistence boundary. Pure StructureValidator
checks the candidate and affected parent/dependency graph before writing, without
turning unrelated legacy diagnostics into a global mutation gate. ItemValidator
adds whole-project diagnostics: approval, layout, artifacts, ownership and advisory
Plan/Design warnings. Structural checks are not a mandatory process state machine.

An item write holds a Symfony FlockStore project lock, serializes to a same-directory
temporary file, flushes/fsyncs and renames it over the record. Readers see old or
new bytes, not partial serialization. Derived board refresh is separate from item
persistence; it is rebuildable, not a multi-file transaction or recovery journal.

## Approval versus verification

Item::scopeHash is SHA-256 of canonical JSON over kind, parent, title, outcome,
scope in/out, acceptance and dependencies. Scope lists/dependencies are deduplicated
and sorted; acceptance order is preserved. Approval stores actor/time/hash.
Status, phase, priority, ownership, narrative, artifacts and receipts do not affect
scope approval. Stale approval is diagnosed and withheld from ready projections.

ScopeOutput shares an exact projection/literal renderer between ReviewCommand and
ApproveCommand. Approve captures displayed values and hash in the same locked
mutation; review includes descendants and full Plan/Design. Empty/list/multiline/
tag values remain distinguishable. JSON writes bypass Console formatting.

ItemLifecycle compares before/after tracked target fields and Plan/Design/Decisions
sections at persistence. Open-item target changes, producer receipts and non-done
verifier receipts clear verification. Explicit successful verifier receipt intent
establishes `verified_at` for the resulting target, including added artifact paths;
supplying a timestamp or verifier-shaped metadata through ordinary save cannot mint
proof. Current proof also requires a matching last successful verifier receipt.

Every supported transition into done uses this rule. CloseCommand normally refuses
missing current proof; only explicit `close --force` records an unverified exception,
without fabricating success or bypassing structural/history protection. Receipt and
close remain separate calls. Already-done legacy records are preserved, not migrated
or retroactively certified.

For done records, material target changes, reopening, producer work and unsuccessful
verification are refused with explicit follow-up guidance. No automatic successor
or reopen mechanism exists. Non-material notes/priority remain possible. Same-path
external artifact edits are outside detection: named targets freeze by convention
and receipts, not content fingerprints or snapshots. CLI role labels do not prove
real agent independence.

## Read projections

ProjectView computes todo/current-approval/done-dependency readiness. Ownership
overlap is a separate reported fact, not silently filtered readiness. The shared
lexical comparator normalizes separators/dot segments and compares literal
project-relative equality/ancestry on component boundaries. Root ownership differs
from empty ownership; no glob, realpath, symlink or existence semantics apply.

Status defaults cap human sections; `--all` removes every cap and includes terminal
groups in JSON too. Default status/ready JSON already includes uncapped open groups.
BriefCommand builds an allowlisted packet for text/JSON, not a serialized Item.
Verifier packets omit last receipt, Progress and unknown metadata; they retain
approved narrative, decisions and artifact references. Blinding is field-based,
not semantic censorship. Explorer output targets are repository-relative.

Plan/design commands replace one section. Optional phase/start flags validate
before one atomic mutation, followed by board refresh; without flags they update
content only. Phase alone never infers status. Set remains available for explicit
corrections. No new approval, phase-order or Plan/Design gates are introduced.

## Distribution and runtime

InstallCommand copies root documents plus `.agents/`, `docs/` and `cli/`, excluding
source `.workflow/`, vendor/tools and caches. Default is add-only; force refreshes
ordinary kit files but never root AGENTS/README/LICENSE, host config/gitignore or
existing workflow documents/history. ProjectInitializer creates missing templates;
only a safe derived board may regenerate. Manual host-rule/config merges remain
reviewed human work, not managed updates, automatic merges or deletions.

Destination resolution follows existing filesystem components before later parent
segments and retains missing suffixes, rejecting source-equal/ancestor/descendant
targets. Copy/initialization holds or rejects links/type collisions. This differs
deliberately from lexical ownership comparison; it is not a guarantee against
concurrent hostile filesystem replacement.

Runtime uses PHP >=8.4.1, Composer 2, iconv and locked Symfony packages plus mbstring
polyfill via `composer install --no-dev`. Full development install brings PHPUnit
and its native-extension requirements; `docs/install.md` lists them. Composer
platform checks are authoritative. Every installed role explicitly reads host root
AGENTS and surfaces absent/incomplete/conflicting manual merges rather than assuming
host authority was overwritten.

## Checks and limits

Pure edge-case tests cover codec, graph, lifecycle, exact scope and lexical paths;
isolated command/store/filesystem tests cover refusal-before-write, integration,
history preservation and real links. Protocol examples execute only in isolated
roots; installed prompt/reference checks are static, not real LLM startup tests.
Full-suite and read-only validation results belong to the relevant receipts.

No database, daemon, managed updater, recovery journal, content-freshness metadata
or automatic history migration. See [historical design notes](../docs/design-notes.md)
for earlier alternatives, not current operating instructions.
