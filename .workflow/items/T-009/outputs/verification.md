# T-009 independent full verification

## Expectations fixed before target inspection

Source of expectations: `cli/bin/workflow brief T-009 --for verifier`, acceptance criteria and Design. This is a full verification of the two named frozen targets, not a targeted recheck. No producer conclusions or receipts were read.

1. Literal scalar text, including exactly `'{}'` and embedded JSON-shaped brace pairs, must return byte-for-byte unchanged after render/parse.
2. A final multiline scalar with zero, one, or multiple terminal LF characters must preserve its exact value. YAML closing front-matter markers must remain separate and parseable; boundary handling must not strip scalar data.
3. Unicode, colon-containing text, delimiter-like content, nested unknown mappings/lists, and empty sequences must survive the codec unchanged.
4. Empty PHP arrays must be emitted as YAML sequences using Symfony's empty-sequence dump flag, without a global textual `{}` rewrite that could mutate scalar text.
5. Existing Markdown body normalization is intentionally unchanged, not made byte-preserving. Existing malformed-YAML rejection must remain covered and pass.

Design checks: changes stay within the codec/test targets; no typed-item list normalization, body-normalization redesign, migrations, new workflow schema, implementation repairs, or unrelated installer/runtime verification.

## Frozen target evidence

Initial SHA-256, captured before source inspection:

```text
ceb87f671b8a881e0721723e8da1ffa21f00cef355619e466960938cf4a4b7ef  cli/src/Domain/FrontMatter.php
816f97aa60a59505a21bc081e8bd4827244be5e78f36efd66746d31526925738  cli/tests/Domain/FrontMatterTest.php
```

Final SHA-256 after all executable checks (2026-10-21T04:58:58Z): identical to both initial hashes above. The targets stayed unchanged across the verification window. Hashes are evidence in this local artifact only, not new workflow metadata.

## Outcome and environment

**PASS: all five acceptance criteria and the supplied Design.** No blocker, failed check, repair, or missing prerequisite. This conclusion applies only to the two frozen targets and the stated environment; it is not epic-wide verification.

Environment E1: Linux, project `/var/home/adam/Projects/general-agentic-workflow`, PHP CLI **8.5.10**, Symfony YAML **v8.1.9** at `6107ee26a25215c3df3af0a3315e56bb4fd0b7db`, PHPUnit **13.4.3**. Tests used the existing `cli/vendor/autoload.php` and `cli/phpunit.xml`; `dom`, `libxml`, `mbstring`, `xml`, `xmlwriter`, and `iconv` were loaded. Installed YAML/PHPUnit versions were checked again at the end and unchanged. No dependencies were installed or repaired.

Only this verification artifact was authored. No producer conclusion/receipt, worker/default/JSON brief, unrelated implementation, historical record, or `docs/handoff-compare.md` was read or modified. The committed version of the same codec file was read solely to compare existing body normalization.

## Executable evidence

All commands below exited **0**. PHPUnit commands ran from `cli/`; syntax checks and independent probes ran from the project root.

| Command | Observed |
| --- | --- |
| `php -l cli/src/Domain/FrontMatter.php` | No syntax errors |
| `php -l cli/tests/Domain/FrontMatterTest.php` | No syntax errors |
| `TMPDIR=/tmp/opencode vendor/bin/phpunit --do-not-cache-result --testdox tests/Domain/FrontMatterTest.php` | 13 tests, 99 assertions, no failures/errors, one runner deprecation |
| `TMPDIR=/tmp/opencode vendor/bin/phpunit --do-not-cache-result tests/Domain` | 17 tests, 111 assertions, no failures/errors, one runner deprecation |
| `TMPDIR=/tmp/opencode vendor/bin/phpunit --do-not-cache-result --display-phpunit-deprecations tests/Domain/FrontMatterTest.php` | 13 tests, 99 assertions; warning identified precisely below |
| `TMPDIR=/tmp/opencode php` with an independent stdin script | **879 strict checks, zero failures**, using actual FrontMatter and Symfony classes, not mocks or PHPUnit assertions |

The single PHPUnit runner deprecation is: `The "--do-not-cache-result" CLI option is deprecated and will be removed in PHPUnit 14. Use "--do-not-record-test-run-history" instead.` The requested option was retained. This is not a failed application test.

