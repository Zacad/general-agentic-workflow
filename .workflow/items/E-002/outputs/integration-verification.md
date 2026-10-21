# E-002 — independent full final integration verification

**Verdict: pass. All nine epic acceptance criteria are met for the frozen target in the available environment.** No integration defect was found. This is an epic-outcome check, not a child-receipt audit. PHP 8.4.1 execution, a genuinely native-mbstring-absent runtime and real LLM-role startup remain **unrun**, not successful checks.

Checked independently on 2026-10-21, against working-tree product files based on HEAD `3ec52c68cd683cee8c7a5ca4efd74dbdb769ec3c`. The coordinator declared all product targets frozen and no workers active. The only repository write by this verifier is this report; no implementation, documentation, item record, board, dependency or history was repaired or updated.

## Authority, expectations and target identity

The first command was `cli/bin/workflow brief E-002 --for verifier`. I loaded `verify-outcome`, read root AGENTS in full, and derived the nine expectations below from the approved acceptance, canonical epic Design and decisions before reading any earlier verifier evidence. Child targets came from explicit `brief T-009` through `brief T-018 --for verifier --json` calls and their actual `owns` lists. No producer conclusion, worker/default brief or producer report was used. Later reads of baseline evidence and parts of earlier **verifier** reports supplied environment/preservation context, not the verdict.

The union contains **56 paths**, listed with hashes below. It includes the deliberately retained T-012 failed-target patch and the unchanged existing `CommandTest.php`; neither is an accidental implementation change. Reused ItemStore/ItemValidator ownership is sequential in the approved Design. All ten child verifier packets show current scope approval and done status, but these facts were not treated as evidence that their product behavior works.

Before executable checks, SHA-256 was recorded for **2,636 existing files** under `cli/`, `.agents/`, `.workflow/`, `docs/` and relevant root files, including source, tests, dependencies, all item records and the user comparison. The 56-path canonical compact JSON hash manifest has SHA-256:

`7e9a74d81614b6f6079feca9f7a8a486b1a0bccbcc535164e363f53401336f78`

Every target hash matched after checking. The wider snapshot had no changed, removed or added files before report creation. The final comparison permits only this new report. These are verification evidence hashes, **not new product freshness/snapshot metadata**.

## Environment and primary executable evidence

- Linux; existing Homebrew **PHP 8.5.10 CLI**, native iconv, mbstring, DOM, filter, JSON, libxml, tokenizer, XMLWriter and Phar loaded.
- Existing **PHPUnit 13.4.3**, using repository development dependencies. Every PHPUnit invocation set `TMPDIR=/tmp/opencode` and used `--do-not-cache-result`.
- Composer is absent from PATH, but the already available `/tmp/opencode/composer.phar` is **Composer 2.10.3**. No tool or prerequisite was installed/downloaded. The independent runtime dependency installation used existing cached archives, offline, in a scaffold under `/tmp/opencode` only.
- All adversarial fixtures and generated scripts/logs are external: `/tmp/opencode/e002-final-gfpc5ytx/`. The published examples retained their isolated roots at `/tmp/opencode/workflow-demo.4MfA8C` and `/tmp/opencode/workflow-install.BN8MuU`.

| Actual check | Observed result |
| --- | --- |
| From `cli/`: `TMPDIR=/tmp/opencode vendor/bin/phpunit --do-not-cache-result` | Exit 0; **699 tests, 5,567 assertions**. |
| Full rerun adding `--display-phpunit-deprecations` and `WORKFLOW_TEST_ROOT=/tmp/opencode` | Exit 0; same **699 / 5,567**; 4.556 seconds, 32 MB. Only the requested cache-option deprecation. |
| Focused `--filter 'RuntimeBootstrapTest\|ProtocolExamplesTest'` with the same temp/cache flags | Exit 0; **10 tests, 212 assertions**. |
| `cli/bin/workflow validate` (read-only, actual repository) | Exit 0, **OK (0 warnings)**, twice. |
| `git diff --check` | Exit 0, empty output, twice. |
| `php -l` on every changed/new `.php` file, plus `cli/bin/workflow` | **37 PHP files plus executable, all 38 pass**. |
| Existing Composer `validate --strict --no-check-publish --no-plugins` | Exit 0, manifest/lock valid. |
| Existing Composer `check-platform-reqs --no-plugins` on repository development vendor | Exit 0; actual runtime/development platform checks pass. Native extensions were also checked directly, since Composer reports mbstring as provided by the polyfill. |
| Independently authored domain/actual-CLI/scaffold probes | **112 named checkpoints pass**, across 180 logged command/helper calls, plus the two extracted shell examples. These are additional to the suite, not copied child receipt assertions. |

