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

Use `./cli/bin/workflow` from the target or the absolute
`/path/to/target/cli/bin/workflow`; optionally add `/path/to/target/cli/bin` to
`PATH`, then run `workflow --project /path/to/target validate`. Mutating commands
refresh the derived `.workflow/board.html`; run `workflow board` to force a
rewrite after a manual edit.

## Register OpenCode

The installer registers OpenCode agents for clean projects and verifies the
catalog when the `opencode` CLI is available. Registration checks are advisory:
they never fail the install.

- **Clean project (no `opencode.json`):** the installer copies
  [opencode.example.json](opencode.example.json) to the target root as
  `opencode.json` automatically. Keep it at the root because its `{file:...}`
  role paths resolve from the config file. It registers
  `workflow-orchestrator` as `primary` and `default_agent`, plus
  `workflow-explorer`, `workflow-worker` and `workflow-verifier` as `subagent`s.
  It omits `model`, so agents inherit the session model; add
  `"model": "provider/model"` only to pin one. `default_agent` chooses the
  starting primary; change it only when sessions should start elsewhere.
- **Existing configuration:** the host file is never overwritten. The installer
  parses it and reports exactly which `workflow-*` agent entries are missing;
  manually merge only the applicable `agents` entries, permissions,
  `model`/`default_agent` choices and unrelated settings. Merge additively and
  review conflicts; do not overwrite host authority. Keep the host
  `default_agent` unless the user asks otherwise.
- **Skills:** `.agents/skills/` is discovered as a project-compatibility source;
  no `skills` entry is needed. Add one only for extra local or HTTP sources.
- **Reload and verify:** restart OpenCode or run
  `opencode api post /api/location/reload`. The installer queries the
  target-scoped V2 `/api/agent` catalog when `opencode` is on `PATH` and prints
  which `workflow-*` agents are registered; it skips gracefully and prints the
  manual check when the CLI is absent:

  ```sh
  opencode api agent.list --param "location[directory]=/path/to/target"
  ```

  Confirm all four `workflow-*` IDs and modes, and that the reviewed
  `default_agent` is a visible primary agent. This proves registration only:
  `workflow validate` checks scaffold records and is not registration proof,
  and the catalog check is not a paid LLM proof. Restart after later prompt or
  config changes.

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
