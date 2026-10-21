# T-015 — independent full verification

**Result: PASS — all six acceptance criteria.** This is a full T-015 verification, not a targeted recheck. The four named targets and the relevant frozen lifecycle/output dependencies had identical SHA-256 hashes before and after testing. This verifier changed no product source or project item record.

## Authority, method and environment

- First command: `cli/bin/workflow brief T-015 --for verifier`. Expectations below were derived from its six criteria and Design before inspecting source. Loaded `verify-outcome`, root `AGENTS.md` and `workflow-cli`; no producer receipt or conclusion was inspected.
- Target: working-tree files, not merely HEAD. Existing user/other-item modifications were preserved. The assignment declared the target and core lifecycle frozen; the hashes below independently confirmed no changes during these checks.
- Environment: 2026-10-21; Linux x86_64, kernel `7.2.5-200.fc44.x86_64`; PHP CLI **8.5.10**, PHPUnit **13.4.3**, Symfony Console/Yaml **8.1.9**, installed project dependencies. Native `mbstring`, `iconv`, XML/DOM and `pcntl` were available. All test commands used `TMPDIR=/tmp/opencode` and `--do-not-cache-result`.
- Independent executable evidence: `/tmp/opencode/t015-independent-full-20261021/T015IndependentTest.php`. It uses separate fixtures and manually specified scope/section expectations, a literal decoder, and a canonical hash oracle; expected packets do not call the implementation's scope projection. Temporary projects remain under that directory. Eight subprocess invocations also exercise the real `cli/bin/workflow` entry point in text/JSON with forced `--ansi`/`--no-ansi`.
- Only durable verifier output: this file. All command writes and deliberate mutation/error fixtures were confined to temporary projects. No approval, result, phase, closure or board command was run against the real project.

## Executed checks

Commands below ran from `/var/home/adam/Projects/general-agentic-workflow/cli`:

```sh
TMPDIR=/tmp/opencode vendor/bin/phpunit --do-not-cache-result tests/Console/ScopeOutputTest.php tests/Domain/ItemScopeTest.php
TMPDIR=/tmp/opencode vendor/bin/phpunit --do-not-cache-result --display-phpunit-deprecations
TMPDIR=/tmp/opencode vendor/bin/phpunit --do-not-cache-result --display-phpunit-deprecations /tmp/opencode/t015-independent-full-20261021/T015IndependentTest.php
```

| Check | Observed result |
| --- | --- |
| Frozen ScopeOutputTest plus existing ItemScopeTest | Exit 0; 26 tests, 304 assertions |
| Full existing regression suite, including lifecycle/store/command checks | Exit 0; 514 tests, 3,232 assertions |
| Final independent suite | Exit 0; 50 tests, 1,019 assertions |

Each invocation reported the same single **PHPUnit runner deprecation**: `--do-not-cache-result` will be removed in PHPUnit 14 in favor of `--do-not-record-test-run-history`. The requested flag was retained. There were no test failures in these final runs.

An initial independent race fixture attempted to change `T-010` from task to subtask. Four cases correctly rejected that *injected later write* with `T-010: id does not match kind subtask.`; this was an invalid verifier scenario, not a T-015 defect. A diagnostic rerun confirmed the exact error. The final fixture keeps the identity-bound kind unchanged and changes every other mutable approved field under a valid write. The original temporary fixtures and exact reproduction instructions are retained in `/tmp/opencode/t015-independent-full-20261021/initial-harness-note.md`. No frozen source was repaired.

## Per-criterion findings

All rows use the environment above.

### AC1 — unambiguous literal text: PASS

**Expected:** distinct list boundaries, empty lists/strings, null and multiline strings remain distinguishable.

**Observed:** 29 independent literal cases round-trip without raw CR, LF or NUL in a rendered field line. Cases include null versus the string `none (null)`, `null`/`true`/`false` strings, `[]` versus `"[]"`, `[""]`, `["a","b"]` versus `["a, b"]`, whitespace, quotes/backslashes, actual newline versus literal backslash-n, CRLF, tabs, NUL, Unicode line/paragraph separators, Unicode and Console tags. Direct projection/rendering also retains empty and whitespace-only list elements and duplicates, with decoration both off and on.

**Method:** independent `testLiteralRoundTripAndSinglePhysicalLine` and `testDirectScopeKeepsEmptyAndWhitespaceListValuesAndOwnsIsContext`, plus command text/JSON cases and frozen regression tests.

