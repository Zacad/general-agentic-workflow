# T-014 independent full verification

**Result: PASS — all eight acceptance criteria.** This is a full verification of the frozen target below, not a targeted recheck. No defect was observed, and no source, item record, completed history or user document was repaired or rewritten.

## Authority, environment and independence

- First command: `cli/bin/workflow brief T-014 --for verifier`. Loaded `verify-outcome` and root `AGENTS.md`; derived expectations from the eight criteria, item Design, parent lifecycle contract and the user's explicit done-history decision. No producer conclusion, default/worker brief or earlier verification report was read.
- Project: `/var/home/adam/Projects/general-agentic-workflow`; Git HEAD `3ec52c68cd683cee8c7a5ca4efd74dbdb769ec3c`. The worktree already contained uncommitted work; the hashes below, not HEAD alone, identify the verified implementation.
- Linux `7.2.5-200.fc44.x86_64`, glibc 2.43; PHP CLI **8.5.10**, executable `/home/linuxbrew/.linuxbrew/bin/php`; installed PHPUnit **13.4.3** and existing Composer autoloader/dependencies. No tools or packages installed.
- Test/probe processes used `TMPDIR=/tmp/opencode`; PHPUnit used the requested `--do-not-cache-result`. All behavioral writes were confined to disposable fixture projects under `/tmp/opencode`, never this project's records. Only this durable report was added to the repository.
- Frozen files were SHA-256 checked before inspection/execution and again after all behavioral checks; all nine matched. Final preservation check: **2026-10-21T05:54:54Z**.

## Executed checks

From the project root:

```sh
env TMPDIR=/tmp/opencode cli/vendor/bin/phpunit -c cli/phpunit.xml --do-not-cache-result --filter 'ItemLifecycleTest|LifecycleWriteTest|LifecycleCommandTest|CommandTest|ItemValidatorTest|StructureValidatorTest|StructureWriteTest|StructureCommandTest'
env TMPDIR=/tmp/opencode cli/vendor/bin/phpunit -c cli/phpunit.xml --do-not-cache-result --display-phpunit-deprecations
env TMPDIR=/tmp/opencode php /tmp/opencode/t014-independent.php
cli/bin/workflow validate --json
```

| Check | Observed result |
| --- | --- |
| Lifecycle/structure/validator and command regression selection | Exit 0; **381 tests, 2,111 assertions** |
| Complete installed test suite | Exit 0; **514 tests, 3,232 assertions** |
| Independently authored store/CLI probes, final run | Exit 0; **255 cases, 989 explicit assertions**, no failures |
| PHP syntax checks on the five frozen implementation files | All five passed |
| Actual project validation | Exit 0; `ok: true`, no errors or warnings |

Both PHPUnit runs reported one runner deprecation: `--do-not-cache-result` will be removed in PHPUnit 14 in favor of `--do-not-record-test-run-history`. No test failures were hidden by that warning. The initial independent run also passed (245 cases); the final run includes ten additional receipt-overwrite/diagnostic/history probes and is the counted evidence below.

The independent harness created fresh fixture records through `ItemStore`, invoked commands through the real Symfony application, and additionally invoked **14 real CLI subprocess cases** through `cli/bin/workflow`. It did not call supplied test methods. Negative checks required both the expected refusal and exact preservation of the fixture workflow tree, including board bytes, item bytes and directory membership; only lock files were excluded. Raw record writes were used solely to establish explicitly labeled legacy-invalid fixtures, not as a claimed supported mutation route. Positive controls checked legitimate completion and permitted nonmaterial updates, preventing a blanket-rejection implementation from passing.

Independent harness SHA-256: `84298211164c21b5b8424006c5b434ece5d9772a3af5a441bd49d5f858cd8c54`. The temporary harness and detailed case results remain available in this environment at `/tmp/opencode/t014-independent.php` and `/tmp/opencode/t014-independent-092e41a614/results.json`; they are diagnostic scratch, not additional durable deliverables.

## Per-criterion findings

All rows use the environment and frozen target above. Counts below are the independent cases, separate from the supplied PHPUnit tests.

### AC1 — tracked open-target changes invalidate verification: PASS

**Expected:** scope, Plan, Design, Decisions, ownership and artifact-reference changes clear `verified_at`, without changing the existing scope-approval contract.

**Observed/method — 33 cases:** independently verified open fixtures, then changed parent, title, outcome, scope-in/out, acceptance, dependencies, ownership, artifact references, Plan, Design, Decisions and a duplicate Design after Progress, using both ordinary `save` and `mutate`. Each persisted edit cleared proof; an attempted normal close then failed without changing bytes. CLI `set`, `plan`, `design` and `decide` paths were also exercised. Approval metadata stayed byte-value equivalent. Operational target changes retained current approval; genuine scope changes made it stale. Reordering scope-in values preserved the existing normalized approval hash but still invalidated verification of the directly compared target. The validator correctly diagnosed an old successful receipt whose target no longer had current verification.

