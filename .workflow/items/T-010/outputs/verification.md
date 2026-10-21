# T-010 — independent full verification

**Result: pass for all seven acceptance criteria.** This is full verification of the four owned targets, not a targeted recheck. The focused suite passed **57 tests / 320 assertions**, three existing init/board integration tests passed **14 assertions**, and independently written supplemental probes passed **93 cases / 957 checks**. No implementation repair was made.

## Authority, environment and frozen target

The first command was `cli/bin/workflow brief T-010 --for verifier`; `verify-outcome` and root `AGENTS.md` were loaded. Expectations below were derived from the acceptance criteria and Design before inspecting implementation/tests. No producer receipts or conclusions were read, and no workflow record commands were run beyond the read-only brief.

**Environment E1:** Linux x86_64, kernel `7.2.5-200.fc44.x86_64`, unprivileged UID 1000; PHP **8.5.10**, PHPUnit **13.4.3**, Symfony Console **v8.1.9**, existing project dependencies. Executable: `/var/home/linuxbrew/.linuxbrew/Cellar/php/8.5.10/bin/php`. Every test/probe invocation used `TMPDIR=/tmp/opencode`; PHPUnit used the requested `--do-not-cache-result`. No prerequisites were installed. Supplemental probes completed at **2026-10-21T05:03:53Z**; final source hash check was **2026-10-21T05:05:28Z**.

The assigned freeze was confirmed by identical SHA-256 values on initial inspection, before/after supplemental probes, and final inspection:

| Frozen owned target | SHA-256 |
| --- | --- |
| `cli/src/Console/Command/InstallCommand.php` | `e4c11c1b314542955b6f41b7010af755c79cb7ac4b6c68c83e55269eacb83774` |
| `cli/src/Store/ProjectInitializer.php` | `9235c16777eeac8704fcd594d3332b8ef601b33abde39aabf8d7f26e2f9c7b2c` |
| `cli/tests/Console/InstallScaffoldTest.php` | `5224456ffe971240bc511f963f42e3b4e47d8c274d3e21e6730bca49ac5a81d0` |
| `cli/tests/Store/ProjectInitializerSafetyTest.php` | `b98dff6ec3484e088dcacf0f9965021da34af81b3aea4368ed6137c5abe03cdc` |

## Method and evidence

Actual implementation and both new test files were inspected. Supporting init/board code was inspected to check the caller boundary. All executable installation probes used tiny synthetic kits under `/tmp/opencode`, with byte-identical copies of the frozen command and initializer, and the project's real autoloader/dependencies. No install used the actual repository as its source. Fixture snapshots compared directory entries, link destinations, file SHA-256 and permission bits without traversing links.

Commands run from `cli/`:

```sh
TMPDIR=/tmp/opencode php vendor/bin/phpunit --do-not-cache-result --display-phpunit-deprecations tests/Console/InstallScaffoldTest.php tests/Store/ProjectInitializerSafetyTest.php
TMPDIR=/tmp/opencode php vendor/bin/phpunit --do-not-cache-result --filter 'testShowSectionAndInitIdempotence|testBoardRefreshesAfterStateChanges|testNewJsonStaysCleanWhenBoardRefreshes' tests/Console/CommandTest.php
```

Both exited **0**. The only reported issue was PHPUnit's runner deprecation of the explicitly requested `--do-not-cache-result` option; there were no assertion failures.

Independent supplemental command, run from the project root:

```sh
TMPDIR=/tmp/opencode php /tmp/opencode/t010-verification-0j3AQKS2/verify.php
```

It exited **0**, with **93 cases, 957 checks, zero failures**. Evidence retained outside the repository:

