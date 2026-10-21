# T-016 independent full verification

## Scope and expectations established before implementation inspection

This is a full verification of all six acceptance criteria, not a targeted recheck. The verifier first ran `cli/bin/workflow brief T-016 --for verifier` and loaded `verify-outcome`. No producer conclusion was requested or read. The frozen target comprises StatusCommand, ItemValidator, ProjectView, CoordinationProjectionTest and OwnedPathTest at their brief-listed paths. Only this report may change in the repository.

| AC | Expected behavior and independent check |
| --- | --- |
| 1 | Default text retains its established per-section caps. Establish those caps from the existing contract/baseline, then exceed every cap in a temporary project and compare default text with default JSON; JSON's existing behavior must not change. |
| 2 | `status --all` text exposes every draft, active, ready, waiting, needs-decision, done and cancelled item, with no per-section truncation. Use distinguishable IDs and fixtures above every ordinary cap. |
| 3 | `status --all --json` preserves existing working-set groups and adds complete done and cancelled groups. Check exact group membership, including beyond-cap IDs. |
| 4 | The same pure lexical comparison is used by ready and validate. Slash variants, repeated separators, dot components and safely resolvable repeated parent components must overlap even for nonexistent future paths; verify pure table cases and actual temporary-project CLI behavior. |
| 5 | Project-root spellings overlap nonempty project-relative ownership but empty ownership does not. Component boundaries distinguish `src/a` from `src/ab`. Check pure comparisons and actual CLI projections. |
| 6 | Read operations leave stored owned-path spellings and all item bytes unchanged. Dependency-ordered ownership suppresses conflicts consistently in ready and validate; unordered active and concurrent-ready conflicts remain visible. Check ordered and unrelated cases against actual CLI output and before/after file hashes. |

The design requires one shared lexical comparator, bounded defaults, complete explicit-all reads, no stored-path rewriting and no added ownership gates. No filesystem existence, glob expansion, realpath or symlink-identity guarantee is expected.

## Execution and results

**Overall: PASS — all six acceptance criteria and the supplied Design verified.** Expectations above were recorded before inspecting the implementation or its tests. Independent probes did not reuse the producer's test helpers. No repair, code change, item-record update or user-document change was made.

### Environment and commands

- Project: `/var/home/adam/Projects/general-agentic-workflow`; Git HEAD `3ec52c68cd683cee8c7a5ca4efd74dbdb769ec3c`. Verification targets are the current uncommitted working-tree files, identified by hashes below, not a claim that HEAD contains these changes.
- Date: 2026-10-21 UTC. Initial target hashes: 06:09:45; independent CLI probes: 06:12:38–06:12:51; final code-freeze comparison: 06:13:55.
- Linux `7.2.5-200.fc44.x86_64`, glibc 2.43; PHP CLI 8.5.10 NTS; PHPUnit 13.4.3; Python 3.14.8.
- All test/probe executions used `TMPDIR=/tmp/opencode`; `WORKFLOW_TEST_ROOT` was unset. Existing `cli/vendor/autoload.php` and installed dependencies were used. `composer` is unavailable on PATH; no installation or repair was attempted or needed to execute the checks.
- Both PHPUnit commands exited 0. Each reported one runner deprecation: `--do-not-cache-result` is deprecated in PHPUnit 13 and will be removed in 14. The requested flag was retained; this is not a failing assertion or an omitted test.

```sh
TMPDIR=/tmp/opencode php cli/vendor/bin/phpunit --do-not-cache-result --configuration cli/phpunit.xml --filter 'CoordinationProjectionTest|OwnedPathTest'
# 111 tests, 1,208 assertions; exit 0; one runner deprecation.

TMPDIR=/tmp/opencode php cli/vendor/bin/phpunit --do-not-cache-result --configuration cli/phpunit.xml --display-phpunit-deprecations
# 693 tests, 5,372 assertions; exit 0; one runner deprecation.

TMPDIR=/tmp/opencode cli/bin/workflow validate --json
# {"ok": true, "errors": [], "warnings": []}; exit 0.
```

`php -l` also passed for all five frozen target files. No dependency on Composer being executable was inferred from these results.

### Per-acceptance results

All rows use the environment above. The temporary-project checks invoked the real absolute `cli/bin/workflow` executable with `--project <temporary-project> --no-ansi`, not only the in-process Console application. Fixtures were raw temporary FrontMatter records with matching approval hashes, isolated from write/lifecycle policies; no repository records were used as mutable fixtures.