The sole PHPUnit notice is: `The "--do-not-cache-result" CLI option is deprecated and will be removed in PHPUnit 14. Use "--do-not-record-test-run-history" instead.` This same notice appears in the preimplementation baseline. No failed/skipped/incomplete tests were reported. Intentionally invalid fixture commands returned nonzero as expected; those are rejection evidence, not failed regression runs.

Primary scratch evidence is retained in `full-suite.log`, `validate.log`, `diff-check.log`, `lint.log`, `composer-validation.log`, `commands.json`, `additional-commands.json`, `scaffold-runtime-commands.json`, the three corresponding check lists, and the example logs under the external evidence root. Independently authored check sources are `domain-probes.php`, `integration-probes.py`, `additional-probes.py` and `scaffold-runtime.py`. No project test or runtime code was modified to produce a passing result.

## Per-criterion verification

### AC1 — ordinary literal and multiline input round-trips — pass

**Expected:** preserve brace-shaped literal text, Unicode, colons, delimiter-like content, nested unknown values and empty sequences; retain final YAML scalar newlines and parseable delimiters. Keep existing typed-list and Markdown-body normalization rather than silently change the schema.

**Observed:** independent codec probes preserved nested `{}`/JSON-shaped values, empty arrays, `café 水`, `---` inside a scalar, and final multiline strings with zero, one, two and three trailing newlines. Body CRLF/outer whitespace normalization remained as documented. An actual CLI epic title `Literal {} <info>水</info> : café\nsecond line` and multiline JSON/colon outcome survived creation, scope assignments, Plan/Design and repeated approvals/store rewrites unchanged. The full suite additionally exercised nested terminal-newline shapes, original malformed-YAML refusals and body boundaries.

**Method/environment:** direct calls to the frozen FrontMatter with real locked Symfony YAML, then actual executable subprocesses and decoded JSON on PHP 8.5.10; inspected FrontMatter and unchanged Item normalization. **Limits:** existing command/list trimming and body normalization are intentional contracts, not promises of arbitrary byte-preserving Markdown editing. Cross-platform/other YAML versions were not exercised.

### AC2 — structural refusals before persistence — pass

**Expected:** create/save/mutate and supported commands reject invalid hierarchy, missing/self references and dependency cycles atomically, including affected reverse relationships, without making unrelated legacy defects a global write gate.

**Observed:** actual `new`/`set` refused task-under-task, subtask-under-epic, epic-parent, missing dependency, self-dependency and a two-item cycle. Each refusal compared all fixture record/board file hashes and paths: no byte changes, new item or board refresh. A valid dependency edit succeeded before the reverse cyclic edit was refused, demonstrating rejection rather than a blanket mutation ban. The executed full suite covers ID/kind identity, all store routes, wrong-kind/missing parents, multi-assignment rollback, longer/new-parent cycles, malformed affected children/graph metadata, reverse-child checks, valid repair around unrelated legacy faults, and lock-time graph reading.

**Method/environment:** actual PHP 8.5.10 CLI negative/recovery probes, source tracing through locked ItemStore persistence into StructureValidator, and the actual structure store/command/validator regression tests. **Limits:** structurally unparseable legacy data remains a full-validate diagnostic; this is not an automatic repair/migration engine or a filesystem sandbox against arbitrary direct record edits.

### AC3 — current independent proof or deliberate exception on completion — pass

**Expected:** shared completion policy on close/set/store writes; tracked-target and receipt invalidation; no timestamp-shaped bypass; explicit force is an unverified exception, never success. Done history requires explicitly approved follow-up for material/new work.

**Observed:** an actual successful verifier receipt set proof; a **failed verifier immediately after success cleared it**, and close refused without changing fixture bytes. A changed Design cleared proof while preserving scope approval. Changes to owns, Decisions and scope also cleared proof; a subsequent worker receipt cleared it again. Actual `set status=done` refused stale/missing proof. Independent direct create/save/mutate probes refused missing/fabricated timestamps and verifier-shaped metadata, preserving original bytes and creating no `T-999`.

