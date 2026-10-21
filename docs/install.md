# Install the workflow

Installation adds a scaffold; it does not manage or merge host documents.
Read the target's root/nested `AGENTS.md`, harness configuration, `.agents/`
and existing `.workflow/` first. Review proposed additions and collisions.

## Prerequisites

- Runtime: PHP **>=8.4.1**, Composer 2 and **iconv**. The locked dependencies
  provide an mbstring polyfill; native mbstring is not a runtime prerequisite.
- Development: full `composer install` additionally requires native **dom,
  filter, json, libxml, mbstring, phar, tokenizer and xmlwriter**.
  Composer's platform checks are authoritative.

## Copy and merge

From a bootstrapped kit:

```sh
/path/to/kit/cli/bin/workflow install /path/to/target
```

Default copying is add-only. Existing root `AGENTS.md`, `README.md` and `LICENSE`
are preserved **even with `--force`**. Existing workflow documents, item history,
outputs, host configuration and gitignore files are preserved. Source project
records are never imported; a safe derived board may regenerate.

`--force` deliberately refreshes ordinary kit-relative files under `.agents/`,
`docs/` and `cli/`. Review/back up external customizations first. It does not
merge content or delete target-only files. Source/destination symlinks and type
collisions are held or rejected, not followed during copying. Source-equal,
ancestor and descendant targets are rejected, including resolved aliases.

Manually merge the canonical workflow rules from the kit's `AGENTS.md` into
the preserved target root instructions. Keep host authority and stricter rules;
resolve conflicts through review. Every installed role explicitly loads that
root file, even when used separately. If it is absent, incomplete or conflicting,
the role must surface the need for a reviewed manual merge, not assume the host
rules were replaced. Do not dispatch workflow roles until this merge is reviewed.

## Bootstrap the target

```sh
cd /path/to/target/cli
composer install --no-dev
cd ..
./cli/bin/workflow init
./cli/bin/workflow validate
```

The installer already initializes missing records; `init` is safe to repeat.
Manual copies must exclude source `.workflow/`, `cli/vendor/`, `cli/tools/`
and caches; initialize with the target's templates instead.

For harness registration, review [opencode.example.json](opencode.example.json)
and merge the applicable entries into the target's configuration; do not
overwrite existing settings. Its role-file references are project-relative, so
keep the configuration at the project root. Restart the harness after changing
prompts/configuration and check that its four workflow roles are available.
CLI/static file checks do not prove that an LLM actually loaded the instructions.

## Isolated smoke check

Run this from the bootstrapped kit root, not a user's project. It uses the
source binary deliberately, so no second dependency download is needed. It
checks scaffold/record commands, not the installed binary's runtime bootstrap
or a real harness session. The cancelled smoke item is retained, not deleted.

<!-- protocol-example: install-smoke -->
```sh
kit="$PWD"
export PATH="$kit/cli/bin:$PATH"
mkdir -p "${TMPDIR:-/tmp/opencode}"
smoke="$(mktemp -d "${TMPDIR:-/tmp/opencode}/workflow-install.XXXXXX")"
workflow install "$smoke" --name "Isolated smoke"
cd "$smoke"
workflow validate
workflow new "Check scaffold" --kind task \
  --standalone-reason "Isolated installation smoke, not project work" \
  --outcome "The isolated scaffold accepts a valid record."
workflow set T-001 'scope_in=CLI record smoke' 'scope_out=Real project work' \
  'acceptance=The role brief loads and cancelled history remains'
workflow plan T-001 --text "Exercise record commands only; no exploration needed for this smoke."
workflow design T-001 --text "Read the brief and cancel this retained smoke record; no deliverable or open risks."
workflow brief T-001 --for orchestrator --json
# After confirmation of this exact isolated smoke scope:
workflow approve T-001 --by user
workflow brief T-001 --for worker
workflow close T-001 --status cancelled --reason "Isolated smoke completed; no project work requested"
workflow board
workflow validate
printf 'Smoke retained at %s\n' "$smoke"
```

## Refresh

Review changed kit files and held paths, then copy additions or deliberately
use `install --force` for reviewed kit-file refreshes. Merge root rules and
harness settings manually, rerun `composer install --no-dev` if dependencies
changed, then `validate`. Keep existing workflow history. There is no managed
updater, automatic merger, deletion engine or source-history migration.