The independent stdin script used explicit `!==` comparisons, collected failures, and exited nonzero on any mismatch; it did not depend on PHP's configurable `assert()`. Each round-trip vector checked data identity, body identity, exactly one unindented closing boundary (with a delimiter-free probe body), and second-round-trip stability. Extra direct-parse and body/error vectors had explicit expected values.

## Per-criterion assessment

### AC1 — literal brace-shaped text: PASS

- **Expected:** ordinary scalar strings, especially exactly `'{}'`, are never rewritten as sequences or otherwise changed.
- **Observed:** frozen tests `testBraceShapedLiteralTextIsNotRewritten` and nested-data tests passed. Independent probes passed **148 checks**: 12 literal values in top-level, nested-map, and nested-sequence positions, plus brace-containing mapping keys. Values included exactly `'{}'`, `'{ }'`, `'JSON: {}'`, `'JSON: { }'`, `'{"nested": {}, "list": []}'`, `'prefix: {} suffix'`, and multiline brace text. Type-sensitive literals such as `'001'`, `'true'`, and `'null'` stayed strings.
- **Method/environment:** E1; strict original-array equality after real render/parse and a second round trip, plus source inspection.
- **Limits:** finite representative string corpus, not every possible PHP string. No acceptance case left unrun.

### AC2 — final multiline scalar and delimiter boundaries: PASS

- **Expected:** zero, one, or multiple terminal LF characters belong to the scalar according to YAML chomping; the closing marker must stay separate. Final blank-only scalars must also survive.
- **Observed:** all frozen final-value and raw-block-boundary tests passed. Independent probes passed **336 checks**. Render/parse covered final scalars with **0, 1, 2, 3, and 5** terminal LFs, leading blank lines, significant content spaces, blank-only values (`"\n"`, `"\n\n\n"`), and delimiter-looking lines. Each appeared as a top-level final value, final nested-map value, or final sequence element. Direct raw parsing covered literal and folded blocks (`|-`, `|`, `|+`, `>-`, `>`, `>+`) with explicit expected values, including keep-chomp tails of one, two, or four LFs, LF/CRLF source forms, and closing markers followed by body text, a newline only, or EOF with no final newline.
- **Method/environment:** E1; original scalar byte equality, strict body checks, standalone closing-boundary checks, and raw YAML inputs independent of the renderer. Source `FrontMatter.php:32-34` includes the boundary LF in YAML; lines 58-62 append only a missing boundary rather than trimming the dump.
- **Limits:** no claim about arbitrary malformed block indicators or every YAML feature. All requested terminal-newline and closing-boundary variants were run.

### AC3 — Unicode, colon/delimiter text, nested unknown data and empty sequences: PASS

- **Expected:** strings and nested heterogeneous data retain their values/types without delimiter confusion or a schema migration.
- **Observed:** the frozen nested/unknown-value tests passed. Independent probes passed **332 checks**: a mixed nested structure with Unicode keys/values, emoji, colon text, `'---'`/`'...'`, embedded delimiter lines, empty arrays at several depths, booleans/null/integers/floats/string-shaped numbers, backslashes, tabs, and CRLF-bearing string data; an 11-level unknown structure beyond dump's inline threshold; and **81** generated multiline values (three content tokens in three positions, with 0/1/3 terminal LFs).
- **Method/environment:** E1; strict nested-array equality, body identity, independent delimiter checks, second round trips, and frozen regression tests. A Markdown body containing its own `---` also passed in the body matrix.
- **Limits:** no exhaustive arbitrary-object, custom-tag, or unsupported YAML-type coverage; those are not this ordinary-data contract. No acceptance case left unrun.

### AC4 — empty sequences without a global replacement: PASS

- **Expected:** empty PHP arrays emit as YAML `[]`, including the root case, without replacing brace text in scalar data.
- **Observed:** frozen empty-sequence/root-delimiter regressions passed. Independent probes passed **21 checks**, including exact `"---\n[]\n---\n\nbody\n"` rendering, nested empty mappings-as-sequences, sequences of empty arrays, and mixed literal `'{}'` plus empty-array data. Codec output matched the real Symfony dump with `DUMP_EMPTY_ARRAY_AS_SEQUENCE` plus the explicitly required boundary newline. Source line 58 uses that flag; the entire renderer contains no global dump-text replacement.
- **Method/environment:** E1; exact rendered-string comparisons, strict round trips, direct Symfony comparison, and full source inspection of the short codec.
- **Limits:** PHP cannot distinguish an empty map from an empty list here; rendering empty arrays as sequences is the expressly approved convention, not a new schema claim. No acceptance case left unrun.

