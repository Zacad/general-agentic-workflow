# E-003 integration verification

**Verdict: PASS — full epic verification, not a targeted recheck.**

Freely stated expectations were derived from the E-003 acceptance criteria and
Design before reading the producer receipt or the T-019/T-020 verifier reports.
Those two reports were read only afterward as supplemental context; every result
below is based on the current working tree and the live target checked directly.

## Frozen target and environment

- Repository: `/var/home/adam/Projects/general-agentic-workflow`
- Base commit: `d9b1e6ccb17e24eba39d7df93c4e42f8fcfc23e5` (`d9b1e6c`); `HEAD`
  remained `d9b1e6c` throughout verification. No commit was created.
- Working tree: 13 modified tracked paths and the expected E-003/T-019/T-020
  records plus `cli/tests/Console/InstallHandoffTest.php`; no unrelated tracked
  source path changed.
- PHP 8.5.10 NTS, PHPUnit 13.4.3, Symfony dependencies installed.
- OpenCode `v2.1.1`; target `/home/adam/Projects/prasowka-bot`.
- Target-scoped agent catalog was queried through V2 `agent.list`; no model
  prompt, generation or paid LLM call was made.
- Tests used `TMPDIR=/tmp/opencode ... --do-not-cache-result`.

## Per-criterion results

### AC1 — restored guidance across the fourteen rewritten documents; no duplicate lifecycle

**Expected:** Every unique operating fact removed between `3ec52c6` and
`d9b1e6c` that still governs current work is restored in its canonical or
role-appropriate document, while the superseded duplicate lifecycle and old
completion policy stay retired.

**Observed:** Compared `git diff 3ec52c6 d9b1e6c` and the current text for all
fourteen rewritten documents.

| Document | Restored / retained unique material | Result |
| --- | --- | --- |
| `AGENTS.md` | Load the CLI contract and only the method skills that match the work (`:8-11`); bounded coordinator reads versus permitted mutations (`:15-23`); single canonical work sequence (`:81-101`); sizing, exact approval, fixed receipt, verification invalidation and historical follow-ups. | pass |
| `README.md` | Scaffolding does not register agents; registration is required, manual and unverified, with a link to clean-project copy, existing-config merge, reload and catalog checks (`:90-92`). | pass |
| `.agents/roles/explorer.md` | Context-selected methods (`:8-11`), item/source revision confirmation (`:17-19`), evidence-only artifact and explicit no-source/no-design boundary (`:22-27`). | pass |
| `.agents/roles/orchestrator.md` | Context-selected methods (`:8-11`), bounded reads and allowed record mutations (`:15-19`), one parent scope map (`:22-23`), reconciliation of project facts into PRD/Architecture (`:27-28`). | pass |
| `.agents/roles/worker.md` | Context-selected methods (`:8-11`), `needs-decision` or report a wrong design rather than silently replace it (`:17-19`), owned-path/check/freeze rules and cache/vendor exclusion (`:20-27`). | pass |
| `.agents/roles/verifier.md` | Verifier brief only, expectations before producer conclusions, frozen actual target, full-versus-targeted distinction, per-criterion evidence and no repair (`:13-22`). The post-consolidation text already retained these facts; no new edit was required. | pass |
| `.agents/skills/decompose-work/SKILL.md` | Advisory focused-session, edit-volume, second-draft and standalone-task fallback cues (`:36-53`); one epic-only scope map and decomposition rationale (`:14-34`). | pass |
| `.agents/skills/fresh-session-handoff/SKILL.md` | Root-authority check, role brief confirmation, parent Design, exact epic review and handoff-is-not-proof semantics (`:12-27`). The post-consolidation text already retained these facts. | pass |
| `.agents/skills/install-workflow/SKILL.md` | Add-only/force distinction, command path and board refresh (`:23-27`), manual OpenCode registration as REQUIRED/MANUAL/UNVERIFIED with target-scoped V2 catalog checks (`:28-37`). | pass |
| `.agents/skills/verify-outcome/SKILL.md` | Independent expectation derivation, actual artifacts, normal/error/recovery/boundary/regression checks, per-criterion results, no repair and explicit limits (`:13-29`). The post-consolidation text already retained these facts. | pass |
| `.agents/skills/workflow-cli/SKILL.md` | New item starts in `plan`, approval moves to `todo`, phase/start flags do not rewind state, exact receipt/close separation and lexical ownership (`:44-75`); explicitly “not a second operating loop” (`:8-10`). | pass |
| `.workflow/PRD.md` | Explicit product non-goals restored at `:26-32`, including no server/auth/search/attachments/auto-commit/migration/automatic reopening/managed updater/host merger/fingerprint or per-attempt copies. | pass |
| `.workflow/Architecture.md` | Lightweight `console`, `yaml`, `filesystem`, `finder`, `lock` composition with no DI container, bundles or HTTP kernel (`:11-13`); storage, lifecycle, projection, distribution and limit facts retained. | pass |
| `docs/install.md` | Scaffold/manual-merge contract, runtime bootstrap, command path and board (`:23-59`); full OpenCode manual registration, clean/existing config, model/default, reload, scoped V2 catalog and unverified limits (`:61-92`); retained isolated smoke example (`:94-125`). | pass |

