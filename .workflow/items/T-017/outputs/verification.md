# T-017 — independent full verification

**Verdict: PASS. All six acceptance criteria and the item Design passed.** This is full verification of the named target, not a targeted recheck or an epic-wide sign-off. No implementation changes or repairs were made.

## Authority and frozen target

The first command was `cli/bin/workflow brief T-017 --for verifier`. Expectations were derived from its acceptance criteria and Design, without reading producer conclusions. Root `AGENTS.md`, `verify-outcome`, and the CLI instructions were loaded. Source and executable tests were then examined independently.

Repository: `/var/home/adam/Projects/general-agentic-workflow`; base HEAD `3ec52c68cd683cee8c7a5ca4efd74dbdb769ec3c`. The working tree contains implementation work, so these SHA-256 values—not HEAD alone—identify the verified target. Initial and final checks agreed:

| Frozen artifact | SHA-256 |
| --- | --- |
| `cli/src/Console/Command/PlanCommand.php` | `14fc694814559d7db81720ee5df6a4083ab3c1987290d7967ef683ee4f1605a6` |
| `cli/src/Console/Command/DesignCommand.php` | `9209ad928b80ee0dd8e22d0140daa85a2ea1d9910b1cd7c2a7228f37797e6bac` |
| `cli/tests/Console/PlanDesignStateTest.php` | `fa20a6e82dbac76e23b30bc5c5739b7ac1604877a6cf8addb8767df9dc30892b` |

Shared store, lifecycle, board, and CLI sources were read only to trace integration; they are not additional frozen deliverables for this receipt.

## Environment and methods

**E1:** 2026-10-21 UTC; Linux `7.2.5-200.fc44.x86_64`, glibc 2.43; unprivileged UID 1000; PHP CLI 8.5.10; PHPUnit 13.4.3; installed Symfony console/filesystem/lock/yaml v8.1.9; Python 3.14.8 standard library. No tools or dependencies were installed.

All executable tests used `TMPDIR=/tmp/opencode`; PHP confirmed `sys_get_temp_dir()` resolved there. Independent fixtures, scripts and raw command logs are retained under `/tmp/opencode/t017-independent-full.e2Z71t/`.

Independent checks invoked the actual `cli/bin/workflow` executable in fresh temporary projects, with explicit `--project`, `--no-ansi`, and `--no-interaction`. State/body were inspected through `show --json`, with independent section extraction and comparison of approval and every scope field. Rejection checks compared the entire `.workflow` tree's file SHA-256, size, modification time, permissions and directory membership, excluding ephemeral `.locks`. Board checks replaced the temporary board with a sentinel or removed it, then checked regeneration and current metadata.

Independent passing runs: **338 scenario cases, 1,212 CLI processes**:

| Check group | Cases |
| --- | ---: |
| Content-only compatibility | 80 |
| Explicit phase, no status inference | 80 |
| Explicit start, combinations, manual corrections | 48 |
| Invalid input and unavailable sources | 54 |
| Argument/content boundaries | 16 |
| Shared verification invalidation and absence of new gates | 4 |
| Completed history, including controls | 52 |
| Current/stale approval and board controls | 4 |

Scripts and logs: `verify_cli.py`, `commands.jsonl`, `summary.json`, `approval_control_v2.py`, `approval-v2-commands.jsonl`, and `approval-v2-summary.json` in that temporary directory. Reproduction requires fresh fixture directories because runs deliberately preserve their evidence.

## Per-criterion results

