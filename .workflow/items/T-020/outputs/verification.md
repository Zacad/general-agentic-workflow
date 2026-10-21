# T-020 verification

**Verdict: PASS** — full verification of frozen acceptance and Design.

Verified 2026-10-21T20:08:30+02:00 against the working tree based on
`d9b1e6ccb17e24eba39d7df93c4e42f8fcfc23e5`. I read the verifier brief and
derived expectations from the five acceptance criteria and Design before
writing this report. I did not read the producer receipt or default worker
brief. No paid LLM call was made, and the `prasowka-bot` target was not
inspected.

## Frozen target hashes

| Path | SHA-256 |
| --- | --- |
| `README.md` | `10719e59208ba6022473a721f083d860b817044a4824cd0d20c22ec8c3125de2` |
| `docs/install.md` | `ab42f298cb0c12fc366fc9baa68bf761c723a9690afab8ef9842174a03add506` |
| `.agents/skills/install-workflow/SKILL.md` | `fd2c86f1659dd0e34285722561f5b07ef2bd793f1bf8b4be98caead1d7fa56c1` |
| `cli/src/Console/Command/InstallCommand.php` | `b91be1db5d55a403a10d2e688a0e940c89e011150cc916ef039f232be9743e60` |
| `cli/tests/Console/InstallScaffoldTest.php` | `317904afa0173074fcc8698ebc45d4d60909000efb6a9ee2aabafa6667dd3120` |
| `cli/tests/Console/InstallHandoffTest.php` | `a30c49ae15994694ef166b9faf91fd73313799175b9be6764e0ed0721625bc49` |
| `docs/opencode.example.json` | `869a44d2afba1024f818f121d266c481bb76f3f6339fa659f4f4b22c778a96f8` |

`AGENTS.md` was read for role/skill assumptions, not treated as a T-020
deliverable.

## Environment and method

- PHP 8.5.10 (NTS), PHPUnit 13.4.3, Composer dependencies installed.
- OpenCode v2.1.1 (`opencode-v2`), isolated `HOME`/`XDG_*` under
  `/tmp/opencode/t020-verify.saa6pM`, isolated server on loopback. Agent
  enumeration was used; no model prompt or session generation was invoked.
- OpenCode V2 source at `/var/home/adam/Projects/opencode`, branch `v2`,
  commit `4617210822bdbb31e5749751afbfb4584ccedac8` (CLI package version
  2.0.26), plus the installed 2.1.1 runtime for behavioral confirmation.
- Focused and full tests ran with `TMPDIR=/tmp/opencode` and
  `--do-not-cache-result`. Installer probes used fresh temporary targets only;
  no user project or host configuration was modified.

## Acceptance results

### AC1 — clean-project copy and existing-configuration manual merge — PASS

**Expected:** Installation guidance explains a reviewed clean-project setup
from the shipped example and an additive manual merge for an existing host
configuration without overwriting it.

**Observed:** `docs/install.md` has a `Register OpenCode (manual)` section:
- Clean project: review `opencode.example.json`, then copy it to the target
  root as `opencode.json`; the example's `{file:...}` role paths are explained
  as config-relative.
- Existing configuration: “preserve the host file”, merge applicable `agents`
  entries, permissions, model/default choices additively, and keep the host
  `default_agent` unless the user asks otherwise.
- The guide and installer state that registration is required, manual and
  unverified; the installer does not create or merge `opencode.json`.

**Method:** Read the guide, skill and README; compared the restoration with
the deleted installation hunks in `git diff 3ec52c6..d9b1e6c`; ran the
installer against a fresh target and against a target containing host
`AGENTS.md`, `README.md`, `LICENSE`, `.gitignore`, `opencode.json`,
`.claude/settings.json`, `.workflow/` records and a target-only docs file.
Hashes were captured before install, after a default install and after
`--force`.

**Result:** Default installation changed none of those host files. After
`--force`, the only pre-existing file that changed was `docs/install.md` (the
ordinary kit file the guide says `--force` refreshes); host root documents,
`opencode.json`, workflow records, item history, outputs and gitignore files
were byte-identical. The host `opencode.json` (including a sentinel secret)
was preserved and its secret never appeared in installer output.