Searches found exactly one numbered lifecycle sequence, in `AGENTS.md`. Role
and skill documents point back to AGENTS and the CLI skill explicitly denies
being a second loop. No superseded mandatory-discovery task, warning-only
completion rule, routine re-approval loop or auto-reopen policy was restored.
Non-material condensation of old slogans (for example a subjective “read in one
sitting” claim and a no-MCP note) does not remove a current operating rule.

**Method/environment:** Git hunk comparison plus source reading on the frozen
worktree above.

**Limits:** Prose and static prompt inspection; no live LLM role startup.

### AC2 — truthful scaffold/manual handoff and preserving installer behavior

**Expected:** Installation output distinguishes a scaffold copy from harness
registration, states that registration remains required, manual and unverified,
and does not create or overwrite `opencode.json`, root host documents or
workflow history. Focused tests assert the wording and preservation.

**Observed:** Ran the real command in a temporary existing target with custom
`AGENTS.md`, `README.md`, `LICENSE`, `opencode.json` containing a sentinel
secret, workflow history/outputs and a target-only doc, then with `--force`.
Before/after SHA-256 snapshots showed the custom root documents, config,
workflow records, item history, outputs, target-only files and local skill were
byte-identical. The only ordinary kit file refreshed was the documented
`--force` surface; target-only files were not deleted. The config sentinel never
appeared in output. A separate clean target received no `opencode.json` but did
receive `docs/opencode.example.json`.

Actual fresh output included:

```text
This is a scaffold copy only; no automatic merges or deletions.
OpenCode/harness registration is REQUIRED, MANUAL and UNVERIFIED.
Clean project: copy .../docs/opencode.example.json to .../opencode.json.
Existing config: manually merge only the agents, default and model entries; never overwrite existing settings.
Expected: workflow-orchestrator primary/default; workflow-explorer, workflow-worker and workflow-verifier subagents.
workflow validate proves scaffold records only; the catalog is not a paid LLM proof.
```

`InstallHandoffTest.php` asserts the handoff wording, absence of automatic
`opencode.json`, no secret/config dump and preservation under both normal and
forced modes. `InstallScaffoldTest.php` asserts root/history/config preservation
and collision behavior.

**Method/environment:** Real `cli/bin/workflow install` probes under
`/tmp/opencode/e003-verify`, SHA-256 before/after, plus focused PHPUnit.

**Result:** pass.

**Limits:** This is isolated installation behavior, not a live manual merge into
a user's existing host configuration.

### AC3 — live target configuration and four-agent catalog

**Expected:** `prasowka-bot` has an active project OpenCode configuration whose
reviewed semantics match the kit example, and the target-scoped V2 catalog
exposes the orchestrator as a primary/default agent plus explorer, worker and
verifier as subagents.

**Observed:**

- `prasowka-bot/opencode.json` is byte-identical to the reviewed
  `docs/opencode.example.json` (`869a44d2…`).
- V2 `config.get` loaded the target document
  `/home/adam/Projects/prasowka-bot/opencode.json` and reported
  `default_agent=workflow-orchestrator` with all four workflow agents.
- `opencode api agent.list --param 'location[directory]=/home/adam/Projects/prasowka-bot'`
  returned:

| Agent | Mode | Hidden |
| --- | --- | --- |
| `workflow-orchestrator` | `primary` | false |
| `workflow-explorer` | `subagent` | false |
| `workflow-worker` | `subagent` | false |
| `workflow-verifier` | `subagent` | false |

- The catalog's role system text matches the target role files; target
  `.agents/roles/`, `cli/src/` and `cli/templates/` are byte-identical to the
  current source kit.
