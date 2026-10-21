# Scaffold and runtime discovery

## Finding and authority

Retain scaffold installation plus manual merges, not a managed updater. Harden destination resolution and collision handling; preserve host instructions and workflow history even with force. Source references below were checked without running an installer copy, package installation or destructive containment experiment.

## Checked evidence

- `cli/src/Console/Command/InstallCommand.php:48-74` uses realpath only for an entirely existing destination, otherwise lexical normalization (`188-202`). A missing suffix after a symlink can evade source containment. The ancestor check at lines 68-69 builds a double slash for root, so `/` needs explicit separator-aware handling.
- `InstallCommand.php:84-96,169-185` applies force to root AGENTS/README/LICENSE as well as kit files. Directory traversal at lines 128-166 and copying do not safely hold symlink/type collisions.
- `cli/src/Store/ProjectInitializer.php:25-57` creates missing records but tests only ordinary directory/file existence, so linked or wrong-type destinations need deliberate rejection. Source `.workflow/` is excluded by `InstallCommand.php:17-24`; preserve that invariant.
- `cli/composer.json:6-15` advertises PHP at least 8.4. Locked Symfony 8.1 packages require at least 8.4.1: for example `cli/composer.lock:113-132` pins Console 8.1.9. The lock platform at lines 3098-3101 still says 8.4.
- `cli/composer.lock:755-776` supplies mbstring via a polyfill requiring iconv. Development dependencies require native mbstring, DOM/filter/JSON/libxml/tokenizer/XMLWriter and Phar (for example lines 1249-1251,1306-1311,1807-1816). `README.md:31` and `docs/install.md:15-18` do not distinguish runtime from development correctly.
- Vendor and tools are excluded from copies, but `InstallCommand.php:120-123` and `cli/bin/workflow:8-11` recommend unrestricted Composer installation, implicitly bringing development requirements into runtime bootstrap.

## Selected scaffold contract

Resolve existing path components before processing subsequent parent segments, respecting symlink semantics and retaining genuinely missing suffixes. Reject dangling links and non-directory intermediates. Compare resolved source and target for equality and ancestor/descendant containment with component boundaries, including root; reject before mkdir/copy. Lexical collapse before symlink resolution is not equivalent. This filesystem resolver is distinct from lexical ownership matching.

Root `AGENTS.md`, `README.md` and `LICENSE` are create-only and remain untouched even with force. Preserve host harness config and existing gitignore files. Under `.agents/`, `docs/` and `cli/`, skip collisions normally; explicit `--force` refreshes ordinary kit-relative files only. It does not merge content, follow symlinks, delete target-only files or replace host rules/records. Hold or reject symlink and file/directory collisions rather than traversing them, including initializer paths and generated-board destinations. Do not import source PRD/Architecture/items/outputs. Existing target PRD/Architecture/items/outputs remain intact; an ordinary derived board may regenerate safely.

Installer owns InstallCommand and ProjectInitializer, plus focused installer tests. Runtime owns composer.json/lock and the bootstrap binary; installer incorporates the agreed runtime-only guidance in its own messages, avoiding two writers for InstallCommand. Docs have one separate protocol writer. No update engine, provenance manifest or automatic host-document merge is needed.

## Runtime contract

Document PHP at least 8.4.1 and Composer 2. Runtime installation uses `composer install --no-dev` with the locked runtime requirements, including iconv and polyfilled mbstring. Full `composer install` is for development and requires the native extensions listed above. Keep the current dependency graph; adjust only minimum-version/platform metadata and bootstrap guidance, without package upgrades. Composer's platform checks remain authoritative; do not duplicate them in a new bootstrap dependency detector.

## Checks and limits

Use isolated temporary target trees for empty/nonempty installs, force/default collisions, host-rule/history byte preservation, target-only files and symlink/type holds. Test source/equal/ancestor/root/missing symlink-suffix containment through refusal paths before any copy. A string-only resolver test is cheap but cannot establish filesystem symlink behavior; add real temporary links. Tests must clean up their own links safely rather than follow external targets.

Verify runtime Composer metadata without changing package versions; exercise a runtime-only install/bootstrap and a separate full development install/test environment. Record exact versions/extensions and mark unavailable environments unrun. Existing dependency declarations are source evidence, not proof of a successful clean installation. Concurrent hostile filesystem replacement is not a new security guarantee; this bounded correction addresses deterministic path and collision handling, not a transactional updater.
