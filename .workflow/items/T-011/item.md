---
id: T-011
kind: task
parent: E-002
title: 'Align runtime requirements and bootstrap guidance'
status: done
phase: verify
priority: 2
depends_on: []
owns:
  - cli/composer.json
  - cli/composer.lock
  - cli/bin/workflow
  - cli/tests/Console/RuntimeBootstrapTest.php
artifacts:
  - cli/composer.json
  - cli/composer.lock
  - cli/bin/workflow
  - cli/tests/Console/RuntimeBootstrapTest.php
  - .workflow/items/T-011/outputs/verification.md
approval:
  at: '2026-10-21T04:51:17+00:00'
  by: user
  scope_hash: ebbc1f85f9bef5c5614df68a09501b6fe12bf9f1d5d9abf6253919cce43ecf8e
last_result:
  at: '2026-10-21T05:07:47+00:00'
  role: verifier
  status: done
  finding: 'All five criteria pass on PHP 8.5.10; independent no-dev/full-dev installs and 117 tests with 600 assertions passed with frozen hashes unchanged. Actual PHP 8.4.1 and native-mbstring-absent environments remain unrun.'
  decision: null
verified_at: '2026-10-21T05:07:47+00:00'
created: '2026-10-20T19:13:13+00:00'
updated: '2026-10-21T05:07:47+00:00'
outcome: 'Composer platform metadata and bootstrap guidance accurately distinguish the locked runtime from development prerequisites without upgrading dependencies.'
scope:
  in:
    - 'Align PHP minimum with the locked Symfony runtime requirement'
    - 'Runtime-only bootstrap guidance with separate development instructions'
    - 'Narrow lock platform/content metadata refresh without package-version changes'
  out:
    - 'Dependency upgrades or broader dependency-graph changes'
    - 'Duplicated runtime extension detectors or automatic package installation'
    - 'Installer implementation or protocol prose owned by other children'
acceptance:
  - 'composer.json and lock platform metadata agree on PHP at least 8.4.1 with all locked package versions unchanged'
  - 'Missing-autoload bootstrap guidance recommends composer install --no-dev for runtime and identifies full composer install as development setup'
  - 'Runtime checks account for Composer 2, iconv and polyfilled mbstring without falsely requiring native mbstring for runtime-only use'
  - 'A runtime-only installation and bootstrap are exercised separately from full development dependencies where environments are available'
  - 'Development verification records native mbstring, DOM, filter, JSON, libxml, tokenizer, XMLWriter and Phar requirements, and unavailable checks are explicitly unrun rather than claimed passing'
---

## Plan

Question: which prerequisites are actually required by the locked runtime versus development tooling?
Dimensions: PHP platform floor, runtime extensions and bootstrap instructions.
Sources: .workflow/items/E-002/outputs/scaffold-discovery.md, composer files and cli/bin/workflow.
Stop: metadata and guidance agree with the existing lock graph without upgrades.
Acceptance: compare package versions and platform metadata, then record runtime-only and development checks with honest environment limits.

## Design

Files: narrowly update composer.json/lock platform metadata and bootstrap guidance, with a focused bootstrap test.
Decision: require PHP at least 8.4.1 and Composer 2, use no-dev runtime setup and keep current locked package versions.
Checks: Composer metadata validation, package-version equality, runtime-only bootstrap and separate full-suite development prerequisites.
Risks: native development extensions exceed runtime needs, and missing clean environments must be reported unrun.
Upstream: .workflow/items/E-002/outputs/scaffold-discovery.md and parent Design provide canonical requirements for installer and protocol writers.

## Progress

- 2026-10-21T04:59:26+00:00 · worker · done
  Finding: Aligned runtime metadata and bootstrap guidance; 4 tests and 17 assertions passed in repository and isolated development setups and isolated no-dev checks passed with package versions/vendor unchanged. PHP 8.4.1 and genuinely mbstring-free execution remain unrun.
  Artifacts: cli/composer.json, cli/composer.lock, cli/bin/workflow, cli/tests/Console/RuntimeBootstrapTest.php
  Decision: none
  Next: Independently verify the frozen artifacts.

- 2026-10-21T05:07:47+00:00 · verifier · done
  Finding: All five criteria pass on PHP 8.5.10; independent no-dev/full-dev installs and 117 tests with 600 assertions passed with frozen hashes unchanged. Actual PHP 8.4.1 and native-mbstring-absent environments remain unrun.
  Artifacts: .workflow/items/T-011/outputs/verification.md
  Decision: none
  Next: Close T-011.

- 2026-10-21T05:07:47+00:00 · closed as done
