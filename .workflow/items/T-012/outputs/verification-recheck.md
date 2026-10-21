# T-012 repaired target — independent full re-verification

**Result: pass for all six acceptance criteria.** This is a fresh full verification of the repaired eight-file implementation/test target, not merely a targeted recheck of the earlier failure. The previous malformed-affected-child cases now refuse before changing bytes or creating records; valid repairs remain possible despite unrelated defects.

The earlier failed verification remains valid evidence about its different frozen target. Neither `verification.md` nor `failed-target.patch` was changed or applied.

## Authority, freeze and environment

- First command: `cli/bin/workflow brief T-012 --for verifier`. Its producer claim was omitted. No worker receipt/conclusion was inspected. Expectations were derived afresh from all six criteria and the candidate-plus-affected-graph Design, including absence of new process gates. Loaded `verify-outcome`; root project instructions were already explicitly read in this verifier's session.
- Verification interval: 2026-10-21 05:24:51–05:28:07 UTC for initial/final pre-report hash checks; the same manifest was checked again after writing this report. All eight target hashes and both preserved evidence hashes matched before/after execution.
- Environment E1 applies to **every criterion and check** below: Linux, repository `/var/home/adam/Projects/general-agentic-workflow`, PHP CLI 8.5.10 NTS, PHPUnit 13.4.3, existing project dependencies/autoloader. All executed PHP probes and tests used `TMPDIR=/tmp/opencode`. No prerequisites were installed, dependencies updated, or implementation/records repaired.
- Other active work was restricted by the assignment to disjoint ScopeOutput/Review/Approve paths. Those changes are not part of this verification. Current T-012 source/test files were read directly and frozen by the manifest below.
- Only this new report was written in the repository by this re-verification. All persistence fixtures were fresh temporary projects, left intact. Real project records, old failed fixtures and old verification evidence were preserved.

## Per-criterion results

All entries ran in E1. Limits below qualify the evidence; no acceptance criterion was left unrun.

| AC | Expected | Methods | Observed | Result and limits |
| --- | --- | --- | --- | --- |
| 1 | Create/save/mutate reject candidate ID-kind identity errors, epic parents, wrong-kind task parents, and missing/wrong-kind subtask parents. | Full source inspection; all four assigned test files; fresh independent 16-case × 3-route real persistence matrix. Extra mutate-rename and save-as-create probes. | All 48 invalid matrix writes refused atomically, including path/newline IDs and all prohibited parent combinations. Mutate could not rename even to another valid-prefix ID. Save-as-create rejected a parentless subtask and accepted one with a task parent. | **pass**. PHP's typed enum/domain model defines possible kinds; this is not a claim to validate arbitrary non-Item objects. |
| 2 | Reject missing references, self-parent/dependency edges, and multi-item dependency cycles before persistence. | Matrix and command writes; 18 additional store cases with cycles passing through malformed metadata; independent iterative-reachability oracle over every four-node directed graph. | Missing/self references and three-node cycles refused across create/save/mutate and commands. Directory mismatch, invalid status/phase, missing declared ID and declared-parent-ID collisions did not hide cycle edges. All 327,680 graph-oracle comparisons agreed. | **pass**. Exhaustive graph enumeration is bounded to four nodes; actual persistence includes three-node paths. Truly unparseable structure cannot supply graph edges; see limits. |
| 3 | A changed parent kind must not invalidate existing children; creating a missing parent must check reverse relationships. | Both kind-change directions, save/mutate, six child variants: 24 refusals; create/save of both epic/task missing parents, same variants: 24 refusals; six CLI affected-parent cases; seven exact earlier repro cases. | All affected wrong-kind relationships were refused with unchanged original bytes and no new record/directory. The old T-010/T-020-declares-T-021 failure and invalid-status variations now reject. CLI `new` returns 2, not success, for the old malformed-child case. | **pass**. Tested structurally parseable legacy records, including broken nonstructural metadata and declared identity. Unreadable/unparseable records are not asserted to be safely interpretable. |
| 4 | Rejected store/command writes preserve original item bytes and do not leave new records. | Exact recursive snapshots of `.workflow` file bytes and directory paths, excluding lock bookkeeping; board included for initialized command fixtures. Snapshot all matrix/affected/cycle refusals and 15 ordinary invalid commands; check lock and rename refusals. | Every expected refusal preserved the captured bytes/paths. No new item directories or temporary item files remained. Multi-assignment `set` did not leak title/priority edits. Board bytes remained unchanged on ordinary and malformed-affected-child command refusal. | **pass**. These are validation/lock refusal checks, not disk-full, crash, partial-write or rename-failure injection. |
| 5 | Valid candidate edits and structural repairs succeed despite unrelated legacy defects; no automatic legacy repair. | 24 positive affected-parent repairs, both target kinds/save/mutate/six child variants; separate unrelated malformed-record, reachable-cycle and bad-parent fixtures; create/save/mutate and own parent/cycle repairs. | Repairs that corrected an affected child's relationship succeeded without rewriting its malformed metadata. Unrelated invalid subtask, missing reference, cycle, invalid status, directory mismatch and malformed YAML did not gate valid writes. Own parent/cycle repairs succeeded; unrelated bytes stayed identical. Unchanged parent-kind edits ignored an existing unrelated child defect. | **pass**. A still-invalid candidate is not a valid repair merely because it improves one field. No automatic repair or broad validation gate was observed. |
| 6 | Full diagnostics retain legacy defects, while standalone reason remains a warning and writes gain no approval/Plan/Design/phase-order gates. | Full ItemValidator tests; independent raw eight-record diagnostic fixture; application `validate`; store/application writes lacking approval/reason/Plan/Design. | All ten independently expected structural diagnostic categories were present without rewriting fixtures. Malformed status, directory identity and YAML remained reported. Standalone `new --phase verify` succeeded; warning-only `validate` exited 0. Unapproved in-progress writes succeeded, with later validation exiting 1 for approval and warning about missing Plan/Design. | **pass**. Full validation reports a malformed metadata parse error rather than necessarily every downstream error for that record. Lifecycle completion rules are outside T-012. |