Actual force-close without proof recorded `Verification exception: explicit --force`, left `verified_at` and `last_result` null and did not claim verifier success. Separate actual CLI probes demonstrated that force cannot bypass an invalid legacy parent or rewrite done history. Routine phase/status/priority/progress changes retained valid proof; close then succeeded. Attempts to reopen the done item, materially replace Plan/Design/owns, add producer work or record unsuccessful verification all refused with `follow-up` instructions, byte-identical records and **no successor creation**. Non-material historical notes/priority corrections remained allowed. The full suite covers the remaining target fields, same-second repeated receipts, store entry routes, legacy proof mismatches and successful re-verification of an unchanged target.

**Method/environment:** actual CLI/store calls on PHP 8.5.10, before/after bytes, shared ItemLifecycle/ItemStore/Result/Close source tracing and executed lifecycle regressions. **Limits:** CLI role labels do not authenticate agent independence; same-path external artifact edits are convention/receipt-based, not detected by content hashes. I performed the present check independently; no receipt was written to real project records.

### AC4 — blind verifier packets and exact scope rendering — pass

**Expected:** verifier text/JSON excludes producer receipts, Progress and unknown metadata while retaining required review inputs; literal JSON survives Console decoration. Review/approve preserve exact scope values/list boundaries and approve captures values/hash together.

**Observed:** an actual fixture contained separate `PRODUCER_SENTINEL`, `PROGRESS_SENTINEL` and nested `UNKNOWN_SENTINEL`. Verifier text and JSON contained none, under both `--ansi` and `--no-ansi`; the unknown sentinel remained stored. All four explicit roles were exercised in both formats/decoration modes. Non-verifier JSON retained its compact appropriate last receipt; explorer output target was exactly `.workflow/items/T-001/outputs/`. JSON decoded the tagged Unicode child title exactly.

Actual review JSON included the epic/two descendants, literal multiline title/outcome, `["a,b","c"]` as distinct entries, empty scope-out list and multiline acceptance. Actual approve JSON matched every reviewed scope field and its hash. Decorated review/approve text preserved tags literally and displayed `["a,b","c"]` as unambiguous JSON literals rather than flattening commas. The executed scope/packet tests cover retained parent Design/decisions/artifacts/dependencies, null versus empty string/list, long values, duplicate/order semantics and a deterministic post-lock change proving approve output uses its captured locked values rather than a later reload.

**Method/environment:** real PHP 8.5.10 executable packets decoded/asserted independently, deliberate scratch metadata injection, allowlist/raw-JSON/ScopeOutput source inspection, plus actual regression tests. **Limits:** blinding is field-based; conclusions deliberately placed in approved narrative are not semantically censored. Literal-tag preservation here is guaranteed for JSON and exact scope text, not a new universal raw-text rendering contract for every human brief line.

### AC5 — literal ownership equivalence and exhaustive status — pass

**Expected:** lexical aliases/component ancestry work on future paths, root is not empty ownership, no glob/realpath/existence interpretation, no stored-path rewrite; all requested status groups are exhaustive while defaults stay bounded.

**Observed:** actual ready/validate agreed on `./new//nested/../module` versus `new/module/future.txt` without any corresponding product path existing. Additional actual CLI cases proved non-overlap of `src/a` and `src/ab/file.php`, root ownership overlap, empty ownership non-overlap, literal rather than expanded `src/*`, and unresolved lexical parents. Stored spellings and record bytes remained unchanged. Dependency-ordered ownership was not reported as parallel conflict.

A separate 47-item read fixture exceeded every human section cap: seven drafts, twelve active, seven ready, seven waiting, seven done and seven cancelled; seven active items also needed decisions. Actual `status --all` text **and JSON** returned every expected ID in each of the seven groups. Default text retained `… and` caps; default JSON retained its existing schema without terminal groups. No projection writes occurred. Full regressions additionally cover all separator/root/Unicode/glob/component cases, real symlink aliases remaining lexically distinct, default limits and larger ready populations.

**Method/environment:** actual CLI status/ready/validate subprocesses, independently specified expected ID sets and byte snapshots, PHP 8.5.10; shared comparator inspection. **Limits:** ownership deliberately is not filesystem identity or glob expansion, and overlap reporting is not a new start gate.

### AC6 — contained scaffold installation and host preservation — pass

**Expected:** reject equal/descendant/ancestor/root destinations before writing, with symlink-aware resolution; preserve host root authority/config/ignore/history under default and force; hold/reject unsafe links/types; scaffold/manual merge only.

