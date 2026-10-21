# T-011 independent full verification

**Outcome: pass for all five acceptance criteria in the available environment.** Actual PHP 8.4.1 execution and a PHP binary without the native mbstring extension are explicitly **unrun**. No target defect was found; no repair or dependency upgrade was performed.

## Expectations derived before inspecting the implementation

The verifier-only brief and root `AGENTS.md` are the authority. No producer receipt, worker brief, JSON brief or worker report was read. This is a full check of T-011, not a targeted recheck. Only this report is owned by the verifier; repository code, vendor and metadata must remain unchanged.

1. **Metadata and graph:** parse both Composer files; require matching PHP floors of at least 8.4.1; validate requirements and the lock content hash using the existing Composer executable; compare every locked runtime/development package and version against `HEAD`, checking that the lock change is metadata-only.
2. **Missing-autoload boundary:** execute an isolated copy of the frozen executable with no vendor directory; expect a failing exit, runtime `composer install --no-dev` instructions and explicitly separate full `composer install` development instructions. Run the focused bootstrap tests with existing project dependencies.
3. **Real runtime prerequisites:** inspect locked requirements, installed dependency identities and generated platform checks; exercise Composer 2, iconv and the actual mbstring polyfill. Run runtime bootstrap with native mbstring disabled where the existing PHP executable permits it; do not infer a native-mbstring requirement from the development toolchain.
4. **Runtime/development separation:** independently exercise no-dev bootstrap and full-development verification. An isolated install is permitted only with existing Composer/dependencies, no network repair or graph upgrade. Record unavailable installations/environments as unrun.
5. **Development extensions and limits:** inspect actual native mbstring, DOM, filter, JSON, libxml, tokenizer, XMLWriter and Phar availability and locked requirements. Execute the development tests with `TMPDIR=/tmp/opencode` and `--do-not-cache-result`. PHP 8.4.1 execution must be marked unrun if only PHP 8.5 is available.

For each criterion the completed report will state expected behavior, method, environment, observations, result and limits. Frozen target hashes are checked again after verification.

## Initial and final freeze

Baseline commit: `3ec52c68cd683cee8c7a5ca4efd74dbdb769ec3c`.

| Frozen path | SHA-256 |
| --- | --- |
| `cli/composer.json` | `ba18ee277c89137f1d36f89af78c8f6deb74bf9310cfb85d29ab6becd5129c2d` |
| `cli/composer.lock` | `b7a8685d64aaec407e60e2858c372cf88365e9fcc5444156f817094b6e2b0b65` |
| `cli/bin/workflow` | `323be940db4ceb592d08abec5d5ce884b04f0a3e5257be7bd1883c63558586fb` |
| `cli/tests/Console/RuntimeBootstrapTest.php` | `f76ce012501682e2787dead9906d03e83271ace82fc33976893ad5c9a3f21f8c` |

Initial environment: Linux; `/home/linuxbrew/.linuxbrew/bin/php`, PHP 8.5.10 CLI. Composer is not on PATH; an existing `/tmp/opencode/composer.phar` was discovered without reading worker artifacts. No PHP 8.4/8.4.1 executable was found on PATH.

All four final SHA-256 values matched this initial table after the checks. `HEAD` remained the baseline commit. Syntax checks for the executable and focused test, and scoped `git diff --check`, exited 0.

## Results

### Shared environment and isolation

Existing Composer is **2.10.3**, SHA-256 `7a2d379d5b8ffdaa028580ef26494c36d2feef4b178d3dd1473a4dbc5e17c8d6`. The repository's existing vendor has exactly the locked **42** packages, with development dependencies enabled; name/version maps have no missing, additional or mismatched entries. All PHP execution below used **8.5.10**, not 8.4.1.

Independent installations used `/tmp/opencode/t011-verifier-wen17ela/runtime-cli` and `development-cli`, initially without vendor, with copied frozen Composer files, executable and current application sources. Both used the already-existing `/home/adam/.cache/composer` archives with `COMPOSER_DISABLE_NETWORK=1`, `COMPOSER_CACHE_READ_ONLY=1`, `COMPOSER_CACHE_DIR=/home/adam/.cache/composer` and an isolated `COMPOSER_HOME`. No prerequisites were installed, no network repair was attempted, and repository vendor/code were not changed. `--no-scripts --no-plugins` constrained installation side effects; the manifest has no install hooks. Both installs preserved their copied manifest and lock hashes.