**Limits:** persisted typed lists already trim/drop empty elements in `Item::fromArray`; those output-only boundaries were tested directly on Item values, rather than misrepresenting existing persistence normalization as changed behavior.

### AC2 — every approved field stays exact and literal: PASS

**Expected:** kind, parent, title, outcome, both scope lists, acceptance and dependencies retain values, without truncation or Console-tag interpretation.

**Observed:** independent packets and decoded text match every field, including duplicate/unsorted scope and dependency entries, positional acceptance entries, a 700-repeat multibyte/combining-character title with an end sentinel, leading/trailing scalar whitespace, escaped CRLF/newlines, quotes and backslashes. `<error>`, color/options and hyperlink markup with `雪🚆`/`żółć` remains literal. Successful decorated output has no introduced ANSI sequences. Approvals of epic/task/subtask records in both formats and decoration modes retain null parents, empty strings/lists and comma-versus-separate list boundaries; a direct empty-string parent is also represented correctly. Binary-entry-point checks give the same values.

**Method:** independent direct rendering, `testReviewAndApproveTextAndJsonAgreeOnEveryActualScopeValue`, `testApproveEachKindIncludingNullEmptyAndListBoundaries`, `testExecutableEntryPointAlsoPreservesLiteralTextAndJson`, complete-field JSON comparisons and source inspection of raw output calls.

**Limits:** exactness is relative to the existing typed Item model, not arbitrary malformed YAML. Invalid UTF-8 and arbitrary terminal-control payloads were not new requirements or exhaustively tested.

### AC3 — shared projection; owns is context only: PASS

**Expected:** Review and Approve consume one small exact scope projection; owned paths are displayed but do not enter approval identity.

**Observed:** both commands use `ScopeOutput::scope` and `ScopeOutput::write`. The projection has precisely the approved scope fields and no `owns`. Review/Approve text and JSON agree on the original list values rather than their sorted/deduplicated hash representation. Changing only persisted ownership changes the review context while preserving the hash and current approval.

**Method:** complete packet/text comparisons, direct ownership changes and a supported temporary-project ownership mutation. Inspected the 69-line, three-method helper and its two consumers: no schema/serializer framework or stored-list normalization change was introduced in this seam.

**Limits:** verification freshness is intentionally a separate lifecycle policy; this result concerns approval identity, not whether an owns edit retains verifier freshness.

### AC4 — actual locked approval capture: PASS

**Expected:** both formats report the scope and hash of the approval mutation, not values reloaded after another write.

**Observed:** four independent text/JSON × decorated/plain interleavings capture the newly stored approval before any success output, then perform a second supported mutation changing parent, title, outcome, scope-in, scope-out, acceptance, dependencies, owns and status. The second mutation succeeds after lock release, and the latest record has a different hash and stale approval. Nevertheless every reported field, original ownership, `todo` status and reported hash matches the first captured approval and the independent hash oracle; no later value leaks into output.

**Method:** a deliberately unrelated invalid record causes board refresh's existing warning; the stderr hook performs the intervening write before either success-output branch. The hook first loads and checks the actual stored approval. Source confirms `ApproveCommand.php:37-52` computes hash, sets approval and returns the scope packet in one `mutate` callback; `ItemStore.php:166-178,216-234` persists and returns it while holding the lock. Rendering uses that returned array, not a later load. Persistence/lifecycle do not rewrite approved scope fields.

**Limits:** deterministic post-lock interleaving plus source inspection, not a timing-dependent multi-process stress test. Kind is fixed by ID prefix for a supported write; its exact capture is checked across all three valid kinds elsewhere.

### AC5 — complete exact Review JSON decomposition: PASS

**Expected:** the epic and all descendants include exact scope, dependencies, ownership, approval and correct Plan/Design information.

**Observed:** the independent tree returns the epic plus naturally ordered depth-first descendants `T-002`, `S-003`, `T-010`, excluding an unrelated epic and its dependency tasks. Counts are two tasks, one subtask and two open descendants. Every record's full scope, hash, owns, status and phase matches the manual fixture. Approval records/state distinguish current, stale and absent approval, retaining literal tags/newlines in the approver. Epic and child Plan/Design contents, indentation, blank lines and written/missing combinations match expected sections. An empty epic yields empty descendants and zero counts. Review leaves fixture item bytes unchanged and creates no board. Non-epic review exits 2 without writes; rejected approval of a missing dependency also leaves item bytes unchanged.

