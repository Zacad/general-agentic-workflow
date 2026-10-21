# E-002 baseline checks

Checked 2026-10-20 at commit `3ec52c68cd683cee8c7a5ca4efd74dbdb769ec3c`.
Scope: existing tests, read-only workflow validation, and check side effects;
no implementation changes or new regression tests.

## Environment and available commands

- Project: `/var/home/adam/Projects/general-agentic-workflow` (all commands below run here).
- OS: `Linux 7.2.5-200.fc44.x86_64 x86_64` (`uname -srm`).
- `php --version`: PHP **8.5.10** CLI, NTS, Homebrew build dated
  Aug 25 2026 21:09:32; Zend Engine/OPcache 8.5.10.
- `command -v php`: `/home/linuxbrew/.linuxbrew/bin/php`;
  `PHP_BINARY`: `/var/home/linuxbrew/.linuxbrew/Cellar/php/8.5.10/bin/php`.
- `php --ini`: loaded `/home/linuxbrew/.linuxbrew/etc/php/8.5/php.ini`;
  additional scan directory `/home/linuxbrew/.linuxbrew/etc/php/8.5/conf.d`,
  no additional INI files parsed.
- `command -v php php8.4 php8.3 php8.2 php8.1 composer composer2 phpunit phpunit.phar || true`
  found only `php`. Composer and a global PHPUnit command are not on PATH;
  no alternative PHP runtime was found under those names.
- Existing local runner: `php cli/vendor/bin/phpunit --version` reports
  **PHPUnit 13.4.3**. `cli/composer.json` defines `composer test` as `phpunit`,
  but Composer commands were not executable from PATH. No installation,
  dependency update, tool download, or network check was attempted.
- Manifest requires PHP `>=8.4`; installed platform check and locked Symfony
  8.1.x/PHPUnit 13.4.3 dependencies require **>=8.4.1**.

## Output safety inspection

Before running tests, inspected `cli/phpunit.xml`, runner help/cache option
handling, and test fixture setup/write locations. Configuration bootstraps
`vendor/autoload.php`, selects `tests/`, includes `src/` for source filtering,
and sets `cacheDirectory=".phpunit.cache"`; it configures no logging,
coverage reports, or extensions. Existing ignored file
`cli/.phpunit.cache/test-run-history` was present before checks.

`--do-not-cache-result` disables run-history writes in PHPUnit 13.4.3;
`--do-not-cache-test-index` separately disables index-cache writes.
Coverage and logging were explicitly disabled. `WorkflowTestCase` places
fixtures under `WORKFLOW_TEST_ROOT` or the system temporary directory;
CLI tests pass that fixture root as `--project`. Both environment overrides
below keep test writes outside the project. `WORKFLOW_TEST_ROOT` was unset
before the explicit override.

## Executed checks and results

```sh
TMPDIR=/tmp/opencode WORKFLOW_TEST_ROOT=/tmp/opencode php cli/vendor/bin/phpunit --configuration cli/phpunit.xml --do-not-cache-result --do-not-cache-test-index --no-coverage --no-logging --colors=never --display-all-issues
```

Exit **0**: **48 tests, 173 assertions**, all passed; 0.126 seconds, 26.00 MB.
One PHPUnit runner deprecation: `--do-not-cache-result` will be removed in
PHPUnit 14; its replacement is `--do-not-record-test-run-history`. This is
the requested legacy option, not a test failure; output says
“OK, but there were issues!” No skipped/incomplete tests were reported.

```sh
TMPDIR=/tmp/opencode cli/bin/workflow validate
```

Exit **0**: `OK (0 warnings)`. Also ran the required explorer brief first,
and read-only `cli/bin/workflow status` (one active epic, nine done items).

## Working tree and limits

- Before/after `git status --porcelain=v1 --untracked-files=all` was identical:
  only pre-existing untracked `.workflow/items/E-002/item.md` and
  `docs/handoff-compare.md`. `git diff --exit-code` and
  `git diff --cached --exit-code` returned 0 with no output.
- A file-metadata snapshot of all 2,588 project files, including ignored
  files but excluding `.git`, found **no additions, removals, or changes**
  across the test/validation interval. Existing PHPUnit history was unchanged;
  no project cache, log, coverage, or generated board output was produced.
  Snapshot evidence is external at
  `/tmp/opencode/e002-baseline-2ce94ajs/{project-before.json,changes-after-checks.json}`.
- This report is the only intentional project write by this explorer;
  implementation, item records, and `docs/handoff-compare.md` were not edited.
- No code coverage was collected. PHP 8.4.0 (manifest minimum), PHP 8.4.1
  (locked dependency minimum), other runtimes/platforms, Composer
  validation/install/audit, and new defect-specific regression coverage were
  **not tested**. A green existing suite does not establish that E-002's
  proposed corrections are already implemented.