**Limits:** I did not perform a real manual merge into a live user project;
that is intentionally a documented manual step. The preservation and
non-generation behavior was checked in an isolated target.

### AC2 — four agents, modes, default, reload and scoped V2 catalog — PASS

**Expected:** The guide names all four workflow agents and their modes,
explains default selection, and gives valid OpenCode V2 reload and
target-scoped catalog verification steps.

**Observed:** The guide states `workflow-orchestrator` is `primary` and
`default_agent`, and `workflow-explorer`, `workflow-worker` and
`workflow-verifier` are `subagent`s. It explains that the example omits
`model` (inherit session model), how to pin a model, and that `default_agent`
selects the starting primary. Reload is
`opencode api post /api/location/reload`; verification is
`opencode api agent.list --param "location[directory]=/path/to/target"`.

**Method:** Checked the actual example config and installed it into a fresh
temporary target, then started an isolated OpenCode 2.1.1 server and called the
documented operations. Also checked the V2 source:
- `packages/schema/src/config.ts` defines `agents` and `default_agent`.
- `packages/schema/src/config/agent.ts` defines `system`, `mode`
  (`primary|subagent|all`), `description` and ordered `permissions`.
- `packages/schema/src/permission.ts` accepts the example's
  `{action,resource,effect}` rule shape.
- `packages/protocol/openapi.json` defines `GET /api/agent` as `agent.list`
  with the deep-object `location[directory]` query parameter, and
  `POST /api/location/reload` as `location.reload`.
- `{file:...}` substitution resolves relative to the config file directory
  (`packages/core/src/config/variable.ts`), and `.agents/skills` is a V2
  compatibility source.

Runtime results in the isolated target:

```text
workflow-orchestrator primary hidden=False
workflow-explorer     subagent hidden=False
workflow-worker       subagent hidden=False
workflow-verifier     subagent hidden=False
```

`config.get` for the target reported document
`.../fresh project/opencode.json` with `default_agent=workflow-orchestrator`
and all four named agents. Role texts loaded from `.agents/roles/`, and
`skill.list` returned 12 project skills, including `install-workflow` and
`workflow-cli`. The documented reload endpoint returned success and the
catalog then reported all four agents.

**Result:** Pass. The guidance matches V2 schema and the installed runtime.

**Limits:** The catalog proves registration/loading only; it is not an LLM
startup or role-behavior proof. I did not create a paid session or prompt.

### AC3 — installer output is scaffold-only and truthful — PASS

**Expected:** Installer output clearly separates a scaffold copy from harness
registration, says registration remains required/manual/unverified, and does
not imply automatic generation or merging.

**Observed:** Actual fresh-target output began with `Scaffold copied into ...`
and included:

```text
Next steps (manual):
  This is a scaffold copy only; no automatic merges or deletions.
  OpenCode/harness registration is REQUIRED, MANUAL and UNVERIFIED.
  ...
  Clean project: copy .../docs/opencode.example.json to .../opencode.json.
  Existing config: manually merge only the agents, default and model entries;
  never overwrite existing settings.
  ...
  workflow validate proves scaffold records only; the catalog is not a paid LLM proof.
```

The fresh target contained no generated `opencode.json`; `docs/opencode.example.json`
was present for manual review. Existing host config was untouched in both
normal and forced runs. The output never printed the host config secret. The
focused test also asserts that the output does not contain `Installed into`,
`opencode debug agents` or a dumped config.

**Result:** Pass. Wording distinguishes scaffold from registration and does
not claim automatic integration. Installer still initializes workflow records
and the derived board, which is presented as scaffold creation.

**Limits:** The output says merge “agents, default and model entries”; the
guide additionally names permissions. The guide is the canonical next step
the output points to, so this is not misleading but is a wording gap rather
than a full permission recap.

### AC4 — focused handoff and preservation tests — PASS

**Expected:** Focused tests assert the handoff wording and existing-host
preservation behavior.