## Executed test and inspection evidence

### Entire assigned regression surface

```sh
env TMPDIR=/tmp/opencode cli/vendor/bin/phpunit -c cli/phpunit.xml \
  --do-not-cache-result --display-phpunit-deprecations \
  --filter 'Workflow\\Tests\\(Validate\\(ItemValidatorTest|StructureValidatorTest)|Store\\StructureWriteTest|Console\\StructureCommandTest)'
```

Exit **0**: **151 tests, 591 assertions**, no failures. One PHPUnit runner deprecation was emitted for the specifically requested `--do-not-cache-result` option. It was not suppressed or repaired. This run re-executed the complete four-file surface, not only the newly added regressions.

All eight PHP targets passed `php -l` with exit 0. `git diff --check -- <eight target paths>` exited 0; untracked files were read/linted/tested directly because that command alone does not check them.

### Fresh independent persistence suite

Fixture root: **`/tmp/opencode/t012-full-recheck-3aa7c9cfa0`**. PHP probes used the actual project autoloader, `ItemStore`, `ItemValidator` and Symfony application, with explicit checks rather than PHP's optionally disabled `assert()`.

| Group | Checks passed |
| --- | ---: |
| Invalid candidate matrix, 16 cases × create/save/mutate | 48/48 |
| Affected kind changes, both directions × save/mutate × six child variants | 24/24 |
| Affected missing-parent creation, both kinds × create/save × six variants | 24/24 |
| Positive repairs that correct affected relationships, without metadata repair | 24/24 |
| Three-node cycles through normal/malformed metadata, all three store routes | 18/18 |
| Unrelated legacy repair/no-gate controls | 4/4 |
| Full diagnostic/error-versus-warning controls | 8/8 |
| Actual command project setup | 4/4 |
| Invalid commands plus whole-project byte/directory snapshots | 15/15 |
| Command no-gate/warning controls | 4/4 |
| CLI missing-parent creation with normal/malformed affected child | 6/6 |
| Held-lock refusals and graph rereading after callback | 4/4 |
| Mutate identity and save-as-create controls | 3/3 |
| **Total** | **186/186** |

The six child variants were normal, directory/front-matter mismatch, invalid status, invalid phase, missing declared ID and declared ID equal to the parent ID. Directory IDs were deliberately distinct from declared IDs where appropriate.

The 16 candidate cases were wrong ID-kind, path ID, trailing-newline ID, epic parent, task→task/subtask/missing/self parent, subtask→null/epic/subtask/missing/self parent, missing dependency, self-dependency and three-node cycle. Invalid edits also changed title so any partial persistence would be visible. Save/mutate cases began with an existing valid candidate; create cases checked directory absence after refusal.

For lock/current-graph checks, create/save/mutate each refused while the existing write lock was held; the mutate callback did not run. A separate mutate callback deliberately wrote a raw incoming-dependency fixture before final validation; the resulting cycle was detected and candidate bytes remained unchanged. That deliberate fixture setup is not asserted to roll back with the candidate.

### Earlier failure reproduced independently against the new target

Fresh exact fixture root: **`/tmp/opencode/t012-exact-recheck-7025569885`**. These were reconstructed directly, not repaired copies of the earlier failing fixtures. **7/7 cases passed**:

| Exact scenario | Current observed result |
| --- | --- |
| Raw T-010 has kind epic; T-020 declares T-021, kind task, parent T-010; save T-010 as task | Refused: `T-020: task parent must be a epic, got task.` All original item bytes unchanged. |
| Same, via mutate | Same refusal and byte preservation. |
| Same relationship with a correctly declared child but invalid status, save and mutate | Both refused with the same parent-kind diagnostic; bytes unchanged. |
| S-020 declares S-021, kind subtask, missing parent E-010; create E-010 epic | Refused: `S-020: subtask parent must be a task, got epic.` No E-010 record/directory. |
| Same, via save-as-create | Same refusal and no new record/directory. |
| Empty initialized command project, S-020 declares S-021 with parent E-001; CLI `new --kind epic` | Exit **2**, `S-020: subtask parent must be a task, got epic.` Item/board bytes unchanged; no E-001 record/directory. |