| Criterion / independently derived expectation | Observed result and method | Environment / limits |
| --- | --- | --- |
| **AC1 — PASS:** without flags, replace only the requested section; retain status and phase. | 80 real-CLI cases covered both commands, text/file sources, draft/todo/in-progress/cancelled, and all five existing phases. The requested content matched trimmed input; the other section, state and approval remained unchanged. Late-phase plan edits did not rewind phase. | E1. Done-item material edits are intentionally tested as refusals under AC6. |
| **AC2 — PASS:** valid explicit phase and content persist together; phase never infers status or enforces ordering. | 80 cases covered every phase for both commands/sources and all four non-done statuses, starting at verify. Requested phase/content persisted; status remained exactly the prior value. Source inspection found one `store->mutate` callback containing content and optional state changes, after source/phase validation. Rejections under AC4/AC6 demonstrated no partial persistence. | E1. Atomicity was checked by integration failures and the persistence path, not process-kill or concurrent-reader fault injection. |
| **AC3 — PASS:** only explicit `--start` requests in-progress; `set` remains usable for corrections. | 48 cases covered start alone, phase+start, reversed flag order, backward phase selection, both sources/commands and all four non-done starting statuses. Start alone retained verify; combinations selected exactly the explicit phase. Each case then used existing `set status=todo phase=explore` successfully without losing content or approval. AC1/AC2 establish the no-start controls. | E1. Cancelled items followed the existing permissive non-done policy; done reopening was separately refused. |
| **AC4 — PASS:** invalid source, phase or unsupported input changes neither content/state nor board. | 54 rejection cases preserved complete snapshots, including item/board modification times. Cases included absent/both sources, empty file path, missing/directory/dangling/unreadable files, invalid/status-shaped/empty/whitespace phases, missing option values, unsupported status/close/json flags, attached and separate start values, extra arguments, option terminators, invalid final repeated phase and missing item IDs. The mode-000 source exercised actual `file_get_contents` failure after `is_file`, returning exit 2. | E1; permissions were effective at UID 1000. PHP emitted a permission warning plus the command's readable refusal for unreadable files; no record/board mutation occurred. Lock internals are excluded from snapshots. |
| **AC5 — PASS:** successful writes refresh the derived board and do not invalidate or manufacture scope approval. | Successful compatibility/flag cases replaced stale-board sentinels and rendered the selected phase; start cases rendered the in-progress count. Approval objects and scope fields remained equal. Four supplemental controls recreated missing boards, retained current approval, made approval stale by an explicit title change, and proved subsequent plan/design writes kept it stale without reapproval. The resulting board placed the changed title/phase in the correct status column. | E1, writable temporary filesystems. Board failure is inherited best-effort warning behavior in `BaseCommand`; an unwritable destination was not fault-injected. |
| **AC6 — PASS:** completed material edits and start reopening use shared history protection, instruct explicit follow-up, and leave no partial changes. | 48 refusal cases covered both commands/sources, changed content alone/with phase/with start/with both flags, and unchanged content with start, for both verifier-completed and deliberately force-completed items. Every refusal included “create a new follow-up item” and “explicitly approve its scope”; tree snapshots and item membership were unchanged. Four positive controls retained done/verification/approval while changing only phase with identical content, consistent with the existing nonmaterial-correction policy. | E1. No follow-up item was auto-created. Done-history policy was traced through the same store/lifecycle path rather than a command-local duplicate. |

## Design and boundary checks

- Both commands validate exactly one content source, successful reading, and phase before their single mutation. The callback changes status only inside the explicit start branch; board refresh follows successful persistence. No generic framework, readiness gate, automatic phase sequence or result/close combination was introduced in the frozen commands.
- Two actual open-item content changes after verifier receipts cleared `verified_at`, preserved approval and caused a subsequent unverified `close` to fail. Two unapproved dependent items could explicitly start at verify with only their requested section present: no approval/readiness/missing-section gate was added.
- Sixteen boundary cases confirmed empty text/whitespace/file content is valid existing section replacement, option-looking text remains literal with attached `--text=`, option-like relative filenames work with `--file=`, and options may precede the ID. Uppercase phases normalize. Repeated scalar phase options use Symfony's last-value semantics; an invalid final value is rejected. Repeated boolean start is accepted. No extra duplicate-option gate was assumed.
- A supplemental harness's first attempt expected lowercase `approval: stale`; the CLI correctly rendered `approval: STALE`, so that assertion stopped the harness, not the product. Its script, ten successful command outputs and fixtures remain preserved as `approval_control.py` / `approval-commands.jsonl`. A separately named v2 used the observed label and fresh fixtures; all four controls passed. Frozen sources were not altered.

## Executed regression surface

From the repository root:

```sh
TMPDIR=/tmp/opencode cli/vendor/bin/phpunit --configuration cli/phpunit.xml --do-not-cache-result cli/tests/Console/PlanDesignStateTest.php
TMPDIR=/tmp/opencode cli/vendor/bin/phpunit --configuration cli/phpunit.xml --do-not-cache-result --display-phpunit-deprecations cli/tests/Console/LifecycleCommandTest.php cli/tests/Console/CommandTest.php cli/tests/Domain/ItemLifecycleTest.php cli/tests/Store/LifecycleWriteTest.php
TMPDIR=/tmp/opencode WORKFLOW_TEST_ROOT=/tmp/opencode cli/vendor/bin/phpunit --configuration cli/phpunit.xml --do-not-cache-result --display-phpunit-deprecations
```

| Run | Result |
| --- | --- |
| Frozen focused test | Exit 0; 68 tests, 932 assertions |
| Lifecycle/command integration regression | Exit 0; 230 tests, 1,520 assertions |
| Complete currently available suite | Exit 0; 693 tests, 5,372 assertions |

Each PHPUnit invocation reported one runner deprecation: the explicitly requested `--do-not-cache-result` option will be removed in PHPUnit 14. No test failed. These runs overlap and must not be summed as unique tests. The complete-suite result is additional regression evidence, not independent acceptance of other items' unfrozen work.

## Limits and disposition

No other PHP/OS versions, process-crash durability, concurrent writers, or failing board filesystem were exercised. The three frozen hashes remained unchanged across verification; other active work was not repaired or claimed as frozen. No producer conclusions were used. The only repository file written by this verification is this report; project records, source and user files were not changed.

No product defect or approval decision remains for T-017. Record the successful independent verifier receipt, then close through the existing lifecycle.
