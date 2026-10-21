---
id: T-013
kind: task
parent: E-002
title: 'Make role packets safe in text and JSON'
status: done
phase: verify
priority: 2
depends_on:
  - T-009
owns:
  - cli/src/Console/Command/BriefCommand.php
  - cli/src/Console/Command/BaseCommand.php
  - cli/tests/Console/BriefPacketTest.php
  - cli/tests/Console/JsonLiteralOutputTest.php
artifacts:
  - cli/src/Console/Command/BriefCommand.php
  - cli/src/Console/Command/BaseCommand.php
  - cli/tests/Console/BriefPacketTest.php
  - cli/tests/Console/JsonLiteralOutputTest.php
  - .workflow/items/T-013/outputs/verification.md
approval:
  at: '2026-10-21T04:51:18+00:00'
  by: user
  scope_hash: 8c77e1db74d9c25e9a9bc7489624b2bec5012273d108dcc7e7c26debd4752fc2
last_result:
  at: '2026-10-21T05:24:21+00:00'
  role: verifier
  status: done
  finding: 'All five criteria passed independent full verification including 40 tests and 82 CLI probes. Frozen hashes remained unchanged with only the requested PHPUnit flag deprecation.'
  decision: null
verified_at: '2026-10-21T05:24:21+00:00'
created: '2026-10-20T19:13:13+00:00'
updated: '2026-10-21T05:24:21+00:00'
outcome: 'Verifier delegation omits producer conclusions and arbitrary metadata in both output formats while preserving required inputs and literal JSON values.'
scope:
  in:
    - 'Allowlisted role-aware BriefCommand packet shared by text and JSON'
    - 'Raw JSON output through BaseCommand'
    - 'Repository-relative explorer output targets and focused role-isolation tests'
  out:
    - 'Changes to Item persistence or arbitrary-metadata storage'
    - 'A general serialization or redaction framework'
    - 'Semantic filtering of approved narrative content'
acceptance:
  - 'Verifier text and JSON omit last_result, progress and unknown metadata, including producer-claim sentinel values'
  - 'Verifier packets retain acceptance, scope, Plan, Design, parent design, decisions, artifacts, dependencies and owned deliverable paths'
  - 'Worker and explorer packets retain appropriate role inputs and bounded last-result information without serializing the entire Item'
  - 'Console-style tags and Unicode in JSON string values survive exact decoding with decorated and undecorated output'
  - 'Explorer output targets use the repository-relative .workflow/items prefix and no unknown metadata is silently deleted from stored records'
---

## Plan

Question: which role inputs can be projected safely without exposing producer claims?
Dimensions: field allowlisting, format parity and literal JSON output.
Sources: .workflow/items/E-002/outputs/delegation-output-discovery.md, BriefCommand, BaseCommand and role contracts.
Stop: all roles have explicit fields and sentinel regressions in both formats.
Acceptance: decode exact JSON values and compare verifier exclusion with retained design, acceptance and artifact inputs.

## Design

Files: keep a small allowlisted packet inside BriefCommand, emit raw JSON centrally in BaseCommand and add focused packet tests.
Decision: share role-aware data between formats, omit verifier receipt/progress/unknown metadata and leave Item storage unchanged.
Checks: role sentinels, text/JSON parity, decorated tags, Unicode and repository-relative explorer output paths.
Risks: field-level blinding does not censor conclusions manually placed in approved design or decisions.
Upstream: .workflow/items/E-002/outputs/delegation-output-discovery.md and parent Design define required fields and integration.

## Progress

- 2026-10-21T05:07:47+00:00 · worker · done
  Finding: Implemented role-aware allowlisted packets and raw literal JSON with bounded receipts and repository-relative explorer targets. Focused checks passed with 40 tests and 431 assertions; full suite remains unrun.
  Artifacts: cli/src/Console/Command/BriefCommand.php, cli/src/Console/Command/BaseCommand.php, cli/tests/Console/BriefPacketTest.php, cli/tests/Console/JsonLiteralOutputTest.php
  Decision: none
  Next: Independently verify the frozen owned targets.

- 2026-10-21T05:24:21+00:00 · verifier · done
  Finding: All five criteria passed independent full verification including 40 tests and 82 CLI probes. Frozen hashes remained unchanged with only the requested PHPUnit flag deprecation.
  Artifacts: .workflow/items/T-013/outputs/verification.md
  Decision: none
  Next: Close T-013.

- 2026-10-21T05:24:21+00:00 · closed as done