- Target `workflow validate` returned `OK (0 warnings)`; both source and target
  `deps` and `ready` commands exited 0. The target has no dependency edges yet,
  so target graph traversal itself was not exercised beyond the command path.
- `opencode.json` mtime `19:54:10 +0200` predates the `--force` install at about
  `20:09:32`; its content hash matches the reviewed example. No paid LLM call
  was made.

**Method/environment:** Live OpenCode v2.1.1 `config.get` and target-scoped
`agent.list`; direct hash and byte comparisons.

**Result:** pass.

**Limits:** The catalog proves registration and loading, not LLM startup,
model behavior or role independence.

### AC4 — validation, focused tests, full suite and repository hygiene

**Expected:** Source and target workflow validation pass; focused installer
tests and the full suite pass in the requested environment; `git diff --check`
is clean.

**Observed:**

```text
source:  cli/bin/workflow validate            OK (0 warnings), exit 0
target:  ./cli/bin/workflow validate          OK (0 warnings), exit 0
focused: 36 tests, 255 assertions, 0 failures
full:    702 tests, 5608 assertions, 0 failures
git diff --check                               exit 0
```

The one PHPUnit notice was the requested `--do-not-cache-result` option's own
deprecation, not a test failure.

**Method/environment:** `TMPDIR=/tmp/opencode` with
`cli/vendor/bin/phpunit --do-not-cache-result`; PHP 8.5.10 / PHPUnit 13.4.3.

**Result:** pass.

**Limits:** PHP 8.4.1, the documented minimum, was not executed. The suite does
not prove real harness/LLM startup.

### AC5 — independent evidence, preservation and no collateral change

**Expected:** Independent verification covers the repaired guidance, handoff and
live catalog; reports environment limits; and checks that no unrelated source,
commit, secret, vendor or target user-history change occurred.

**Observed:**

- `HEAD` remained `d9b1e6c`; no commit was created.
- Tracked changes versus `d9b1e6c` are confined to the E-003 guidance set,
  `cli/src/Console/Command/InstallCommand.php`, `cli/tests/Console/InstallScaffoldTest.php`
  and `docs/install.md`; the new installer test is expected E-003 work.
  `docs/handoff-compare.md` is a pre-existing untracked handoff artifact and was
  not modified by this verification.
- `cli/composer.lock` and tracked vendor content were unchanged; no vendor or
  `node_modules` path is tracked. Repository-wide secret-pattern inspection of
  the working files found none. `git diff --check` passed.
- Target-host preservation: no pre-install baseline hash was available. The
  strongest available evidence is that target `.workflow/PRD.md` and
  `Architecture.md` mtimes (`19:23:38`) predate the `--force` install at about
  `20:09:32`; E-001 records creation at `18:02:22` and approval at `18:10:35 UTC`
  (local mtime `20:10:35 +0200`, after install), and its history/outputs remain
  present. The installer excludes `.workflow/` entirely, and focused tests
  confirmed existing item history/outputs survive both modes. `opencode.json`
  predates install and still matches the reviewed example.
- Target root `AGENTS.md` and `README.md` are byte-identical to the current kit,
  so no host-specific customization remains; target role files also match.
- T-019/T-020 verifier reports were reviewed only after the independent checks
  above. They agree with the observed current state and were not used as sole
  evidence.

**Result:** pass.

**Limits:** The target is not a Git repository and no pre-install content hash
of E-001 or `opencode.json` was recorded. Preservation is therefore supported by
timestamps/content, installer exclusion behavior and executable preservation
tests rather than a historical target hash. Same-path content changes are not
fingerprinted by the product.

## Frozen hashes

### Source working tree