- `/tmp/opencode/t010-verification-0j3AQKS2/verify.php` — independent harness; SHA-256 `7f4bdd97e000579313ee47da596c9e2119332e0e3b4f273a90d9c12c52a5c5e6`.
- `/tmp/opencode/t010-verification-0j3AQKS2/results.json` — per-probe results, environment and before/after target hashes; SHA-256 `870db9b3bb915a4d8ac9cb052b7be53b72709b717c7addf166bf3856a57f3c04`.
- `/tmp/opencode/t010-verification-0j3AQKS2/focused-phpunit.log` and `integration-phpunit.log` — captured suite output.
- `/tmp/opencode/t010-verification-0j3AQKS2/cases/` — isolated fixtures and each subprocess's target, force mode, exit and output in `commands.log`.

For a repeat, copy `verify.php` to a **new empty directory under `/tmp/opencode`** and run it there; fixture names are deterministic within a run. Temporary evidence is supplementary; the acceptance observations and frozen hashes are recorded here durably.

## Acceptance results

### 1. Containment rejects equal, descendant, ancestor and root before writes

- **Expected:** Reject physical equality/ancestry in either direction, including symlink aliases, missing suffixes and subsequent parent segments; allow an outside sibling whose name merely shares the kit prefix.
- **Observed:** Twenty default/force containment probes returned exit **2** with unchanged fixture snapshots. Cases included equality, new descendants, ancestor, alias equality, alias plus missing suffix, child alias then `..`, alias then `..` to ancestor, missing component then `..`, repeated separators and `.`. An outside symlink followed by `..` installed at its physical parent's missing suffix, not its lexical parent; a relative `kit-sibling` succeeded and retry succeeded.
- **Method/environment:** Focused tests plus independent subprocesses and snapshot comparisons in E1; inspected `InstallCommand` lines 48–83 and 181–213. A real `/` target was executed with PHP `open_basedir` restricting access to fixtures and required source/dependency directories. It returned **2**, `Target cannot be a parent of the kit root.`, without any forbidden-access warning.
- **Result:** **pass**. **Limits:** The root probe was deliberately sandboxed against filesystem-root writes; the actual kit was never self-copied.

### 2. Dangling links and non-directory intermediates cannot be bypassed

- **Expected:** Refuse a dangling link or file component even if later segments contain `..`; do not silently normalize past it.
- **Observed:** Nine independent target forms covered dangling links, plain files, links to files and self-loop links, as final/intermediate components or before `..`. All returned **2** with the expected dangling/not-directory diagnostic and identical snapshots. The supplied focused tests also passed their seven invalid-component cases.
- **Method/environment:** Real POSIX links/files, subprocess exit/message assertions and snapshots in E1; component-by-component resolver inspection.
- **Result:** **pass**. **Limits:** POSIX/Linux path semantics only; no concurrent filesystem replacement was simulated.

### 3. Root AGENTS, README and LICENSE are create-only, including force

- **Expected:** Create absent ordinary root documents; never replace existing host documents, links or conflicting types, even with force.
- **Observed:** A fresh install copied all three absent documents exactly. They were then replaced with distinctive host bytes containing CRLF, NUL and `0xFF`; every byte survived default, repeated default, force, repeated force and subsequent default. Live/dangling document links and an existing README directory also remained untouched in both modes.
- **Method/environment:** Fresh/repeated installation, byte equality, link/type assertions and external-target snapshots in E1; inspected the root-document loop's unconditional `false` force argument.
- **Result:** **pass**. **Limits:** No automatic document merge is performed or claimed.

### 4. Ordinary kit collisions skip by default and refresh only explicitly

- **Expected:** Existing ordinary kit-relative files skip without force, refresh with force, and do not trigger merges or deletion of target-only files.
- **Observed:** Host versions of `docs/guide.md`, a skill and `cli/composer.json` survived two default runs, changed exactly to source bytes on force, and stayed correct on force/default retries. Target-only files in `docs`, `cli` and `.agents` survived all runs. Existing nested gitignores remained byte-identical even during force.
- **Method/environment:** Repeated force-mode sequence, explicit expected content and target-only preservation assertions in E1; inspected `copyTree` and `copyFile`.
- **Result:** **pass**. **Limits:** This is scaffold refresh, not a managed updater, provenance system or merge engine. The kit-relative manifest is an ordinary refreshable kit file, unlike root host configuration.

