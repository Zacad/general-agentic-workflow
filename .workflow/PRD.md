# General Agentic Workflow — product requirements

## Purpose and users

A developer coordinating LLM agents needs durable, reviewable work records
without filling the coordinator's context with reports. This installable kit
uses Markdown as truth and a small PHP/Symfony CLI for compact projections,
safe mutations and a read-only board. Root `AGENTS.md` is the sole operating
contract; this document specifies product outcomes, not another protocol.

Primary scenarios: frame/approve work, investigate independent questions,
delegate non-overlapping deliverables, verify a frozen target, resume from
records alone, and install into an existing repository without replacing its
authority/history. Small work must remain small; broad work must be decomposable
without repeated approval of unchanged scope.

## Goals and non-goals

- Context economy: bounded default coordination views, evidence in role contexts.
- One durable truth: human-readable records; derived views are rebuildable.
- Low ceremony: scope-only approval, advisory phases, no mandatory discovery item.
- Integrity: valid hierarchy/dependencies, one writer, current verification and
  preserved completed history with explicit exceptions/follow-ups.
- Safe distribution: additive scaffold, reviewed manual merges, reproducible runtime.

No database, daemon, scheduler or long-running server; no authentication,
multi-user synchronization or remote collaboration; no rigid state machine or
enforcement of every edge case. No full-text search, comments, attachments, time
tracking, auto-commit or web UI beyond the generated read-only board. No migration
of records from the earlier system, automatic reopening/successor creation,
managed updater or host-document merger. No content-fingerprint/snapshot
infrastructure or per-attempt project copies.

## Functional requirements

Identifiers retain the original requirement references.

### Records and integrity

- **FR1–FR4:** `.workflow/PRD.md` and `Architecture.md` hold project facts;
  `items/<ID>/item.md` holds typed YAML state plus Markdown narrative; optional
  `outputs/` holds evidence/deliverables. A generated board is not source state.
  Preserve literal/multiline front matter and unknown keys without changing
  the existing typed-list normalization contract.
- **FR2:** Epic has no parent; task has an epic parent or explicit standalone
  reason; subtask has a task parent. Supported writes reject invalid kinds,
  parent relationships and dependency cycles before persistence.
- **FR5–FR7:** Status/phase are separate enums; Plan, Design, Decisions and
  append-only Progress provide narrative. No enforced phase sequence.
- **FR17, FR19:** Receipts update result/artifact references. All supported
  completion routes require current verifier proof; deliberate forced close
  records an unverified exception, never a successful verifier claim. Tracked
  scope, Plan/Design/Decisions, ownership/artifact changes and producer or non-done
  verifier receipts invalidate open-item proof. Material done-item work requires
  an explicit approved follow-up, preserving original history; notes/priority
  corrections are not material work. Same-path content freezes remain conventional.

### Approval and projections

- **FR8–FR11:** Approval records actor/time and scope identity: kind, parent,
  title, outcome, scope, acceptance and dependencies. Other changes do not stale
  it. Draft approval moves to todo; stale approval is reported and excluded
  from readiness. Verification invalidation is separate from scope approval.
- **FR14–FR16:** Default status/ready views are bounded; `status --all` includes
  every section and terminal group. Ready exposes dependency and lexical
  literal-path overlap facts, including future paths. Role-specific briefs
  contain sufficient delegation inputs; verifier packets omit receipts/progress/
  unknown metadata in text and JSON. No semantic censorship of approved narrative.
- **FR23, FR29–FR30:** Read commands support literal JSON. Review projects exact
  epic/descendant scope and Plan/Design; approve prints values/hash from the same
  locked mutation. List boundaries, empty/multiline values and tags stay distinct.
- **FR24–FR26:** Routine coordinator reads use bounded views, not whole records
  or reports. Coordinator mutations remain available. Delegated receipts have
  five fixed fields; evidence detail stays in artifacts.

### Commands and distribution

- **FR12–FR13, FR18–FR21:** Initialize missing templates, allocate IDs, expose
  dependencies, update records, validate diagnostics and render an escaped static
  board. Plan/design are content-only by default; optional phase/start flags
  request one validated atomic content/state mutation. Receipt then close remains
  two calls. Missing Plan/Design yields advisory warnings, not new gates.
- **FR22, FR27:** Install source kit files, not source workflow history or vendor
  dependencies. Default is add-only; root AGENTS/README/LICENSE, workflow records,
  host configuration and gitignore files survive force. Ordinary kit-file force
  refresh is explicit; links/type conflicts are held/rejected. No automatic merges
  or deletions. Roles explicitly load host root rules and surface needed manual merges.
- **FR28:** Runtime requires PHP >=8.4.1, Composer 2, iconv and locked dependencies
  via `composer install --no-dev`; mbstring is polyfilled. Development installation
  has additional native-extension requirements documented in `docs/install.md`.

## Acceptance evidence

1. Isolated init/create/approve/brief/verifier/close examples use valid hierarchy
   and project-relative artifacts, pass validation and preserve history.
2. Codec and locked persistence tests cover literal round trips, invalid graph
   refusal before write, repair around unrelated legacy defects and atomic writes.
3. Lifecycle tests cover invalidation, all done-entry routes, explicit exceptions
   and byte-preserving refusal of material completed-history changes.
4. Packet tests cover role blinding, exact scope values/hashes, exhaustive all
   output and consistent ownership aliases/boundaries without filesystem semantics.
5. Real isolated scaffold tests cover containment, links/type collisions and host
   preservation; runtime/development checks distinguish executed environments.
6. Canonical loading/manual-merge checks and executable public examples agree with
   AGENTS. Word-count comparisons state their measured file set, not a repository-wide
   slimming claim. Static role checks do not prove actual LLM startup/independence.

## Product decisions

Markdown-only storage and scope-only approval replace earlier revision/attempt
ceremony. Four roles separate coordination, evidence, production and verification;
skills teach methods. Independent discovery may live on the epic. Epic Design
owns the scope map; children reference it. Later evidence changes only affected
approved scopes. Historical design notes remain evidence of earlier decisions,
not current operating authority.