| Path | SHA-256 |
| --- | --- |
| `AGENTS.md` | `fc14adb23049f94a4b343949622bbdd40276a4d16aff72f7b872ffdbb1096f03` |
| `README.md` | `10719e59208ba6022473a721f083d860b817044a4824cd0d20c22ec8c3125de2` |
| `.agents/roles/explorer.md` | `0fb9e682c102d0382995108b3c11bdf3575df0f1b342bfe53de513aaab6b635b` |
| `.agents/roles/orchestrator.md` | `1c33e0acc414d858a9fd2401ca0895e7386ddc668a3c73b105c34dde61b1de2a` |
| `.agents/roles/verifier.md` | `1dc8d6e566ba6c875278f8f9d2d55f0a9eda27e21303d2c697f927e69ca235ac` |
| `.agents/roles/worker.md` | `580a5d1945dff2a5efb4336a2eff689756cb2459910ad24b62f75c58b3d9491b` |
| `.agents/skills/decompose-work/SKILL.md` | `08f4f4e46e1eb6f18c06c9bf41a07e96b56502adaf9ba48f7450db973dd0db76` |
| `.agents/skills/fresh-session-handoff/SKILL.md` | `2351fa7062b1f5973b1094af4384da9b2664704b20e89d9f222d098c364460c4` |
| `.agents/skills/install-workflow/SKILL.md` | `fd2c86f1659dd0e34285722561f5b07ef2bd793f1bf8b4be98caead1d7fa56c1` |
| `.agents/skills/verify-outcome/SKILL.md` | `12efa92e17e255bf500201199317d6db2d91f5b8e65edd929a609b2abceb1d9f` |
| `.agents/skills/workflow-cli/SKILL.md` | `9c0eab4f3f3748cb749650a29a1d748d0f6ff96139da8c645f284619d17f37fc` |
| `.workflow/PRD.md` | `d69066a01a231419837a69b0f8b25f0025a530b9c0f4d3cfae9653a500a68933` |
| `.workflow/Architecture.md` | `cb3fdd5b17b5a31cf5eb5a2d88cd20f37cd7f61b3b2d8a0bdba58dc10c99e0ec` |
| `docs/install.md` | `ab42f298cb0c12fc366fc9baa68bf761c723a9690afab8ef9842174a03add506` |
| `docs/opencode.example.json` | `869a44d2afba1024f818f121d266c481bb76f3f6339fa659f4f4b22c778a96f8` |
| `cli/src/Console/Command/InstallCommand.php` | `b91be1db5d55a403a10d2e688a0e940c89e011150cc916ef039f232be9743e60` |
| `cli/tests/Console/InstallHandoffTest.php` | `a30c49ae15994694ef166b9faf91fd73313799175b9be6764e0ed0721625bc49` |
| `cli/tests/Console/InstallScaffoldTest.php` | `317904afa0173074fcc8698ebc45d4d60909000efb6a9ee2aabafa6667dd3120` |

### Live target

| Path | SHA-256 |
| --- | --- |
| `prasowka-bot/AGENTS.md` | `fc14adb23049f94a4b343949622bbdd40276a4d16aff72f7b872ffdbb1096f03` |
| `prasowka-bot/README.md` | `10719e59208ba6022473a721f083d860b817044a4824cd0d20c22ec8c3125de2` |
| `prasowka-bot/opencode.json` | `869a44d2afba1024f818f121d266c481bb76f3f6339fa659f4f4b22c778a96f8` |
| `prasowka-bot/.workflow/items/E-001/item.md` | `3fcfdcc3bfd962124561d5285741a21123c59e53d7115a04150bed31711a9790` |
| `prasowka-bot/.agents/roles/explorer.md` | `0fb9e682c102d0382995108b3c11bdf3575df0f1b342bfe53de513aaab6b635b` |
| `prasowka-bot/.agents/roles/orchestrator.md` | `1c33e0acc414d858a9fd2401ca0895e7386ddc668a3c73b105c34dde61b1de2a` |
| `prasowka-bot/.agents/roles/verifier.md` | `1dc8d6e566ba6c875278f8f9d2d55f0a9eda27e21303d2c697f927e69ca235ac` |
| `prasowka-bot/.agents/roles/worker.md` | `580a5d1945dff2a5efb4336a2eff689756cb2459910ad24b62f75c58b3d9491b` |
| `prasowka-bot/cli/bin/workflow` | `323be940db4ceb592d08abec5d5ce884b04f0a3e5257be7bd1883c63558586fb` |

## Overall limits

- This verifies documentation/guidance, installer behavior, workflow records
  and OpenCode registration. It does not prove real LLM quality, startup,
  independence or role adherence.
- The target is not a Git repository; target preservation relies on timestamps,
  content, installer contract and executable tests, not a baseline history.
- PHP 8.4.1 and a native-mbstring-absent runtime were not executed; PHP 8.5.10
  was used.
- No paid model call was made. Catalog output is registration evidence only.
- Same-path artifact content has no content-fingerprint guarantee; this report
  freezes the named hashes above.

## Verdict

All five epic acceptance criteria pass. The guidance restoration, installer
handoff, live four-agent catalog, validation, focused/full tests and preservation
checks are consistent. No repair or target mutation was performed during
verification.