| AC | Observed evidence and method | Result | Limits |
| --- | --- | --- | --- |
| 1 | A 120-item fixture exceeded every existing text cap. Default text showed drafts 5/17, active 10/16, ready 5/29, waiting 5/18 and decisions 5/35, with exact remaining counts and no terminal sections. The committed pre-change StatusCommand was loaded into a separate PHP process without modifying source files: both default text and default JSON were byte-for-byte identical to the current command on the same fixture. Default JSON retained its existing uncapped working-set arrays and exact existing keys. | pass | Default JSON is intentionally **not capped like text**; the scope explicitly excludes changing existing default JSON behavior. Comparison used undecorated CLI output. |
| 2 | `status --all` text returned exact expected ID lists for drafts 17, active 16, ready 29, waiting 18, decisions 35, done 19 and cancelled 21. Every section total matched; no `… and` truncation appeared. Footer: `80 open · 19 done · 21 cancelled`. The decision fixture included draft, active, ready and waiting items; a cancelled needs-decision receipt did not leak into the open decision group. | pass | Finite above-cap fixture, not a performance claim for arbitrary project size. Groups can intentionally contain the same open item in both its work-state group and decisions. |
| 3 | `status --all --json` returned every expected ID in all seven groups; its only additional top-level keys versus default JSON were `done` and `cancelled`. Every existing default key/value was unchanged. A separate empty project returned empty terminal arrays only with `--all`. | pass | Machine-readable IDs, keys and values checked; no new schema beyond the requested terminal groups assumed. |
| 4 | 44 independently selected path pairs passed symmetric pure comparisons and actual ready/validate/status checks with the other owner first active and then concurrent-ready: 88 isolated pair fixtures. Slash/backslash aliases, repeated separators, dot components and repeated safe parents matched nonexistent future paths. For every fixture, ready overlap IDs agreed with validate's parallel-overlap warning and status's ready projection. | pass | Lexical comparison only. Three additional whitespace cases were pure-only because inherited Item string-list loading trims list entries. |
| 5 | The pair fixtures exercised `.`, `/`, `\`, `//./`, `a/..`, `a/b/../..` and `././`: each owned a future descendant, none was equivalent to empty ownership, and none swallowed unresolved above-root paths. Empty/empty and empty/descendant did not overlap. `future/lib` versus `future/library`, extension boundaries and normalized component-prefix boundaries did not overlap in pure checks or either CLI projection. | pass | Leading slash means a project-root ownership alias here, not operating-system root identity. Case remains literal. |
| 6 | Before/after SHA-256 inventories of every temporary fixture's complete directory/file tree were equal after reads; alias spellings remained unchanged in stored item bytes and projected owns values. Eight independent dependency fixtures confirmed direct/transitive suppression, dependency waiting, completed/cancelled exclusion, unordered active-active/active-ready conflicts and concurrent-ready conflicts. Ownership warnings did not remove eligible items from ready. | pass | Existing empty-entry filtering and outer-whitespace trimming on load are unchanged; byte preservation does not claim new lossless loading semantics. No supported-write policy or new ownership gate was required by this item. |

The independent Python driver completed **1,735 explicit checks**, including subprocess return/stderr checks. Scratch fixtures and machine-readable evidence remain at `/tmp/opencode/t016-independent-cf0zz7w9`; these are temporary diagnostic material, not additional repository deliverables. This report retains the acceptance evidence independently of their retention.

### Independent fixture detail

Status IDs and expected memberships were derived before invoking the commands:

| Group | IDs | Count |
| --- | --- | --- |
| drafts | T-001–T-017 | 17 |
| active | T-101–T-116 | 16 |
| ready | T-201–T-229 | 29 |
| waiting | T-301–T-318, each depending on active T-101 | 18 |
| decisions | T-001–T-008, T-101–T-111, T-201–T-209, T-301–T-307 | 35 |
| done | T-401–T-419 | 19 |
| cancelled | T-501–T-521 | 21 |

As an additional bounded-default check, actual `ready` text showed 20 rows by default and 29 with `--all`; ready JSON remained 29 rows with either flag setting.

The independent path table below shows the 23 non-root pairs. Each was checked in both argument orders, then through real CLI reads against an active owner and against another ready owner. Every positive warning retained the left owner's original spelling. Future owned paths were explicitly confirmed absent.

| Left literal | Right literal | Expected overlap |
| --- | --- | --- |
| `future/lib` | `future/lib/module/new.php` | yes |
| `/future//lib/./module/new.php` | `future/lib/module/new.php` | yes |
| `.\future\lib\module\new.php` | `future/lib/module` | yes |
| `future\lib//module/./new.php` | `./future/lib/module/new.php` | yes |
| `future/a/b/c/../../../lib/module` | `future/lib/module/new.php` | yes |
| `future/a/../lib/b/../module` | `future/lib/module/new.php` | yes |
| `a/b/../../future/lib` | `future/lib/module` | yes |
| `future/lib` | `future/library/new.php` | no |
| `future/a.php` | `future/a.php.bak` | no |
| `future/a/../lib` | `future/library` | no |
| `future/Lib` | `future/lib` | no |
| `future/źródła` | `./future/źródła/new.php` | yes |
| `future/new dir` | `future/new dir/new.php` | yes |
| empty string | empty string | no |
| empty string | `future/lib/new.php` | no |
| empty string | `.` | no |
| `future/*` | `future/lib` | no |
| `future/*` | `future/*/new.php` | yes |
| `future/?.php` | `future/a.php` | no |
| `future/[ab]` | `future/a` | no |
| `a/../../future` | `future` | no |
| `../outside` | `../outside/new.php` | yes |
| `...` | `future` | no |