### AC5 — existing body normalization and malformed-input rejection: PASS

- **Expected:** preserve existing CRLF-to-LF handling, leading blank-line removal, render-time trailing-whitespace normalization, parse-time preservation of trailing body whitespace, and malformed-YAML errors.
- **Observed:** frozen body-normalization tests and invalid-YAML/unclosed-sequence tests passed. Independent probes passed **42 checks**: ten render-body vectors checked exact rendered bytes and parsed body; six direct-parse vectors checked only the established parse normalization; eight invalid-input vectors checked `InvalidItemException` and successful parsing immediately afterward. Vectors covered empty/whitespace-only bodies, CRLF, leading blank lines versus significant leading spaces/tabs, internal Markdown hard-break spaces, trailing spaces/newlines/NUL/vertical-tab, an internal bare CR, and Markdown delimiter lines. Rejections covered missing opening/closing markers, a non-container YAML root, unclosed flow sequence/map, bad colon syntax, duplicate keys, and inconsistent indentation.
- **Method/environment:** E1; explicit body expectations rather than whole-body byte preservation. Additionally, `git show HEAD:cli/src/Domain/FrontMatter.php` at commit `3ec52c68cd683cee8c7a5ca4efd74dbdb769ec3c` confirmed the body extraction and normalization expressions are unchanged: `$body = substr($raw, $end + 5)`, parse's `ltrim($body, "\n")`, render's CRLF replacement/leading-newline removal, and final `rtrim($body)."\n"`. The affected Domain suite also passed.
- **Limits:** body normalization intentionally remains lossy as before. This is not new fidelity for trailing Markdown whitespace, typed-item list trimming, or schema validation. No acceptance case left unrun.

## Small independently reproducible core probe

This subset reproduces the central literal, terminal-newline, raw-boundary and body expectations using the installed dependencies; it is not the full 879-check corpus. The exact block below was also extracted from this artifact and executed with PHP in E1: exit 0, `core checks passed`. Run at the project root:

```sh
TMPDIR=/tmp/opencode php <<'PHP'
<?php
require 'cli/vendor/autoload.php';
use Workflow\Domain\FrontMatter;
function same($expected, $actual): void {
    if ($expected !== $actual) {
        throw new RuntimeException(var_export([$expected, $actual], true));
    }
}
foreach (['{}', 'JSON: {}', '{"nested": {}}'] as $value) {
    same(['value' => $value], FrontMatter::parse(FrontMatter::render(['value' => $value], ''))['data']);
}
foreach ([0, 1, 2, 3, 5] as $count) {
    $value = "first\nsecond".str_repeat("\n", $count);
    foreach ([['value' => $value], ['last' => ['text' => $value]], ['last' => [$value]]] as $data) {
        same($data, FrontMatter::parse(FrontMatter::render($data, 'body'))['data']);
    }
}
foreach ([['-', 1, "first\nsecond"], ['', 1, "first\nsecond\n"], ['+', 3, "first\nsecond\n\n\n"]] as [$chomp, $lf, $value]) {
    foreach (["---\n\nbody\n", "---\n", '---'] as $closing) {
        $raw = "---\nvalue: |".$chomp."\n  first\n  second".str_repeat("\n", $lf).$closing;
        same(['value' => $value], FrontMatter::parse($raw)['data']);
    }
}
same("---\n[]\n---\n\nbody\n", FrontMatter::render([], 'body'));
same("keep  \nlast\n", FrontMatter::parse(FrontMatter::render(['title' => 'Body'], "\r\nkeep  \r\nlast  \r\n\r\n"))['body']);
same("body  \n\n", FrontMatter::parse("---\ntitle: Body\n---\n\nbody  \n\n")['body']);
echo "core checks passed\n";
PHP
```

## Scope and remaining limits

The supplied Design is implemented coherently: Symfony's sequence flag, inclusion of the YAML boundary LF, and a separate closing delimiter without YAML trimming; focused codec regressions cover the described risks. No design gap was found. Evidence did not require reading upstream producer reports.

No criterion is failed, blocked, or unrun in E1. Checks of other PHP/Symfony versions, minimum-runtime provisioning, installer behavior, other active workers' deliverables, the full repository suite, and epic integration were **not run** and are outside this item verification. No workflow records or new schema fields were written. The orchestrator should record this receipt as verifier evidence before considering closure.
