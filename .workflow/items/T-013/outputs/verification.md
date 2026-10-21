# T-013 independent full verification

**Result: PASS — all five acceptance criteria and the recorded Design verified.**
This is full verification of T-013's four frozen deliverables, not a full-repository
integration run or a recheck of selected fixes. No repairs were made.

## Authority, independence and frozen target

First command: `cli/bin/workflow brief T-013 --for verifier`. Expectations below
were derived from that packet, its Design and root `AGENTS.md`; `verify-outcome`
was loaded. No real-item producer receipt, worker/JSON brief, or producer report
was read. The first independent subprocess probe completed before the two new
test files were read; their passing tests were supplementary evidence.

Initial and final SHA-256 snapshots matched. Final frozen-target check:
**2026-10-21 05:13:37 UTC**. Git HEAD was
`3ec52c68cd683cee8c7a5ca4efd74dbdb769ec3c`; the two source files were already
modified and the two tests untracked. These hashes, not HEAD alone, identify the
verified outcome:

| Frozen path | SHA-256 |
| --- | --- |
| `cli/src/Console/Command/BriefCommand.php` | `10f37056c415bd537035930c479cf0453c61ee19cf7888a564f0beab90dd2df2` |
| `cli/src/Console/Command/BaseCommand.php` | `1fad3db121953ea05d36b97c9bf3699b3332d2987fdbf4be7ebf42f17785aae0` |
| `cli/tests/Console/BriefPacketTest.php` | `ad09940bcf9fad23b13c46d4cb5f514ba552d29cda15fab63814713ddc8e03da` |
| `cli/tests/Console/JsonLiteralOutputTest.php` | `bfb24962f585b737427fc9e343c27c3f9746b92ab922abaf05100a3101e57f28` |

## Environment and executed checks

Linux x86_64; PHP CLI 8.5.10 with native mbstring and iconv; Symfony Console/Yaml
8.1.9; PHPUnit 13.4.3; Python 3.14.8. Existing dependencies only: no installation
or environment repair. All test commands used `TMPDIR=/tmp/opencode`; PHPUnit
also used `WORKFLOW_TEST_ROOT=/tmp/opencode` and `--do-not-cache-result`.

From `cli/`:

```sh
TMPDIR=/tmp/opencode WORKFLOW_TEST_ROOT=/tmp/opencode vendor/bin/phpunit --do-not-cache-result --display-phpunit-deprecations --colors=never tests/Console/BriefPacketTest.php tests/Console/JsonLiteralOutputTest.php
TMPDIR=/tmp/opencode WORKFLOW_TEST_ROOT=/tmp/opencode vendor/bin/phpunit --do-not-cache-result --filter 'testBrief|testVerifierBrief|testExplorerBrief|testJsonReadsAndGraph|testNewJsonStaysCleanWhenBoardRefreshes' tests/Console/CommandTest.php
```

- New frozen tests: **33 tests, 381 assertions, exit 0**.
- Relevant existing command regressions: **7 tests, 33 assertions, exit 0**.
- Each run reported one PHPUnit runner deprecation: `--do-not-cache-result`
  will be removed in PHPUnit 14; the suggested replacement is
  `--do-not-record-test-run-history`. This was not a test failure.
- `TMPDIR=/tmp/opencode php -l` over all four frozen files: no syntax errors.
- Independently authored subprocess probe: **82 CLI invocations, 1,218 checks,
  zero failures**. One deliberately invalid role returned the expected exit 2;
  all other invocations exited 0, with no unexpected stderr.
- Independent direct BaseCommand probe: **40 exact-decoding/control-character
  checks passed**, plus **two unsafe-writer negative controls detected**.

## Acceptance results

Environment for every criterion is the environment above. Each result combines
source inspection with actual command/output assertions, not report existence.

### 1. Verifier excludes producer receipts, progress and unknown metadata — PASS

**Expected:** neither text nor JSON exposes `last_result`, Progress or arbitrary
metadata, including hidden producer-claim sentinels.

**Observed/method:** all four roles were executed in text/JSON under both
`--ansi` and `--no-ansi`. Verifier JSON had an exact explicit field set with no
receipt/body/progress/extra fields; text had no last-result section. Independent
sentinels in scalar/list/nested unknown metadata, metadata named plan/design/
decisions/body, unknown nested scope/approval keys, Progress and unselected body
content were absent. Parent/dependency metadata, receipts and Progress also
stayed absent. Receipt head/tail/decision/next/artifact/timestamp/unknown payloads
did not leak. A full-item `show --json` positive control demonstrated that the
receipt, unknown metadata and Progress sentinels really existed in the fixture.

Source: `BriefCommand.php:58-105` constructs its allowlist; both renderers receive
that same packet. It does not serialize `Item::toArray()`.

**Limit:** this is field-based blinding, not narrative censorship; see Design.

### 2. Verifier retains every required input — PASS

**Expected:** acceptance, both scope lists, Plan, Design, parent Design,
Decisions, artifacts, dependency context and owned deliverables survive.

**Observed/method:** independent JSON assertions compared full literal values
for every listed input, plus outcome, title, ownership and upstream artifact
mapping. Text assertions found each corresponding value marker and section.
Dependency IDs and state labels included done, waiting and missing cases:
`T-051 (done)`, `T-052 (todo)`, `T-999 (missing)`. Stale approval was represented
honestly. Sparse items preserved empty collections/sections rather than invented
inputs; missing-plan/design warnings remained in text. New tests additionally
checked exact current-approval values and ordinary multi-criterion text.