**Method:** independent `testReviewJsonIsExactReadOnlyCompleteTreeAndCorrectSectionState`, shared-format/real-entry-point comparisons, error/empty-tree cases, byte snapshots and frozen regression tests.

**Limits:** Plan/Design retain the established `Item::section()` outer-whitespace normalization, not raw body bytes. Review is not claimed to be a transactionally locked snapshot across concurrent writers; that requirement applies to approval capture.

### AC6 — existing scope-hash semantics unchanged: PASS

**Expected:** routine context remains outside approval identity; scope-in/out and dependencies remain set-like for hashing, while acceptance order remains material.

**Observed:** existing ItemScope tests pass unchanged. The independent oracle matches reported/stored hashes; reordering/deduplicating both scope lists and dependencies preserves approval, while changing acceptance order makes it stale. Each approved field independently invalidates approval when materially changed. Changes to owns, artifacts, phase, status, priority and body do not change approval identity. `git diff --exit-code -- cli/src/Domain/Item.php cli/tests/Domain/ItemScopeTest.php` exits 0, and both files' hashes remain stable during verification.

**Method:** source inspection of `Item::scopeHash()`/approval methods, existing ItemScope tests, `testHashOracleAndAcceptanceOrderVersusSetOrderAndRoutineContext`, and the full regression suite with the frozen lifecycle implementation.

**Limits:** the regression run is not a separate independent certification of all T-014 acceptance criteria. No hash migration or new normalization was attempted.

## Frozen file hashes

SHA-256; each following repository file was checked before and after tests and remained identical:

```text
893494156b4520e27171399d0cfaba13cc05744ee19d9fbc21bc137f1a039226  cli/src/Support/ScopeOutput.php
d0cc27f1dd4830fc9d944242b02a05ba211dcc313b1fe4bd33ede6d7be89b072  cli/src/Console/Command/ReviewCommand.php
dc7551cdf7098e8a65388ee07465d91c90dc83ccbbe71bd2d870373fea93833e  cli/src/Console/Command/ApproveCommand.php
add59ef33f6f8bb3fadcaea19d989a9ed51b73488cd9efc83e96cb8d2086374a  cli/tests/Console/ScopeOutputTest.php
e85b168fd602bbde0eefbd7b4e22a10e146d4e79e025094d28a75cd6eab00355  cli/src/Domain/Item.php
a1474d75d58e6bfea364abd403944d26acdf70509310b0f8215f6131ffdd0915  cli/src/Domain/ItemLifecycle.php
962bbfb4541c48194b7703d04abe50c3f48d633f741652146f742fb02280c959  cli/src/Store/ItemStore.php
19382655cc6c4a51b9ad76d4a9257f358d31f1c2ef55ed140652f09d11bd2652  cli/src/Validate/ItemValidator.php
3a0023fd98cb250f94f0edf08d1f5c041d141c3b8411bd8429bb25e541ea3e37  cli/src/Validate/StructureValidator.php
1fad3db121953ea05d36b97c9bf3699b3332d2987fdbf4be7ebf42f17785aae0  cli/src/Console/Command/BaseCommand.php
15588f576bf857ec75ba2d8dfb2fe125f61ee374ed3a5df8af295ce2d8ddbc8e  cli/src/Console/Command/CloseCommand.php
558e9d1f9146ec39b58ace4120ffdd3feb2480226dd640ce0773563aa5a69a10  cli/src/Console/Command/ResultCommand.php
ceb87f671b8a881e0721723e8da1ffa21f00cef355619e466960938cf4a4b7ef  cli/src/Domain/FrontMatter.php
d11df148d9238d4e244d879d6df908ce0f1c9b9199e6e7ae717517817baa135a  cli/tests/Domain/ItemScopeTest.php
b7a8685d64aaec407e60e2858c372cf88365e9fcc5444156f817094b6e2b0b65  cli/composer.lock
```

Final independent test harness SHA-256:

```text
0065dd60c972324426c591bfd86e5ef8cf194699b2d0225de848813b12e0a8d3  /tmp/opencode/t015-independent-full-20261021/T015IndependentTest.php
```

## Overall limits and next action

No checks were blocked. Alternate PHP versions/operating systems, malformed-byte inputs and probabilistic concurrency stress were not run. Temporary executable evidence is retained locally, not a permanent repository test addition. Confidence is high for all six T-015 criteria against the named hashes; changes to those targets or relevant dependencies require a new check.

The orchestrator may record this independent verifier receipt for T-015. This verification does not itself approve scope, mutate the item or close it.