**Observed:** installed a real independent scratch kit, then used **its installed binary** for hazardous tests. Equality, missing descendant, ancestor, `/`, and an alias through `runtime-alias/../future-missing` all refused with unchanged scratch-source hashes. Dangling and file intermediaries were rejected before parent-segment bypass. No recursive install into the actual repository or actual source self-copy was attempted.

Real default and force installation into a host fixture preserved byte-for-byte root AGENTS/README/LICENSE, config, root/nested gitignores, PRD/Architecture, item history, outputs, target-only files and existing host vendor sentinel. Ordinary docs collisions skipped by default and refreshed only with force. Two source-only scratch item records were not imported. Source links were not copied/followed; destination directory links and file-directory collisions stayed held; initializer/board-link rejection left the external marker and workflow documents unwritten. Installed role files retained the explicit canonical-root/manual-merge instructions, and installer output clearly required manual AGENTS merging rather than claiming completion of it. Full filesystem regressions cover further source/destination/workflow/template/board collision cases.

**Method/environment:** real Linux directories/symlinks and actual source/installed PHP 8.5.10 CLI invocations; independent saved host/outside bytes and pre/post scratch hashes; InstallCommand/ProjectInitializer inspection and full regression suite. **Limits:** no claim of resistance to a concurrently hostile filesystem replacement race; board regeneration is derived output, not a multi-file transaction. A reviewed host-rule merge and actual role behavior remain human/harness obligations.

### AC7 — runtime prerequisites and public examples — pass, with named unrun environments

**Expected:** manifest/lock/bootstrap/docs agree on PHP >=8.4.1, Composer 2, iconv and polyfilled runtime mbstring; separate native development requirements; preserve dependency versions and exercise real no-dev bootstrap plus public examples where available.

**Observed:** manifest and lock platform both require `>=8.4.1`; strict Composer validation passes. All **15 runtime + 27 development package objects** (not just versions) equal HEAD. Root manifest changes only the PHP floor; lock top-level changes only `content-hash` and `platform`. Existing development vendor versions exactly equal the HEAD lock graph.

An actual clean scaffold initially had no vendor; its binary failed with the documented runtime `composer install --no-dev` and separate development guidance. Using existing Composer/cache with `COMPOSER_DISABLE_NETWORK=1`, `COMPOSER_CACHE_READ_ONLY=1`, isolated Composer home and `--no-scripts --no-plugins`, real `install --no-dev --no-interaction --no-progress --prefer-dist` succeeded in the scratch scaffold. Its installed graph was exactly **15 runtime packages**, `dev=false`, no PHPUnit, and unchanged manifest/lock hashes. Actual no-dev platform checks, installed `--version`, `list --raw` and project `validate` all exited 0. The source repository vendor was not used as a symlink substitute for that install.

With the available PHP's native mbstring functions disabled, actual installed polyfill autoload supplied non-internal replacements: Unicode length/case/conversion assertions and real CLI startup passed. Native mbstring remained loaded: this is fallback execution evidence, **not** a genuinely extension-absent environment.

I extracted and executed the current README quickstart and install smoke shell blocks in isolated roots, not hand-rewritten alternatives. Both exited 0 and validated. Independent final reads confirmed quickstart's exact `hello\n`, valid parent hierarchy, two done items with matching successful verifier receipts, relative existing artifacts and retained history; smoke retained one cancelled standalone record, not deleted history. Static role/config reference and public link regressions pass.

**Method/environment:** actual Composer 2.10.3, PHP 8.5.10, locked/cache-only no-dev installation, native extension inspection, real executable/bootstrap/polyfill checks, published shell scripts and runtime/example tests. **Limits:** PHP **8.4.1** and truly absent native mbstring are **unrun**; named alternative binaries were unavailable, and `php -n` still has mbstring in this build. Composer 1 and absent individual development extensions were not tested. No tool/prerequisite was installed to manufacture those environments. Real LLM-role/harness startup is **unrun**; static prompt/reference checks do not prove it.

### AC8 — one concise canonical operating contract — pass

**Expected:** AGENTS canonical; each role explicitly loads host root rules and surfaces manual merges; staged epic/discovery then exact-child approval; independent discovery can live on the epic; sole epic Design scope map; concise small-work/default-verifier/explicit-exception path without new gates.

**Observed:** read all 14 owned prose files, current four role prompts, adjacent unchanged method skills/templates/config references and actual parent/child verifier designs. AGENTS contains the operating rules; roles delegate policy to it; skills describe methods/commands; README introduces executable use; PRD/Architecture describe product/mechanisms and limits rather than a duplicate operating loop. All four **actually installed** role files explicitly require reading root AGENTS even separately and surface absent/incomplete/conflicting rules for reviewed manual merge. Preserved host AGENTS was not silently replaced by those prompts.