**Limit:** artifact *references* were tested; same-path contents are deliberately outside automatic detection (AC8). Invalid kind/ID combinations are additionally covered by the supplied lifecycle/structural regression selection, not claimed as independently accepted store edits.

### AC2 — new producer or non-done verifier receipts invalidate verification: PASS

**Expected:** worker/explorer receipts and failed, blocked or needs-decision verifier receipts clear previous success on open items, even when a failure follows an earlier successful verification.

**Observed/method — 38 cases:** independently crossed all four supported roles with all four receipt statuses except successful verifier, through both `recordReceipt` and CLI `result`. Every non-successful-verifier receipt cleared proof, left workflow status unchanged and prevented normal close. Store receipts reused the exact timestamp of earlier success. Ordinary `save`/`mutate` overwrites removing the receipt, replacing it with worker success or verifier failure, or copying a later successful-verifier-shaped receipt likewise could not retain or manufacture proof.

**Limit:** role independence is the workflow's explicit verifier-role/receipt contract, not authentication of a person's identity. No identity system is introduced or claimed.

### AC3 — explicit verifier success establishes resulting proof; routine changes retain it: PASS

**Expected:** a successful independent verifier receipt verifies its resulting artifact-reference target; phase/status/priority/progress changes alone do not invalidate it.

**Observed/method — 4 multi-step cases:** CLI verifier success added two new references, deduplicated an existing reference and set matching `verified_at`/receipt timestamps. Subsequent phase, priority, progress, reviewer correction and open/non-done status changes retained proof; ordinary closure then succeeded. Explicit identical same-second successful receipts, including one adding a verification artifact, established or retained current proof. Both `save` and `mutate` operational-only updates retained it. Complementary ordinary-write forgery cases under AC2/AC4 were refused or stripped of proof.

**Limit:** receipt values and final stored target are the trust boundary; the CLI cannot determine whether the human/agent actually performed the independent review.

### AC4 — all completion routes share the current-proof requirement: PASS

**Expected:** `close`, `set status=done`, create, save (new/existing) and mutate cannot complete an unverified or stale target.

**Observed/method — 52 cases:** a route/proof matrix exercised create, save-new, save-existing, mutate, CLI close and CLI set against absent proof, timestamps alone, verifier-shaped/mismatching metadata, target edits after success, failed verification after success and new worker work. All invalid completion attempts failed before record persistence. Store create/save-new could not mint verification by supplying successful-looking metadata. Save/mutate/set/close each successfully completed a currently verified target.

Real CLI subprocess probes tested both assignment orders for material edits plus `status=done`, repeated status assignments ending in done, and ownership edits before/after phase/status assignments. No ordering preserved stale proof. Store receipt-overwrite-plus-completion was refused. Detached previously verified objects could not overwrite a newer target, worker/explorer result or failed-verifier result and then complete. A raw legacy timestamp mismatching its successful receipt was diagnosed, refused completion, and was cleared by an ordinary non-completing mutation.

**Limit:** direct external replacement of `item.md` bypasses the supported store entirely; no new snapshots or hashes were authorized to detect arbitrary manual tampering. Legacy matching metadata is assessed using the existing receipt contract.

### AC5 — force is an honest exception, not verification or a structural bypass: PASS

**Expected:** deliberate `close --force` may close without current proof, durably records the exception, fabricates no verifier success, and cannot override structure/history protection.

**Observed/method — 10 cases:** real CLI forced closure with absent, worker-only, failed-verifier or target-invalidated proof returned `verified: false`, stored `verified_at: null`, preserved the existing last receipt and recorded `Verification exception: explicit --force`. Missing-parent, wrong-parent-kind, self-dependency and cycle fixtures still failed atomically. Force on a genuinely verified target retained proof without inventing an exception. A material Plan injected through an open closure reason invalidated old proof: normal close failed; explicit force closed only as an unverified recorded exception. Done-history force refusal is independently covered in AC6.

**Limit:** force deliberately permits an open unverified target to become done; it is not a claim of successful verification or a bypass of the user's completed-history rule.

### AC6 — material done-item changes require an explicitly new follow-up: PASS

**Expected:** material target edits, reopening, new producer work and unsuccessful verifier receipts on done items fail with explicit follow-up instructions.

**Observed/method — 108 cases:** both store save/mutate refused the tracked field/section/reference changes described in AC1. Every supported non-done destination was refused through store/CLI; forced cancellation also failed. The full non-successful-verifier role/status matrix was rejected via explicit store receipts, CLI receipts, save-overwrite and mutate-overwrite paths. Real CLI order/repeated-status permutations could not disguise material edits by temporarily assigning an open status or ending at done again. Receipt erasure, copied new verifier metadata, new successful-verifier artifact targets, material Design injected through `log`, and material Design in a forced closure reason all failed. Refusals instructed creation of a new follow-up item and preserved the fixture tree exactly. Explicit successful re-verification of the **same** completed target remained permitted; it did not reopen or change target references.