The other 21 pairs crossed each of the seven root spellings listed under AC5 with `future/lib/new.php` (yes), empty string (no) and `../outside` (no). Three additional pure symmetric checks distinguished leading/trailing literal spaces and accepted a descendant beneath a component containing a trailing space; those were not represented as unchanged CLI load semantics.

Dependency fixtures all passed the following independently chosen expectations. Each also checked status's ready projection and complete tree-byte preservation:

| Scenario | Expected and observed ready projection | Expected and observed validate overlaps |
| --- | --- | --- |
| T-002 active depends directly on todo T-001; equivalent ownership | T-001 ready, no overlap | none |
| Active T-003 depends on draft T-002, which depends on todo T-001; equivalent ownership | T-001 ready, no overlap | none |
| Todo T-001 depends on active T-002 | no ready items | none |
| Todo T-002 depends on todo T-001 | only T-001 ready, no overlap | none |
| Done T-001 owns root; T-002 depends on it; cancelled T-003 also owns root | only T-002 ready, no overlap | none |
| Ready T-001 and unrelated active T-002/T-003 share a path | T-001 overlaps T-002 and T-003 | all three unordered pairs |
| Ready T-001/T-002 share a path; ready T-003 owns component-prefix sibling | reciprocal T-001/T-002 overlap; T-003 clear | only T-001/T-002 |
| Active T-002 depends on unrelated draft T-003, sharing ownership with ready T-001 | T-001 overlaps T-002 | only T-001/T-002 |

For those fixtures the equivalent ownership spellings were `./future/x/y/../../shared` and `future/shared/new.php`; the sibling was `future/sharedish`.

### Design inspection and deliberate exclusions

- StatusCommand uses nullable all-mode limits for every existing bounded section and appends complete terminal JSON groups only when requested (`cli/src/Console/Command/StatusCommand.php:36–38,64–89,97–160`).
- ItemValidator's single `pathsOverlap` implementation handles lexical components without filesystem calls and distinguishes absent ownership from root (`cli/src/Validate/ItemValidator.php:357–399`). Validator ownership warnings call that implementation after dependency ordering (`:275–301,342–355`).
- ProjectView calls the same dependency-order predicate and comparator for active and concurrent-ready paths (`cli/src/Read/ProjectView.php:80–107,115–146`). No second path normalization implementation or new readiness rejection was found.
- A real temporary file and symlink deliberately demonstrated the limit: `actual/new.php` and `alias/new.php` resolved to the same file but did **not** overlap lexically; `alias/../actual/new.php` did overlap `actual/new.php` lexically. Literal glob characters were not expanded. This confirms the requested lexical boundary, not a realpath/symlink/physical-identity guarantee.
- No minimum-version PHP 8.4.1 run, other OS run, clean dependency installation, arbitrary-scale performance test or decorated-output snapshot was performed. Those are not claimed. No acceptance criterion was left unrun on the available runtime.

### Frozen target identity

The five target hashes were taken before implementation inspection and remained identical at completion:

| File | SHA-256 |
| --- | --- |
| `cli/src/Console/Command/StatusCommand.php` | `483d0d8d2d5a344e8172d3d8f2c8c08beab8126b76e6551f3eab08a4506f3e1c` |
| `cli/src/Validate/ItemValidator.php` | `f8bad7986e88682a46a51ab5f7b06d1617a0dd461cd78f5fc8ee747ee48fa153` |
| `cli/src/Read/ProjectView.php` | `59e99f9c82b71c2ad0846b92a25609f3626767fad6435499eb88226d00a40af6` |
| `cli/tests/Console/CoordinationProjectionTest.php` | `7f65f9e5ac328d61a7f00e1b3efaabe8e970dd0082d890c66dc000018c99e984` |
| `cli/tests/Validate/OwnedPathTest.php` | `b8acc0b2d449d042180a5b056c920df7c5c35b406cafb253ee44d29e61ef818d` |

A broader 66-file manifest covered every PHP file under `cli/src` and `cli/tests`, plus `cli/bin/workflow`, `cli/composer.json`, `cli/composer.lock` and `cli/phpunit.xml`. It was captured before independent CLI probes and compared after the full suite: identical file set and SHA-256 values. Both `freeze-before.json` and `freeze-after.json` have SHA-256 `d7454e74e61dd7792ad6d717b4b3a13dbd1c98c93b7be2ad22d85f032d5f549a` in the scratch directory. Independent `results.json` has SHA-256 `fe778d0c0d026841c8126fd611d5430bb5ce871e14cacc4371863815d52320c7`.

No changing-code target or acceptance failure was observed. This verifier's only repository write is `.workflow/items/T-016/outputs/verification.md`; recording the verifier receipt and closing T-016 remain the orchestrator's actions.
