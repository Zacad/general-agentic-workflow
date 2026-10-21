# Protocol slimming discovery

## Decision and source status

Keep `AGENTS.md` as the canonical operating protocol. Shorten roles to responsibilities, inputs and receipts; skills teach methods/command usage, not another full operating loop. This source-checked synthesis adopts the user's staged approval, epic-hosted independent discovery, explicit small-work path and historical done-item policy. No further policy question is open.

## Checked inconsistencies

- `AGENTS.md:89-94`, `.agents/roles/orchestrator.md:26-28`, `.agents/skills/decompose-work/SKILL.md:35-41` and `README.md:122-128` mandate a separate discovery task and say later exploration cannot change decomposition. Both conflict with accepted flexibility: independent discovery may be booked on the epic; later evidence can change affected child scope with reapproval only there.
- The operating loop/readiness/approval rules repeat across AGENTS, orchestrator, workflow-cli and README. `AGENTS.md:96-98` makes triviality an implicit verification exemption; replace it with an explicit deliberate exception, not weakened default verification.
- `.workflow/Architecture.md:183-184` says closure merely warns, unlike the actual refusal in `cli/src/Console/Command/CloseCommand.php:49-56`. Architecture also lists nonexistent Receipt and Store/FrontMatter paths at lines 43 and 103. `README.md:86-88` gives a task another task as parent.
- `docs/install.md:80-82` recommends deleting smoke-test history; use an isolated temporary smoke project or explicit cancellation instead. `docs/opencode.example.json` exists and points each agent at its role file (lines 8,19,27,35); no harness schema change is required for this work.
- `PlanCommand.php:20-25,53-59` and `DesignCommand.php:20-25,53-59` only replace content. Every phase/status change needs a separate set call, and neither refreshes the board. Their similar shape supports a very small optional-flag correction rather than a command framework.

## Canonical-source comparison

Moving the protocol into a new `docs/workflow-protocol.md` would avoid manually merging a long root file into hosts, but still needs root and role discovery pointers and adds another default source to load. Existing AGENTS is usable and already the agreed canonical source. Keep it, reduce duplication, and require every installed role to read the project's root AGENTS explicitly. Installation must tell users to merge the canonical workflow rules into a preserved host AGENTS; agents encountering absent/conflicting rules report that limitation rather than silently replacing host authority. This is the smallest total-context change, not a managed distribution system.

README becomes an introduction and executable quickstart with a link to the protocol. PRD/Architecture retain product requirements, mechanisms, limits and decisions, not repeated instructions. Historical `docs/design-notes.md` remains historical and unchanged. Generic PRD/Architecture templates already ask for project content and need no protocol loop. Existing `docs/handoff-compare.md` is expressly untouched.

## Selected behavior and ownership

One scope map lives in epic Design; child designs reference it instead of copying the decomposition. Approval is staged: epic/discovery first, evidence then exact children. An unchanged epic is not reapproved. Discovery independence means a separate role/run, not a mandatory extra item. Use a discovery child only when its independently acceptable deliverable justifies it.

Small work uses one item, short exact scope approval, one-line Plan and Design, an explicit explanation when no exploration is needed, and worker plus independent verifier normally. A deliberate `close --force` exception remains available. No mandatory phase sequence or new hard plan/design/approval gates.

Completed items remain historical. Material scope/design/target changes, new producer work or unsuccessful verification require an explicit new follow-up item and its scope review. The CLI refuses those edits with instructions; it never reopens or creates a successor automatically. External same-path artifact changes require convention-based freezing and receipts, not fingerprint machinery.

For bookkeeping, add optional `--phase PHASE` and `--start` to existing plan/design commands. Validate all inputs before one atomic content/state mutation; absent flags remain content-only and never rewind phase/status. Explicit phase does not infer status; only `--start` requests in-progress. Refresh derived board afterward. Keep `set` for manual corrections. Defer optional `result --close`: the existing explicit receipt/close pair is clear and avoids a second terminal path while lifecycle rules are being unified.

## Verification and integration

Use one protocol writer across AGENTS, README, install docs, four roles, five workflow-related skills, PRD and Architecture: 14 related files, justified by one coherent contract and one voice. Do not split overlapping prose writers. A focused example smoke test may accompany that deliverable. Bookkeeping is a separate command deliverable depending on lifecycle protection, using new focused tests.

Check installed role loading, stale mandatory-discovery language, canonical-link resolution, exact staged review, deliberate verification exceptions and done-history refusal. Run quickstart/smoke examples only in isolated roots with valid hierarchy, complete verifier/closure flow and repository-relative artifact paths. Compare plain content updates with flagged atomic transitions and invalid-option rollback. Word counts and repetition checks are useful evidence, not new process gates. Source inspection here did not execute those future acceptance tests.