AGENTS scope/sequence sections explicitly stage approval after independent evidence, permit epic-hosted discovery and useful optional discovery children, retain unchanged-epic approval and reapprove only affected changed scopes. It assigns one epic Design scope/coverage/dependency/ownership/integration map and reference-based child designs. Its small-work paragraph is one item, short exact approval, one-line Plan/Design, justified no-exploration, default independent verifier and deliberate force exception. Done-history follow-up and conventional same-path freezes are explicit. No updater, second canonical protocol, snapshot/fingerprint metadata or new phase/approval gate was introduced.

Independent HEAD whitespace-token counts confirm **9,831 → 6,778 words across precisely these 14 prose files**, down **3,053 (31.05%)**; this is not a repository-wide or every-file-shrinks claim. Detailed counts appear below.

**Method/environment:** direct frozen text/source/reference inspection, actual installed role bytes/manual-merge guidance, independently computed HEAD counts; Linux/PHP 8.5.10 for executable/static reference tests. **Limits:** these instructions specify role loading and independence; actual LLM compliance/human review cannot be established by this CLI verification and was not simulated as success.

### AC9 — lower bookkeeping without phase-order gates; integration regressions — pass

**Expected:** optional atomic phase/start flags on Plan/Design, old content-only defaults/manual set retained; no inferred status/order gates; shared lifecycle protects completion; regression and independent epic verification succeed.

**Observed:** actual Design jumped directly to verify while status stayed todo. Content-only Plan preserved phase. An explicit backward Plan phase plus `--start` changed content/state together to explore/in-progress and refreshed the board; approval stayed current unless scope actually changed. Invalid phase and conflicting content sources refused with all fixture bytes unchanged. Material done Plan/Design/start requests were refused by the shared lifecycle rather than a duplicated local policy. A separate actual command probe started an unapproved draft directly in verify and closed after explicit verifier proof despite missing Design: warnings/diagnostics did not become new write/phase gates.

The full suite passes **699 / 5,567**, actual repository validation has zero warnings and the independent probes cross the serializer→locked structure→lifecycle→scope approval→role packet→status/ownership→phase/start→documentation seams. Canonical approval and verification remain separate; actual approval/Plan/Design operations and proof invalidation were checked together. Receipt and close remain separate actual calls. This report supplies independent final epic verification, not a producer claim or a forced completion.

**Method/environment:** actual PHP 8.5.10 CLI state/content/approval/board comparisons, shared-source inspection, all focused regressions in the full suite and independently authored scenarios. **Limits:** no exhaustive concurrency/interleaving proof, coverage percentage or real LLM-independence authentication is claimed. Board refresh remains separate derived output. No material acceptance criterion is left without source/actual executable or prose evidence.

## Baseline delta, ownership and preservation

The baseline report at this same HEAD records **48 tests / 173 assertions**, exit 0 with the same deprecated cache-option notice, and validation `OK (0 warnings)`. The current frozen run is **699 / 5,567**: **651 additional parameterized test cases and 5,394 assertions**, not a claim of that many new test methods. The original checks were not silently replaced with a different baseline.

| Approved boundary | Expected and observed product delta |
| --- | --- |
| T-009 codec | YAML dump flags/boundary newline; expanded existing codec tests. |
| T-010 scaffold | Component-aware destination resolution and preserved/held copy/initializer paths; new isolated filesystem tests. |
| T-011 runtime | PHP-floor metadata and bootstrap guidance, new runtime tests; package versions/references unchanged. |
| T-012 structure | New pure StructureValidator, locked candidate/affected-graph validation, new store/command/validator tests; retained failed-target history. |
| T-013 packets | Allowlisted role projection and raw JSON, new role/literal tests. |
| T-014 lifecycle | New shared ItemLifecycle, store/receipt/close integration and domain/store/command regressions; existing CommandTest remains unchanged. |
| T-015 exact scope | New small ScopeOutput helper with shared review/approve projection and output tests, no Item/scope-hash schema change. |
| T-016 coordination | All status groups and one lexical path comparison shared by ready/validate; focused projection/path tests. |
| T-017 bookkeeping | Plan/Design optional phase/start flags and board refresh plus atomic/refusal tests. |
| T-018 protocol | Exactly 14 related prose files and new executable ProtocolExamplesTest. |