Source: `BriefCommand.php:63-88,118-209,247-275`.

**Limit:** text retains the established console formatting behavior; the literal
string equality requirement applies to JSON, tested separately below.

### 3. Worker/explorer context and bounded receipts — PASS

**Expected:** appropriate role inputs remain, without serializing the entire
Item; a last-result summary cannot carry arbitrary nested/unbounded data.

**Observed/method:** worker, explorer and orchestrator received the same required
context, with explorer's output target added. Receipt JSON contained only
`role`, `status`, `finding`; text used the same normalized summary. The finding
was bounded to 100 Unicode code points, whitespace collapsed, with a Unicode
ellipsis when truncated. Independent cases exercised null/empty receipts,
invalid role/status arrays and strings, non-string findings, and findings of
99, 100, 101 and 2,500 emoji. Invalid fields became `?`/empty finding rather than
serialized payloads. Verifier packets excluded every receipt variant. Invalid
roles were rejected instead of falling back to worker context; mixed-case valid
roles normalized correctly.

Source: `BriefCommand.php:36-40,94-102,212-219`; the existing
`Text::truncate` implementation supplies the code-point bound.

**Limit:** only receipt information is bounded here; approved narrative sections
are intentionally retained in full.

### 4. Exact literal JSON under both decoration modes — PASS

**Expected:** JSON decoding recovers exact console-style tags and Unicode,
independent of decoration settings.

**Observed/method:** real CLI processes used `--ansi` and `--no-ansi`. Exact
decoded comparisons covered title, outcome, scope, acceptance, all narrative
sections, owned/artifact paths and upstream artifacts. Inputs included error,
foreground/options and hyperlink tags, Polish/CJK/emoji/combining Unicode,
quotes, backslashes, newlines, tabs and U+2028. JSON contained no injected ANSI.
Non-brief `show --json` also preserved exact values and body content.

An independent anonymous BaseCommand subclass additionally exercised scalar and
nested payloads, tagged keys, escaped/unknown tags, empty/null/boolean/integer
values, NUL, CR/LF, literal ESC content and U+2029. Every decoded value matched;
control characters remained JSON-escaped. As a failure-revealing control,
normal console-formatted JSON corrupted the same tag value in both decoration
modes, and both corruptions were detected.

Source: `BaseCommand.php:34-39` uses `OutputInterface::OUTPUT_RAW` centrally.

**Limit:** invalid UTF-8/error recovery is not an acceptance claim; it was not
treated as a literal JSON value that must decode successfully.

### 5. Repository-relative explorer target; storage intact — PASS

**Expected:** explorer targets start with `.workflow/items/` relative to the
repository; safe projection must not delete stored unknown metadata.

**Observed/method:** explorer text and JSON returned exactly
`.workflow/items/T-050/outputs/`, with explicit `--project` and from a nested
working directory using project discovery. All original fixture records were
SHA-256/byte unchanged after brief, show and invalid-role reads, including their
nested metadata and full receipts. `show --json` confirmed unknown metadata
remained accessible. A separate isolated valid item's supported priority-save
also retained unknown top-level metadata and its complete receipt.

Source: `BriefCommand.php:90-92,187-189`; packet generation performs no writes
and leaves Item persistence untouched.

**Limit:** this does not claim a new schema-preservation contract for arbitrary
mutations of nested known fields; Item persistence changes are explicitly out
of scope. The real repository's item records were never modified.

## Design, evidence and limits

The implementation follows the recorded small-seam design: one role-aware
allowlisted packet shared by text/JSON, a central raw JSON writer, no generic
redaction framework or persistence changes. Independent narrative probes placed
the same claim in required scope/acceptance/outcome/Plan/Design/Decisions and a
receipt: all six permitted narrative occurrences survived, while the receipt
and Progress remained excluded. This confirms the stated semantic-blinding
boundary rather than accidentally censoring approved inputs.

Temporary reproducibility evidence (not additional repository deliverables):

- `/tmp/opencode/t013-independent-full-20261021.py`
  (`9c2a8293cf310e30746b168bedf80ccdb228288cd3406ec692de160b8edcbfb6`)
- `/tmp/opencode/t013-independent-json-20261021.php`
  (`248f6fedef795ddc699532019823fe37125230206c644a6d9b27293cdadbab63`)
- `/tmp/opencode/t013-independent-full-spe6aiyr/summary.json`
  (`39128d7304d8286f832eed11262dcdaf72eb497799bf91fb966f22e0d033828d`),
  with per-call stdout/stderr alongside it. Verifier JSON captures
  `call-001.stdout`/`call-003.stdout` were each 2,913 bytes in undecorated/decorated
  mode respectively.

No T-013 criterion remains failed, blocked or unrun. No tools were installed,
source repaired, or real workflow records mutated. Only this repository report
was written. Full-suite integration, minimum-runtime/no-dev/bootstrap checks,
structure writes and installation safety were not rerun here; sibling verifiers
own those frozen surfaces. A later change to any frozen hash requires a fresh
check before using this receipt for closure.