All PHPUnit runs set `TMPDIR=/tmp/opencode` and passed `--do-not-cache-result`.

### 1. Matching PHP metadata; unchanged package graph — pass

**Expected:** manifest and lock require PHP at least 8.4.1; metadata is coherent and no locked package changes.

**Method/environment:** parse both JSON files and `git show HEAD:cli/composer.lock`; compare every complete package object and name/version map in both lock groups. Use the existing Composer 2.10.3 on PHP 8.5.10 for strict validation, its actual `Composer\Package\Locker::getContentHash()` and semantic-version boundary checks.

**Observed:** manifest `require.php` and lock `platform.php` both equal `>=8.4.1`; `platform-dev` remains empty. All **15 runtime** and **27 development** package objects are byte-content-equivalent after JSON decoding to their HEAD counterparts, including versions, requirements and source/dist references. Only lock top-level `content-hash` and `platform` changed. The manifest change is only the PHP floor. Locked Symfony runtime packages independently require `>=8.4.1`.

`php /tmp/opencode/composer.phar validate --strict --no-check-publish --no-plugins` exited 0. Recorded and independently computed content hashes both equal `2d135ebcb1042a176a0b3b528cf164eb`. Composer Semver rejects 8.4.0 and accepts 8.4.1 and 8.5.10 against the root requirement.

**Limits:** semantic-version evaluation and the generated `PHP_VERSION_ID >= 80401` platform check are not actual execution on PHP 8.4.1. That execution is **unrun**: no such executable was found on PATH or in the existing Homebrew/system PHP locations checked.

### 2. Missing-autoload runtime/development guidance — pass

**Expected:** a missing autoloader fails clearly, recommends no-dev runtime installation, and separately explains development setup.

**Method/environment:** inspect and execute the frozen executable on PHP 8.5.10 in an independent directory named `missing kit's cli`, with no vendor; run the focused PHPUnit test using real existing development dependencies.

**Observed:** exit 1, empty stdout; stderr states PHP `>=8.4.1`, Composer 2, iconv and polyfilled mbstring. It distinguishes `Runtime setup: composer install --no-dev --working-dir=...` from `Development setup: composer install --working-dir=...`, lists the native development extensions and correctly shell-quotes the space/apostrophe-containing path. The focused test also exercises an existing isolated autoloader and checks propagation of its application exit code 23, rather than falling through to missing-dependency guidance.

Focused command: `php vendor/bin/phpunit --do-not-cache-result tests/Console/RuntimeBootstrapTest.php`: **4 tests, 17 assertions, exit 0**.

**Limits:** one PHPUnit runner deprecation is recorded below; guidance was not tested with Composer 1, which is outside the supported setup.

### 3. Composer 2, iconv and actual mbstring polyfill — pass, with one unrun environment

**Expected:** runtime needs iconv and supports polyfilled mbstring, without adding a native-mbstring gate or duplicated extension detector.

**Method/environment:** inspect actual lock requirements, executable, installed polyfill bootstrap and generated Composer platform check; run no-dev platform checks in both repository and isolated runtime installation on Composer 2.10.3/PHP 8.5.10. Exercise the installed polyfill, not a replacement stub.

**Observed:** root runtime requirements contain no `ext-mbstring`. Locked `symfony/polyfill-mbstring` **v1.43.0** requires `ext-iconv: *` and provides `ext-mbstring: *`. No other native runtime extension is required by the locked runtime packages. `check-platform-reqs --no-dev --no-plugins` exits 0 and lists only iconv and PHP. The executable adds guidance only, not an extension detector. Composer 2 successfully installs and starts the frozen runtime dependency graph.

In the isolated no-dev installation, `php -n` plus `disable_functions` for all **65** existing native mbstring functions makes `mb_strlen` absent before autoload. Actual Composer autoload defines it from the installed polyfill's `bootstrap80.php`; reflection confirms it is **not internal**. Unicode probes return `mb_strlen('é水', 'UTF-8') = 2`, ISO-8859-1 conversion of `é` yields hex `e9`, and uppercasing `été` yields `ÉTÉ` (exit 0). Under those same flags, both real `bin/workflow --version` and `list --raw` exit 0.