Unlike the old target, structural errors now name the directory identity, so the diagnostic is T-020/S-020 rather than the defective declared ID.

### Independent graph and full-diagnostic checks

- **Graph oracle rerun:** enumerate all 65,536 adjacency matrices on four task IDs, including self-edges. Iterative reachability independently determines whether each candidate can reach itself and whether any node participates in a cycle. Compare four candidate validations and one full validation per matrix: **327,680 comparisons, zero disagreement**. Parentless task warnings do not count as cycle errors. The oracle is separate from the production DFS algorithm.
- **Raw diagnostic fixture:** `/tmp/opencode/t012-diagnostics-recheck-9a7b1e3e67`; all ten expected categories appeared: ID-kind mismatch, epic parent, self-parent, self-dependency, missing dependency, missing parent, mandatory subtask parent, wrong-kind subtask parent, wrong-kind task parent and multi-item cycle. Every original item byte remained unchanged by full validation.

## Design assessment

The implementation follows the approved seam rather than introducing workflow gates. In `ItemStore::persist()` lines 191–216, the graph is projected from each record's parseable `kind`, `parent` and `depends_on`, keyed by directory ID. Broken declared identity or status/phase metadata no longer erases affected structural edges. This projection is never persisted as a repair. The proposed candidate remains subject to its actual identity and parent/dependency checks.

Create/save/mutate still reach validation under the existing lock. Candidate validation checks reverse relationships on parent creation/kind change, rejects candidate-involving dependency cycles, and does not reject unrelated legacy defects. Rendering, timestamp updates and the atomic write occur only after structural refusal has been ruled out. Full ItemValidator diagnostics remain a separate path and retain their broader approval/plan/design/legacy warnings and errors.

The positive and negative independent cases above test both sides of the trade-off: do not drop affected edges, but also do not make malformed unrelated metadata a full-project write gate.

## Frozen SHA-256 manifest

These values were identical before and after verification, including a post-report comparison:

```text
3a0023fd98cb250f94f0edf08d1f5c041d141c3b8411bd8429bb25e541ea3e37  cli/src/Validate/StructureValidator.php
5de0f88352aad82c8bad82f73b5108ffbd3831ecb1af100706038f770cde1feb  cli/src/Validate/ItemValidator.php
6180d29987bd26bfc7b073ed78c87aea81b5178c55d7dae6237d2421bba123ae  cli/src/Store/ItemStore.php
60cc90d08b6ad22eef595e17a219e442c1bf205f555477b50f754429934dc2a4  cli/src/Console/Command/NewCommand.php
b82e554687ea34497f2103cfa30df029886861288539048c7dc1de6c10c5888b  cli/tests/Validate/ItemValidatorTest.php
959d7ab520fbf833c8da2ff3a518d62793c787fec86bc78fb10085982ec05fdc  cli/tests/Validate/StructureValidatorTest.php
2fb0abb4ccc33e800c62de2dc898d3739119462092496f45f475bc55a9e51e21  cli/tests/Store/StructureWriteTest.php
de8e3cfbebf0a10e65eb86be616330cf9a27f0ab42c90359b9eaac17b8fe7d08  cli/tests/Console/StructureCommandTest.php
2ab113994dc72530c8c88b39b3a1918ca751a0d31099c8f610ca7c53a2399b14  .workflow/items/T-012/outputs/verification.md
2f4c54623a617dbfe95645615db9d3757fc112e60b8c4e555c97142bf3ace4c3  .workflow/items/T-012/outputs/failed-target.patch
```

## Limits and handoff

- This is full verification of all **T-012** acceptance criteria and its frozen eight-file target, not certification of other items or the whole project. No project-wide suite was run while disjoint work was active.
- No alternate runtime/OS, true simultaneous multi-process writers, unreadable-file permissions, disk-full, crash, fsync or rename-failure injection was run. Existing lock contention/refusal and post-callback graph freshness were executed.
- Completely unparseable YAML, unreadable records and invalid structural kind cannot reliably provide edges; they remain full-validate diagnostics. The acceptance evidence covers malformed **nonstructural metadata/declared identity with parseable structure**, plus unrelated malformed-YAML non-gating. It does not claim interpretation or automatic repair of unknowable relationships.
- Local temporary fixtures remain as evidence, but their paths are not portable dependencies. No new failure was observed; the preserved earlier report/patch still identify the old failed target.
- No item state, receipt or closure was written. The orchestrator may record this verifier result for the manifest above and proceed with T-012 closure under the existing workflow.
