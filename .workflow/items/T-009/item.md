---
id: T-009
kind: task
parent: E-002
title: 'Preserve literal and multiline front matter'
status: done
phase: verify
priority: 2
depends_on: []
owns:
  - cli/src/Domain/FrontMatter.php
  - cli/tests/Domain/FrontMatterTest.php
artifacts:
  - cli/src/Domain/FrontMatter.php
  - cli/tests/Domain/FrontMatterTest.php
  - .workflow/items/T-009/outputs/verification.md
approval:
  at: '2026-10-21T04:51:17+00:00'
  by: user
  scope_hash: 3da5c39e21ba6dd473133e0079d4c7fe8288875b4596a3a091603b229b97acd9
last_result:
  at: '2026-10-21T04:59:26+00:00'
  role: verifier
  status: done
  finding: 'All five criteria passed independent full verification: 17 Domain tests and 879 additional checks. Frozen hashes remained unchanged with only the requested PHPUnit option deprecation.'
  decision: null
verified_at: '2026-10-21T04:59:26+00:00'
created: '2026-10-20T19:13:13+00:00'
updated: '2026-10-21T04:59:26+00:00'
outcome: 'Ordinary literal and multiline YAML values survive rendering and parsing without silent mutation or broken delimiters.'
scope:
  in:
    - 'Replace post-dump empty-array rewriting with the Symfony empty-sequence flag'
    - 'Preserve YAML boundary and scalar terminal newlines while keeping closing delimiters separate'
    - 'Add focused codec regressions for ordinary strings and nested data'
  out:
    - 'Changes to typed-item list trimming or Markdown-body normalization'
    - 'Record migrations or edits to completed history'
acceptance:
  - 'Scalar text containing brace-shaped JSON syntax remains unchanged after render and parse'
  - 'Final multiline scalars with zero, one or multiple trailing newlines retain their exact YAML values and parseable closing boundaries'
  - 'Unicode, colon text, delimiter-like content, nested unknown data and empty sequences round-trip correctly'
  - 'Empty arrays render as sequences without a global text replacement'
  - 'Existing body-normalization behavior and malformed-YAML rejection remain covered and passing'
---

## Plan

Question: how can literal YAML survive the existing front-matter boundary without schema changes?
Dimensions: scalar preservation, delimiter framing and empty sequences.
Sources: .workflow/items/E-002/outputs/record-lifecycle-discovery.md and FrontMatter with its current tests.
Stop: each reported corruption has a focused failing regression and a minimal codec correction.
Acceptance: run codec regressions and existing domain tests, distinguishing codec fidelity from typed-list normalization.

## Design

Files: correct FrontMatter and extend FrontMatterTest only.
Decision: use Symfony's empty-sequence flag, preserve the parse boundary newline and ensure a separate delimiter without trimming scalar data.
Checks: literal text, final block scalars with terminal-newline variants, Unicode, nested values, empty arrays and invalid YAML.
Risks: preserve existing body/list normalization and do not rewrite existing records.
Upstream: .workflow/items/E-002/outputs/record-lifecycle-discovery.md and the parent Design are sufficient evidence, with no new broad exploration.

## Progress

- 2026-10-21T04:53:57+00:00 · worker · done
  Finding: Implemented Symfony empty-sequence dumping and lossless multiline YAML boundaries without changing body normalization. Focused domain tests passed: 17 tests and 111 assertions with only the deprecated test-option warning.
  Artifacts: cli/src/Domain/FrontMatter.php, cli/tests/Domain/FrontMatterTest.php
  Decision: none
  Next: Independently verify the frozen owned files.

- 2026-10-21T04:59:26+00:00 · verifier · done
  Finding: All five criteria passed independent full verification: 17 Domain tests and 879 additional checks. Frozen hashes remained unchanged with only the requested PHPUnit option deprecation.
  Artifacts: .workflow/items/T-009/outputs/verification.md
  Decision: none
  Next: Close T-009 and start dependency-ready work.

- 2026-10-21T04:59:26+00:00 · closed as done