**Observed:** `InstallHandoffTest.php` has three focused tests:
- fresh scaffold handoff wording, including scaffold-only, required/manual/
  unverified, example path, existing-config merge warning, skills discovery,
  four expected agents, `agent.list` target path, `workflow validate` limits,
  PATH and board guidance;
- no automatic `opencode.json`, no config dump and no stale `Installed into`
  wording;
- host configuration preserved under both default and `--force`, and secret
  values never printed.

`InstallScaffoldTest.php` covers fresh scaffold contents, no source history or
dependencies, preserved root documents/history/outputs/gitignore/config, and
collision/unsafe-path behavior.

**Method:** Ran the focused files and inspected their assertions. Command:

```sh
cd cli
TMPDIR=/tmp/opencode php vendor/bin/phpunit --do-not-cache-result \
  tests/Console/InstallScaffoldTest.php tests/Console/InstallHandoffTest.php
```

**Result:** `36 tests, 255 assertions`, all pass. The single reported PHPUnit
deprecation is the verification command's own deprecated
`--do-not-cache-result` option, not a product/test failure.

### AC5 — README link and test suite — PASS

**Expected:** README points to actionable installation guidance; focused and
full tests pass.

**Observed:** README links `[installation guide](docs/install.md)` and
explicitly says scaffolding does not register agents and that OpenCode
registration is a required, manual and unverified follow-up, naming clean
project copy, existing-config merge, reload and catalog checks. The full suite
also executes the documented README quickstart and install smoke examples.

**Method:** Read README; ran focused and full suites and `workflow validate`.

```sh
cd cli
TMPDIR=/tmp/opencode php vendor/bin/phpunit --do-not-cache-result
cd ..
cli/bin/workflow validate
```

**Result:** Full suite `702 tests, 5608 assertions`, all pass; `workflow
validate` reports `OK (0 warnings)`.

## Additional installer probes

- Fresh target with a space in its path: installer exited 0, copied 93 files,
  created `.workflow/` records and board, and left no `opencode.json`.
  `workflow --project <target> validate` (kit binary) reported `OK (0 warnings)`.
- Repeated `workflow --project <target> init` twice: both succeeded; target
  still validated `OK (0 warnings)`.
- `workflow --project <target> board`: regenerated `.workflow/board.html`.
- Existing target with host `docs/install.md` replaced by a directory, and
  `.agents` pointing at an external directory: `install --force` exited 0,
  preserved the directory collision and symlink, and left the external
  sentinel untouched.
- The target’s own copied `cli/bin/workflow` was not run because the scaffold
  correctly excludes `vendor/`; `composer install --no-dev` in the target
  remains the documented bootstrap step.

## Non-blocking observations and limits

1. Printed installer commands interpolate the target path without shell
   quoting. The observed target `.../fresh project` (with a space) produced
   unquoted `cd`, `--param` and `--project` commands. A user with a spaced path
   must quote or copy the documented placeholder commands instead. This is
   outside AC1–AC5 but is a real actionability edge for such targets.
2. No target-side `composer install --no-dev` was performed, so the target’s
   own copied CLI binary runtime was not exercised; validation used the kit
   binary against the target’s records. This does not affect the installer or
   guidance criteria.
3. `prasowka-bot` was deliberately not inspected; its independent check
   belongs to the epic’s later integration work as instructed.
4. The focused/full tests use isolated temporary targets under `/tmp/opencode`
   and do not prove a live LLM session, role independence or real OpenCode UI
   startup.

## Deleted-hunk coverage check

The installation hunks removed between `3ec52c6` and `d9b1e6c` contained the
kit layout, prerequisites, clean/existing registration, example fields,
model/default behavior, relative role references, skill discovery, CLI PATH,
board refresh and an isolated smoke check. T-020 restores those unique facts
in their owning documents; the only deliberate substitution is the V2
`agent.list`/`location[directory]` catalog check in place of the older
`opencode debug agents` proof. The V2 runtime test above confirms the
replacement works.

## Verdict

All five acceptance criteria pass, the Design's scaffold/manual-merge boundary
is preserved, and the output cannot reasonably be read as automatic harness
integration. No blocking defect found.