**Limits:** native mbstring remains a loaded extension in this function-disabled probe. Even `php -n` retains it in the available binary. A genuinely extension-absent run is **unrun**, not represented as passing; the probe establishes actual fallback function execution, not equivalence to an unloaded extension. Composer's generated platform check is PHP-only and does not itself prove iconv availability; actual platform checks and native-extension inspection do.

### 4. Runtime-only and full development exercised separately — pass

**Expected:** runtime bootstrap works with no development packages; full development is checked as a different dependency setup.

**Method/environment:** offline, clean-vendor installs in the two independent scratch directories with existing Composer/cache and PHP 8.5.10:

```sh
php /tmp/opencode/composer.phar install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-plugins
php /tmp/opencode/composer.phar install --no-interaction --no-progress --prefer-dist --no-scripts --no-plugins
```

**Observed:** runtime install exits 0: **15 installs, 0 updates, 0 removals**; installed versions exactly match lock runtime packages, `dev=false`, no `vendor/phpunit`, and PHPUnit's test class cannot autoload. Real CLI version prints `workflow 5.0.0`; full raw command listing exits 0. The fallback probe above uses this installation.

Full-development install separately exits 0: **42 installs, 0 updates, 0 removals**, exact complete lock match and `dev=true`. Its CLI bootstrap, full platform check and real `vendor/bin/phpunit --version` all exit 0; PHPUnit identifies itself as **13.4.3**. Separately, the repository's already-installed complete development graph runs the full suite: `php vendor/bin/phpunit --do-not-cache-result` gives **117 tests, 600 assertions, exit 0**.

**Limits:** no-dev describes the installed dependency graph, not a stripped-down PHP binary: native development extensions remain present in PHP. The full suite used the live workspace's other sources, which have disjoint concurrent workers; it is an integration observation, not verification of those workers' deliverables. The four T-011 targets stayed frozen.

### 5. Native development prerequisites and honest unavailable checks — pass

**Expected:** record the requested development extensions and distinguish native runtime availability from Composer's ability to satisfy virtual extension packages.

**Method/environment:** inspect locked development package requirements and the installed PHPUnit launcher; directly call `extension_loaded()`/`phpversion()` for each required native extension on PHP 8.5.10; run real PHPUnit and complete Composer platform checks.

| Native development extension | Requirement evidence | Observed native availability |
| --- | --- | --- |
| mbstring | PHPUnit launcher explicitly checks `extension_loaded('mbstring')`; PHPUnit/coverage/comparator/exporter lock requirements | loaded, 8.5.10 |
| DOM | PHPUnit, coverage and phar-io/manifest lock requirements | loaded, 20031129 |
| filter | PHPUnit lock requirement and launcher | loaded, 8.5.10 |
| JSON | PHPUnit/php-parser lock requirements and launcher | loaded, 8.5.10 |
| libxml | PHPUnit/coverage/phar-io/manifest requirements | loaded, 8.5.10 |
| tokenizer | php-parser/theseer requirements and PHPUnit launcher | loaded, 8.5.10 |
| XMLWriter | PHPUnit/coverage/phar-io/manifest/theseer requirements | loaded, 8.5.10 |
| Phar | phar-io/manifest lock requirement | loaded, 8.5.10 |

Native iconv is also loaded, version 8.5.10, independently satisfying the runtime requirement. Complete `check-platform-reqs --no-plugins` exits 0, but notably reports `ext-mbstring` as **provided by symfony/polyfill-mbstring**: that result alone does not establish PHPUnit's native mbstring requirement. Direct native-extension checks and actual PHPUnit execution establish it here.

**Limits/warnings:** focused and full suites each report one runner deprecation, not a failed test. A focused rerun with `--display-phpunit-deprecations` identifies the required `--do-not-cache-result` option as deprecated for removal in PHPUnit 14; tests still exit 0. PHP 8.4.1 execution, true native-mbstring absence and development startup with individual extensions physically absent are **unrun**, with no claim that Composer's virtual provision makes those development environments valid. Missing environments were not installed or repaired.
