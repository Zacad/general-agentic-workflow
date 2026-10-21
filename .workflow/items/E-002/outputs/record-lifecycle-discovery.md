# Record and lifecycle discovery

## Finding and authority

Use three bounded corrections: lossless YAML framing, structural checks at the locked persistence boundary, and one completion/invalidation rule. E-002's approved brief and the user's completed-item decision govern this synthesis. Completed work remains history: material changes, new producer work or unsuccessful verification require an explicitly created follow-up item. Never reopen or generate successors automatically.

This report source-checks supplied independent exploration; it is not a fresh implementation or test run. The earlier explorer reported a nine-case in-memory codec prototype and existing domain checks of nine tests/21 assertions. Those results were not reproduced here and are not acceptance evidence for a patch.

## Checked source evidence

- `cli/src/Domain/FrontMatter.php:57-63` applies a global replacement to dumped YAML. It can change ordinary scalar text such as `JSON: {}` into `JSON: []`. Symfony already provides `DUMP_EMPTY_ARRAY_AS_SEQUENCE` (`cli/vendor/symfony/yaml/Yaml.php:35`, `Inline.php:240`), eliminating that replacement.
- `FrontMatter.php:32` excludes the YAML boundary newline. Literal-block chomping therefore loses information; rendering at line 63 also assumes the dumper ended with a separator newline. The previous explorer observed a final multiline value joined as `second---`. Preserve the parse boundary newline, ensure a separate closing delimiter, and never strip scalar trailing newlines from YAML output. Keep existing Markdown-body newline normalization distinct.
- `cli/src/Domain/Item.php:224-240` deliberately trims/drops empty list entries. Codec tests must distinguish that existing typed-item normalization from serialization corruption; changing the list schema is not authorized.
- `cli/src/Console/Command/NewCommand.php:57-75` checks reference existence, not parent kinds or required subtask parents. `SetCommand.php:68-87` changes parent/dependencies without graph validation. `cli/src/Store/ItemStore.php:96-125,185-190` funnels create/save/mutate into unchecked persistence under a lock.
- `cli/src/Validate/ItemValidator.php:108-209` already implements parent-kind and dependency-cycle algorithms. Its full validation also includes approvals, narrative warnings, artifacts and ownership; invoking all of it before saves would add unintended gates.
- `ResultCommand.php:82-93` only sets verification on verifier success; a later failed verifier leaves the timestamp. `SetCommand.php:69` can bypass `CloseCommand.php:49-65`, which only checks timestamp presence. Scope/design/artifact changes currently leave old verification intact.

## Selected boundaries

1. **codec** owns FrontMatter and its focused tests. Replace regex rewriting with the Symfony flag; test literals, Unicode, empty sequences, nested/unknown data, final multiline values and zero/one/multiple terminal scalar newlines. Reject malformed YAML as before. No migration.
2. **structure** extracts one pure structural validator and shares it with diagnostics and locked store writes. Validate the candidate plus affected parent/dependency graph, including reverse relationships when a parent kind changes. Reject invalid IDs/kinds, parent relationships, missing/self references and cycles before writing. Do not let unrelated legacy defects prevent a valid repair; retain full-project diagnostics. Create/save/mutate all use this boundary, not just commands. Invalid legacy fixtures in `cli/tests/Validate/ItemValidatorTest.php:69-91` must be written explicitly as raw test records rather than through now-safe store APIs.
3. **lifecycle** uses a small shared before/after rule at persistence, with command receipt intent where necessary. For open items, clear verification after scope, Plan/Design/Decisions, owned/artifact-reference changes, producer receipts or unsuccessful verifier receipts. A successful verifier receipt establishes verification for its resulting target. Do not infer status from phase. All routes entering done use the same check; only explicit `close --force` bypasses missing verification, records an exception, and never fabricates verification or bypasses structural/history protection.

For done items, refuse material scope/design/target changes, reopening, new producer receipts and unsuccessful verifier receipts before persistence, with an instruction to create an explicit follow-up item. Non-material notes/priority corrections need not become a general immutability gate. Preserve current done records; no batch rewriting. Scope approval remains governed by existing `scopeHash`, independent of verification invalidation.

## Trade-offs, checks and limits

Central rules avoid command-specific bypasses; full workflow validation on each save would wrongly gate ordinary work. Clearing existing `verified_at` is sufficient for tracked edits; new fingerprints, versions and snapshots are rejected. Same-path external artifact changes remain a freeze/receipt convention, not a detected guarantee.

Use fast pure codec/graph/lifecycle tests plus temporary-project command/store tests for atomic refusal, invalid multi-assignment rollback, force exceptions and done-history byte preservation. Unit tests alone cannot establish caller integration; CLI-only tests obscure the edge-case matrix. Structure and lifecycle share ItemStore/ItemValidator sequentially with the same writer; lifecycle alone may update existing `cli/tests/Console/CommandTest.php`. Independent verification remains required normally, with deliberate explicit exceptions.