### 5. Links/type conflicts are held or rejected, including initializer/board

- **Expected:** Do not follow source/managed-destination links or replace conflicting file/directory types; reject unsafe initializer and board destinations.
- **Observed:** Default and force runs, each repeated, held root/nested destination links, live/dangling links, source file/directory/dangling links, file-over-directory and directory-over-file conflicts. Source root links were not imported. External bytes, directory entries and executable mode `0600` were unchanged, including linked `cli/bin` (no escaped chmod).
- **Observed, initializer boundary:** The independent matrix covered **six managed paths × four collision shapes × two modes = 48 cases**: `.workflow`, `items`, PRD, Architecture, gitignore and board; links to files, links to directories, dangling links and wrong types. Direct initialization rejected each without any fixture change; installation rejected each on two attempts while preserving the collision/external target, then succeeded after removing only the fixture-owned collision. Linked template files/directories/ancestors, dangling or directory templates, missing templates, a linked project ancestor and file-valued project root were also rejected; safe retry/idempotence passed.
- **Method/environment:** New tests plus independent exception/message assertions, subprocesses, snapshot/mode checks and manual fixture-only recovery in E1; inspected initializer preflight and board writer call ordering.
- **Result:** **pass**. **Limits:** Ordinary scaffold copies can precede an initializer refusal; whole-install rollback is not promised. No hostile race-resistance or special-file/hard-link guarantee is inferred.

### 6. Host configuration/history survive; source history never imports

- **Expected:** Preserve host configuration, gitignores, PRD, Architecture, item records and outputs; exclude source workflow records. Only a safe derived board may regenerate.
- **Observed:** Root `.gitignore`, `opencode.json`, `.claude/settings.json`, `.agents/settings.json`, nested gitignores, workflow config/PRD/Architecture/gitignore, an existing done item and binary output all remained byte-identical across the default/force/retry sequence. Source root configuration, source `.workflow` PRD/Architecture/items/outputs/board, nested `.workflow`, `.git`, vendor/tools/cache were not imported. Successful boards reflected the host item, not source history.
- **Observed, error/retry:** With malformed host YAML, both default and force returned success with a board warning while preserving the entire workflow snapshot, including prior board and binary output. After a fixture-only host-record correction, retry regenerated the board without changing item/output bytes. Direct initializer retries left existing board/history untouched.
- **Method/environment:** Distinct source markers, exact host bytes and workflow/item snapshots in E1; fresh installation, successful repetition and induced board-render failure/recovery.
- **Result:** **pass**. **Limits:** Preservation does not mean malformed history is repaired or merged; board failure remains a warning. Shared store/codec behavior outside this boundary is not independently certified by this item.

### 7. Manual merge/runtime-only guidance and isolated regressions

- **Expected:** Tell users to merge AGENTS manually and use `composer install --no-dev` for runtime; regress default and force behavior in isolated filesystems.
- **Observed:** Actual success output contained the manual AGENTS merge instruction, `composer install --no-dev`, scaffold-only/no-automatic-merges-or-deletions guidance and a validation command. Both new test files use isolated `/tmp/opencode` fixtures; command tests load the command beneath a synthetic kit root and cleanup does not traverse links. Focused, integration and independent normal/error/retry checks all passed.
- **Method/environment:** Output assertions, implementation/test inspection and real PHP execution in E1.
- **Result:** **pass**. **Limits:** Composer installation was not executed and no prerequisites were installed; dependency/bootstrap certification belongs to runtime work. No claim is made for PHP 8.4.1 specifically, other operating systems or the entire repository suite.

## Final assessment

The implementation follows the approved Design: physical-component resolution before parent traversal, separator-aware containment, create-only root authority, explicit ordinary-file force behavior, initializer preflight, safe board regeneration and no history merge/import/delete mechanism. All four frozen hashes remained unchanged. The only project file written by this verification is this report; targets and workflow records were not changed. There is no failing reproduction or repair request.