**Limit:** the policy protects tracked material target fields, not a blanket immutable record. Permitted nonmaterial corrections are checked separately in AC7.

### AC7 — refusal is byte-preserving; legacy history and nonmaterial corrections survive: PASS

**Expected:** rejected changes create no successor or other item/board change; unrelated legacy done records remain unchanged; notes and priority corrections are allowed.

**Observed/method — 4 multi-step cases plus all negative-case tree comparisons:** raw legacy done history without successful verification remained byte-identical while another item was planned, verified, closed and the project validated. Both verified and unverified done fixtures accepted priority, phase and nonmaterial progress corrections while retaining done status and unchanged proof/receipt values. A refused material edit created no successor; only an explicit `new` command created the requested draft, unapproved follow-up with its dependency, leaving its completed predecessor unchanged. Across the real repository, all **14 preexisting done records** and **23 Markdown user/codebase documents outside `.workflow` and `vendor`** retained their baseline hashes. Existing dirty files, including `docs/handoff-compare.md`, were not overwritten.

**Limit:** rejected-operation equality excludes synchronization lock files; an already requested permitted note/priority update necessarily changes its own item bytes. No claim of blanket done-file immutability is made.

### AC8 — phase does not imply status; same-path external edits remain convention-based: PASS

**Expected:** phase never infers workflow status; verification freshness tracks frozen receipt/path references, not external file contents.

**Observed/method — 6 multi-step cases:** each of draft, todo, in-progress, cancelled and done was held constant while CLI phase was changed through all five phases. Proof remained current, and no phase implied starting, completing or reopening. Replacing a referenced file's contents at the identical path did **not** clear proof or prevent normal closure; this is the explicitly requested limitation, not an automatic detection claim. Inspected `ItemLifecycle` lines 10–14 document the freeze/new-independent-verification convention. Persisted fixture metadata contained only the existing keys; approval scope-hash semantics remained unchanged. The inspected implementation adds no freshness hash, snapshot, target version or content scanner.

**Limit:** operators must freeze named paths, report external changes and independently reverify before closure. This implementation does not automatically detect same-path external edits, and this report does not certify arbitrary external artifact content.

## Frozen target and preservation evidence

SHA-256 values were identical at entry and after execution:

| Frozen path | SHA-256 |
| --- | --- |
| `cli/src/Domain/ItemLifecycle.php` | `a1474d75d58e6bfea364abd403944d26acdf70509310b0f8215f6131ffdd0915` |
| `cli/src/Store/ItemStore.php` | `962bbfb4541c48194b7703d04abe50c3f48d633f741652146f742fb02280c959` |
| `cli/src/Validate/ItemValidator.php` | `19382655cc6c4a51b9ad76d4a9257f358d31f1c2ef55ed140652f09d11bd2652` |
| `cli/src/Console/Command/ResultCommand.php` | `558e9d1f9146ec39b58ace4120ffdd3feb2480226dd640ce0773563aa5a69a10` |
| `cli/src/Console/Command/CloseCommand.php` | `15588f576bf857ec75ba2d8dfb2fe125f61ee374ed3a5df8af295ce2d8ddbc8e` |
| `cli/tests/Domain/ItemLifecycleTest.php` | `aa827bbb504bb5ff3a2505f334a9630da4e0eae8ba57df54ec346f8643b90cd7` |
| `cli/tests/Store/LifecycleWriteTest.php` | `e0bacd02c9faf0b6218d109b4691c63a0eb320c9d2e5c663dbfc1d58bebde330` |
| `cli/tests/Console/LifecycleCommandTest.php` | `ddeeccf9def4eb0bbc5a11297bb66f755f66d7fd4d87d37928ad59e4481c300b` |
| `cli/tests/Console/CommandTest.php` | `0e6cf5a00b20352f1f3296722000030071e431569f05a67a22c059d0ce1bef28` |

The actual done-record guard covered E-001, T-001 through T-013. Its canonical JSON path-to-hash mapping SHA-256 was `f66d33ced0d96d28ad6381a2fa51fbd9350009d5854d92b82a021c42e0c2eefe`; the 23-document mapping SHA-256 was `2d045a23f1094c2a71d2ec1136bcec407672526ca815c978230531f2abb4daf9`. Final comparison found zero changed guarded files. Temporary detailed guard: `/tmp/opencode/t014-repository-guard.json`.

## Remaining limits and disposition

- Executed on the installed PHP 8.5.10/Linux environment only; no PHP 8.4.1 minimum-runtime, other OS, remote filesystem or adversarial concurrent external editor run is claimed.
- The supplied suite and independent executable checks support all eight criteria for these exact hashes. They do not prove every possible input or future revision.
- No failed target was found to repair. The verified target remains in place unchanged; this report is the only durable verifier output. The orchestrator may record this verifier receipt and close T-014 under the existing two-command protocol.