`git diff --stat` reports 35 modified tracked files, 1,561 insertions and 1,831 deletions; untracked owned source/tests are separately included in the 56-path target manifest. Comparing every changed/untracked product path to the union of actual child ownership found **no unowned product changes**. Expected new epic/child records/evidence are not accidental product files; the preexisting untracked user comparison was excluded from product ownership, not modified or absorbed into scope. No extra per-attempt project copies or item-directory entries appeared.

- Original **nine done records**, E-001 and T-001–T-008, are byte-for-byte equal to `git show HEAD:<path>`. All current item records also remained identical across this verification interval. No auto-reopen, successor generation or bulk migration occurred.
- `docs/design-notes.md` and tracked historical comparison/config documents are HEAD-equal. Earlier source-context hashes additionally match `docs/handoff-compare-3.md`, `docs/pilot-compare-3.md`, `docs/opencode.example.json` and the historical design notes.
- User-owned untracked `docs/handoff-compare.md` remains SHA-256 `cbfbe0c15de0e97cb7a5b21b2ac180652e421abaf7bd94cfca55f9a39dcbf963`, matching earlier T-014/T-018 source-context manifests. Its mode, size, nanosecond mtime and ctime also equal the original preimplementation baseline snapshot. There is **no HEAD content comparison for an untracked file**, and no claim to have independently observed bytes before that earlier evidence was recorded.
- Vendor is ignored/not in HEAD, so a literal vendor-content-versus-HEAD comparison is unavailable. Instead, all **42 installed package versions and all locked package objects match HEAD**, and all **2,497 vendor file hashes** exactly match `/tmp/opencode/t011-runtime-d0f5c2po/starting-vendor-hashes.json` from before runtime implementation. They also match this verifier's before/after snapshot. No source vendor, package graph, Composer tool or cache was updated.
- The preimplementation snapshot is `/tmp/opencode/e002-baseline-2ce94ajs/project-before.json`; it is metadata, not invented historical content hashes. Later explicit hash context was used where available and distinguished from HEAD evidence.

## Independent prose measurement

Method: `len(text.split())`, including Markdown/front matter/code fences; baseline bytes read with `git show HEAD:<path>`. No producer word-count output was used to compute these values. `ProtocolExamplesTest.php` is excluded from the prose total.

| Owned prose path | HEAD | Frozen |
| --- | ---: | ---: |
| `AGENTS.md` | 1,951 | 1,185 |
| `README.md` | 1,075 | 714 |
| `docs/install.md` | 406 | 607 |
| `.agents/roles/orchestrator.md` | 695 | 234 |
| `.agents/roles/explorer.md` | 261 | 181 |
| `.agents/roles/worker.md` | 299 | 203 |
| `.agents/roles/verifier.md` | 331 | 206 |
| `.agents/skills/workflow-cli/SKILL.md` | 902 | 690 |
| `.agents/skills/decompose-work/SKILL.md` | 574 | 299 |
| `.agents/skills/install-workflow/SKILL.md` | 304 | 216 |
| `.agents/skills/fresh-session-handoff/SKILL.md` | 199 | 165 |
| `.agents/skills/verify-outcome/SKILL.md` | 308 | 244 |
| `.workflow/PRD.md` | 1,363 | 831 |
| `.workflow/Architecture.md` | 1,163 | 1,003 |
| **Total** | **9,831** | **6,778** |

Installation guidance grows by 201 words; the overall reduction is 3,053 words/31.05% while making preservation/runtime limits explicit.

## Frozen target manifest — before and after SHA-256

These are the distinct actual child-owned paths. Each listed hash was measured before verification and matched after all executable checks; only this newly added report is outside the freeze. Full wider before/after evidence remains in the external evidence root.

