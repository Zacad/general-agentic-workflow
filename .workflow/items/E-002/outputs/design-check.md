# E-002 independent pre-implementation design check

**Conclusion:** Coherent and implementable, with no blocking design/scope defect found. This checks the design, not delivered outcomes; it must not establish E-002's `verified_at` or authorize implementation before child-scope approval.

Authority: verifier brief, exact `workflow review E-002` packet, and the latest completed-history decision. Discovery artifacts were treated as source leads, not implementation proof. Known pending-ID/temporary-JSON wording cleanup is excluded.

## Blocking issues

None identified. No accepted requirement appears missing, and no additional user policy decision is needed for the reviewed design.

## Three bounded checks

1. **Scope and policy — covered.** The epic coverage table maps all nine acceptance criteria to T-009–T-018. T-014 and T-018 explicitly require a newly created, explicitly approved follow-up for material completed-item work; they prohibit automatic reopening/successors. Independent verification remains the default, with deliberate recorded `close --force` exceptions, not fabricated success. T-010/T-018 retain scaffold copying, host-rule preservation and manual merges. No new fingerprint/version infrastructure, mandatory phase sequence, approval gate or updater is introduced. Sources: `.workflow/items/E-002/item.md`, Design/Decisions; T-010, T-014 and T-018 records as rendered by review; the four discovery reports in this directory.

2. **Dependencies and ownership — feasible.** Shared ItemStore edits are ordered T-012 → T-014; shared ItemValidator edits continue through T-016. The epic expressly requires the same writer for that chain. Other implementation paths and new focused tests are disjoint. Existing invalid validator fixtures belong to T-012; existing CommandTest belongs only to T-014. T-018 joins all relevant integration branches transitively and has one prose writer. `ready --all` currently returns no ready items because children are drafts: expected before approval, not an ownership blocker. Recheck readiness when dispatching. Sources: exact child depends/owns records; `cli/src/Read/ProjectView.php:80-129`; `cli/tests/Validate/ItemValidatorTest.php:69-91`.

3. **Implementation specificity and independent checks — sufficient.** Designs identify concrete seams, constrained alternatives, edge cases and refusal-before-write evidence. Pure codec/graph/lifecycle/path tables plus isolated command/store/filesystem checks are stronger than snapshots alone and avoid broad re-research. Literal packet sentinels, unchanged-byte assertions, explicit force cases and real symlinks have independently observable expectations. Runtime-environment limits are explicitly reportable rather than silently waived. Sources: child Plan/Design/acceptance; discovery reports' verification sections.

## Advisory implementation details, not missing requirements

- **T-014 receipt intent:** Include same-second success/failure and success/target-change cases, and direct store creation/save with a supplied verification timestamp. The shared rule must not infer fresh independent success merely from timestamp inequality or non-nullness. Receipt intent is already in the design; no new persisted metadata is needed. Sources: `cli/src/Support/Clock.php:9-11`, `cli/src/Console/Command/ResultCommand.php:71-93`, `cli/src/Store/ItemStore.php:96-125`.
- **T-015 test compatibility:** Exact literal rendering may change ordinary output assertions in existing CommandTest. Preserve compatible simple-value output where possible, or route any necessary assertion edits through its sole T-014 writer before freezing tests; do not let a second writer silently edit that file. This is test integration, not scope expansion. Source: `cli/tests/Console/CommandTest.php:510-532`.

No implementation, item-record edits, test-suite run or outcome verification was performed. The supplied 48-test/173-assertion baseline and deprecated-option warning were not independently rerun. `docs/handoff-compare.md` remains untouched.