| Path | SHA-256 (before = after) |
| --- | --- |
| `.agents/roles/explorer.md` | `19558d1e9300fa0ea0cee0c1530e587251d6659c1ae18b0212dc756e989f6173` |
| `.agents/roles/orchestrator.md` | `b020e5a62bd421b28c1f86bfaca3aebd37ee135413f4053f2093ba601b55b6ca` |
| `.agents/roles/verifier.md` | `1dc8d6e566ba6c875278f8f9d2d55f0a9eda27e21303d2c697f927e69ca235ac` |
| `.agents/roles/worker.md` | `aebfc14120125ba56bdb918d33f10796f55d67e8de55eb2b86ccf297041d017e` |
| `.agents/skills/decompose-work/SKILL.md` | `06f0798dfbe3182e5b45fbb34482e7b8dd70fa21b0e06078250759c6f4069254` |
| `.agents/skills/fresh-session-handoff/SKILL.md` | `2351fa7062b1f5973b1094af4384da9b2664704b20e89d9f222d098c364460c4` |
| `.agents/skills/install-workflow/SKILL.md` | `1ab42e362ba360da78171213b06f6ee84c5454f4d7ebe83c1f45cf55d508f974` |
| `.agents/skills/verify-outcome/SKILL.md` | `12efa92e17e255bf500201199317d6db2d91f5b8e65edd929a609b2abceb1d9f` |
| `.agents/skills/workflow-cli/SKILL.md` | `888fbaaa5281c749b467c3b9835f0667af8fea259d356b4f0cea1138ca0f799b` |
| `.workflow/Architecture.md` | `639194264736917bd55a056c3037cf838b1d4cb78c3d44403353c91fa6fe8a7a` |
| `.workflow/PRD.md` | `a5f625209b208ca9efe0d602026c3cbc92739218b540a5599fd5d7503757a983` |
| `.workflow/items/T-012/outputs/failed-target.patch` | `2f4c54623a617dbfe95645615db9d3757fc112e60b8c4e555c97142bf3ace4c3` |
| `AGENTS.md` | `41f62a68e6150325f1ff20d4ca0d532fcb32d9f9c7d999734d386eda833aaf7a` |
| `README.md` | `facf28fec93859916354f772146bbe0298fb5ab17dcba73b5cadaf8e94826cce` |
| `cli/bin/workflow` | `323be940db4ceb592d08abec5d5ce884b04f0a3e5257be7bd1883c63558586fb` |
| `cli/composer.json` | `ba18ee277c89137f1d36f89af78c8f6deb74bf9310cfb85d29ab6becd5129c2d` |
| `cli/composer.lock` | `b7a8685d64aaec407e60e2858c372cf88365e9fcc5444156f817094b6e2b0b65` |
| `cli/src/Console/Command/ApproveCommand.php` | `dc7551cdf7098e8a65388ee07465d91c90dc83ccbbe71bd2d870373fea93833e` |
| `cli/src/Console/Command/BaseCommand.php` | `1fad3db121953ea05d36b97c9bf3699b3332d2987fdbf4be7ebf42f17785aae0` |
| `cli/src/Console/Command/BriefCommand.php` | `10f37056c415bd537035930c479cf0453c61ee19cf7888a564f0beab90dd2df2` |
| `cli/src/Console/Command/CloseCommand.php` | `15588f576bf857ec75ba2d8dfb2fe125f61ee374ed3a5df8af295ce2d8ddbc8e` |
| `cli/src/Console/Command/DesignCommand.php` | `9209ad928b80ee0dd8e22d0140daa85a2ea1d9910b1cd7c2a7228f37797e6bac` |
| `cli/src/Console/Command/InstallCommand.php` | `e4c11c1b314542955b6f41b7010af755c79cb7ac4b6c68c83e55269eacb83774` |
| `cli/src/Console/Command/NewCommand.php` | `60cc90d08b6ad22eef595e17a219e442c1bf205f555477b50f754429934dc2a4` |
| `cli/src/Console/Command/PlanCommand.php` | `14fc694814559d7db81720ee5df6a4083ab3c1987290d7967ef683ee4f1605a6` |
| `cli/src/Console/Command/ResultCommand.php` | `558e9d1f9146ec39b58ace4120ffdd3feb2480226dd640ce0773563aa5a69a10` |
| `cli/src/Console/Command/ReviewCommand.php` | `d0cc27f1dd4830fc9d944242b02a05ba211dcc313b1fe4bd33ede6d7be89b072` |
| `cli/src/Console/Command/StatusCommand.php` | `483d0d8d2d5a344e8172d3d8f2c8c08beab8126b76e6551f3eab08a4506f3e1c` |
| `cli/src/Domain/FrontMatter.php` | `ceb87f671b8a881e0721723e8da1ffa21f00cef355619e466960938cf4a4b7ef` |
| `cli/src/Domain/ItemLifecycle.php` | `a1474d75d58e6bfea364abd403944d26acdf70509310b0f8215f6131ffdd0915` |
| `cli/src/Read/ProjectView.php` | `59e99f9c82b71c2ad0846b92a25609f3626767fad6435499eb88226d00a40af6` |
| `cli/src/Store/ItemStore.php` | `962bbfb4541c48194b7703d04abe50c3f48d633f741652146f742fb02280c959` |
| `cli/src/Store/ProjectInitializer.php` | `9235c16777eeac8704fcd594d3332b8ef601b33abde39aabf8d7f26e2f9c7b2c` |
| `cli/src/Support/ScopeOutput.php` | `893494156b4520e27171399d0cfaba13cc05744ee19d9fbc21bc137f1a039226` |
| `cli/src/Validate/ItemValidator.php` | `f8bad7986e88682a46a51ab5f7b06d1617a0dd461cd78f5fc8ee747ee48fa153` |
| `cli/src/Validate/StructureValidator.php` | `3a0023fd98cb250f94f0edf08d1f5c041d141c3b8411bd8429bb25e541ea3e37` |
| `cli/tests/Console/BriefPacketTest.php` | `ad09940bcf9fad23b13c46d4cb5f514ba552d29cda15fab63814713ddc8e03da` |
| `cli/tests/Console/CommandTest.php` | `0e6cf5a00b20352f1f3296722000030071e431569f05a67a22c059d0ce1bef28` |
| `cli/tests/Console/CoordinationProjectionTest.php` | `7f65f9e5ac328d61a7f00e1b3efaabe8e970dd0082d890c66dc000018c99e984` |
| `cli/tests/Console/InstallScaffoldTest.php` | `5224456ffe971240bc511f963f42e3b4e47d8c274d3e21e6730bca49ac5a81d0` |
| `cli/tests/Console/JsonLiteralOutputTest.php` | `bfb24962f585b737427fc9e343c27c3f9746b92ab922abaf05100a3101e57f28` |
| `cli/tests/Console/LifecycleCommandTest.php` | `ddeeccf9def4eb0bbc5a11297bb66f755f66d7fd4d87d37928ad59e4481c300b` |
| `cli/tests/Console/PlanDesignStateTest.php` | `fa20a6e82dbac76e23b30bc5c5739b7ac1604877a6cf8addb8767df9dc30892b` |
| `cli/tests/Console/ProtocolExamplesTest.php` | `de6b1b3354bc2a54158d90be60f595da3a955252383e898f94c619c27ece0e31` |
| `cli/tests/Console/RuntimeBootstrapTest.php` | `f76ce012501682e2787dead9906d03e83271ace82fc33976893ad5c9a3f21f8c` |
| `cli/tests/Console/ScopeOutputTest.php` | `add59ef33f6f8bb3fadcaea19d989a9ed51b73488cd9efc83e96cb8d2086374a` |
| `cli/tests/Console/StructureCommandTest.php` | `de8e3cfbebf0a10e65eb86be616330cf9a27f0ab42c90359b9eaac17b8fe7d08` |
| `cli/tests/Domain/FrontMatterTest.php` | `816f97aa60a59505a21bc081e8bd4827244be5e78f36efd66746d31526925738` |
| `cli/tests/Domain/ItemLifecycleTest.php` | `aa827bbb504bb5ff3a2505f334a9630da4e0eae8ba57df54ec346f8643b90cd7` |
| `cli/tests/Store/LifecycleWriteTest.php` | `e0bacd02c9faf0b6218d109b4691c63a0eb320c9d2e5c663dbfc1d58bebde330` |
| `cli/tests/Store/ProjectInitializerSafetyTest.php` | `b98dff6ec3484e088dcacf0f9965021da34af81b3aea4368ed6137c5abe03cdc` |
| `cli/tests/Store/StructureWriteTest.php` | `2fb0abb4ccc33e800c62de2dc898d3739119462092496f45f475bc55a9e51e21` |
| `cli/tests/Validate/ItemValidatorTest.php` | `b82e554687ea34497f2103cfa30df029886861288539048c7dc1de6c10c5888b` |
| `cli/tests/Validate/OwnedPathTest.php` | `b8acc0b2d449d042180a5b056c920df7c5c35b406cafb253ee44d29e61ef818d` |
| `cli/tests/Validate/StructureValidatorTest.php` | `959d7ab520fbf833c8da2ff3a518d62793c787fec86bc78fb10085982ec05fdc` |
| `docs/install.md` | `809844b1ffea990aa2d267f09d072a1eb4caf1b95990af8756a592697ab2fa97` |

**Next:** coordinator records this successful independent verifier receipt for E-002, including the named environment limits, then closes the epic in a separate CLI call while the target remains frozen. No repair, scope decision, force exception or further implementation is required by these findings.
