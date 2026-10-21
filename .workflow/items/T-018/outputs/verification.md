# T-018 — independent full verification

**Verdict: pass for all nine acceptance criteria and the supplied Design.** No
frozen deliverable was repaired or changed. Full suite: **699 tests, 5,567
assertions**, exit 0; focused suite: **6 tests, 195 assertions**, exit 0. Both
report the requested PHPUnit option's deprecation, not a product failure.
Read-only project validation: **exit 0, `OK (0 warnings)`**.

## Authority, independence and environment

- First command: `cli/bin/workflow brief T-018 --for verifier`. Expectations
  below came from its nine acceptance criteria, item Design and parent Design,
  before inspecting the deliverables. Loaded `verify-outcome`, root `AGENTS.md`,
  `workflow-cli`, `critical-reasoning` and `clear-communication`.
- This is a **full verification**, not a recheck of a producer-selected subset.
  No T-018 producer receipt, default/worker brief, worker report or worker word
  count was consulted. Dependency verification reports were not used as proof.
- Repository: `/var/home/adam/Projects/general-agentic-workflow`;
  comparison HEAD: `3ec52c68cd683cee8c7a5ca4efd74dbdb769ec3c`.
- **R:** read-only inspection of the working-tree deliverables and implementing
  commands, compared with the approved packet and Git HEAD where appropriate.
- **C:** Linux, PHP **8.5.10**, native iconv **and native mbstring present**;
  PHPUnit **13.4.3**. Independent fixtures and retained public examples live in
  `/tmp/opencode/T018-independent-vz83wr2w/`. The final test runs set both
  `TMPDIR` and `WORKFLOW_TEST_ROOT` to that directory.
- **I:** copied scaffold at
  `/tmp/opencode/T018-independent-vz83wr2w/workflow-install.rezeNb`;
  runtime bootstrap used the existing kit `cli/tools/composer.phar`, Composer
  **2.10.3**, because plain `composer` was not on PATH. Installation occurred
  only in this isolated copy, never in the frozen source CLI.
- **Not available / not claimed:** an actual PHP 8.4.1 environment, a clean
  native-mbstring-absent runtime, or real installed LLM/harness startup. Static
  prompt/reference checks and CLI role labels cannot establish actual model
  loading, human consent or independent role execution.

## Per-criterion results

### AC1 — one canonical operating contract and explicit installed-role loading

**Expected:** root AGENTS is authoritative; all four installed roles explicitly
load it even when used separately; missing, incomplete or conflicting host rules
trigger reviewed manual merging, not replacement.

**Observed:** `AGENTS.md:3–10` establishes authority. Each role's opening paragraph
explicitly says to read root AGENTS in full, including standalone use, and to
surface absent/incomplete/conflicting rules without assuming away host authority.
`docs/install.md:34–39` prohibits dispatch before the merge is reviewed. The
installed copies contain these instructions; their harness example references
resolve to the four real role files, and named method skills exist. A separate
host fixture preserved a deliberately incomplete host AGENTS, root documents,
configuration, gitignores, records and evidence under both default and forced
installation; output requested the manual merge.

**Method/environment:** R; direct reads of all roles and command implementation;
C/I installed-file and reference assertions; default/force host fixture; focused
and full tests. **Result: pass. Limits:** instruction loading/conflict handling
was checked statically, not by starting four real LLM roles. No automatic semantic
host-rule merger is asserted or required.

### AC2 — independent epic-hosted discovery without a mandatory discovery child

**Expected:** discovery independence is a separate explorer run, not extra
hierarchy; an optional discovery child needs its own useful outcome.

**Observed:** `AGENTS.md:59–64` states both distinctions explicitly.
`decompose-work/SKILL.md:18–22` selects a child only for an independently useful
deliverable. No active role or other method skill reinstates a mandatory discovery
task. An isolated CLI fixture recorded an explorer-labelled evidence path directly
on E-001 while E-001 was the sole item; implementation children were created later.

**Method/environment:** R policy/duplicate-instruction review; C epic evidence
fixture and role-packet checks. **Result: pass. Limits:** the fixture is synthetic
CLI evidence, not a claim that a real independent explorer ran in that fixture;
the actual operating rule requires that separate run.

### AC3 — staged exact approval, reapproving only affected changed scopes

**Expected:** approve epic/discovery first; obtain evidence or justify none;
review exact new children afterward; unchanged epic/siblings retain approval.

**Observed:** `AGENTS.md:52–78` specifies the sequence, exact record projections
and scope-only approval. The complete README example reviews the epic before
children exist, approves the epic once, gives a justified fixed-input
no-exploration reason, then reviews and approves the new child. Independent
fixture checks retained the epic approval after evidence and parent Design
updates. Changing T-001 acceptance made only T-001 stale; T-002 and the epic
remained current. Review JSON preserved a literal tag-containing criterion and a
separate comma-containing criterion as distinct strings.

**Method/environment:** R; execution of the exact public shell block in C;
additional exact-review/approval fixture; implementing ScopeOutput/approval
contract and full tests. **Result: pass. Limits:** these are projections and
scope-hash behavior, not authentication of a user's approval. README expressly
states that limitation.

### AC4 — one scope map in epic Design

**Expected:** the epic owns dimensions, coverage, children, dependencies,
ownership, integration, unknowns and sizing rationale; children reference that
map instead of copying it.

**Observed:** `AGENTS.md:66–72` defines the sole canonical map, coverage matrix
and decomposition rationale. `decompose-work` supplies a boundary-selection
procedure, not another map or lifecycle. The orchestrator references AGENTS;
the other roles consume parent Design through their briefs. README puts its
single-child map and no-subtask rationale in E-001 Design, and T-001 Design says
`Follow parent Design`. The supplied parent Design already contains the full
T-018 context, coverage and broad-one-writer rationale.

**Method/environment:** R review of all 14 prose files and related unchanged
method skills; C exact README execution and parent/child projection assertions.
**Result: pass. Limits:** no new decomposition or retrospective rewrite of the
completed dependencies was performed.

### AC5 — small work without extra hierarchy or new gates; deliberate exception

**Expected:** one item suffices for a small outcome; exact short approval,
one-line Plan/Design and a justified no-exploration route; normally independent
verification, with an explicit unverified exception rather than invented proof.

**Observed:** `AGENTS.md:102–112` says exactly this and makes sizing signals
judgment, not gates. `AGENTS.md:44–48` distinguishes advisory process warnings
from structural/current-verification requirements. The focused small-work test
uses one standalone task, explicit approval, short Plan/Design and no extra
hierarchy. Independent CLI checks rejected normal producer-only closure,
accepted `close --force --reason ...`, and retained null `verified_at` and
`last_result` with a recorded `Verification exception: explicit --force`.
Validation emitted the expected unverified-completion warning for that fixture.

**Method/environment:** R; C focused test, separate exception/closure checks,
and full lifecycle regressions. **Result: pass. Limits:** force is not verifier
success and does not waive structure/history protection. Short Plan/Design are
operating expectations, not newly enforced CLI gates.

### AC6 — historical completion, explicit follow-ups and evolving source

**Expected:** material edits to done records, reopening, new producer work and
unsuccessful verification are refused; new work uses an explicitly approved
follow-up, without automatic successors/migration. Preserve historical records
and evidence, not an impossible permanent freeze on evolving project source.

**Observed:** `AGENTS.md:145–161` and the product mechanism descriptions agree
with ItemLifecycle. Independent checks first established current proof, changed
Design and observed closure refusal, then reverified and closed. Attempts to
reopen, change ownership/Design, submit new worker work, submit a failed verifier
receipt or cancel a done item with force all failed with explicit follow-up
guidance, byte-identical original records and no generated successor. Historical
notes and priority corrections remained possible.

The same fixture then **explicitly created, reviewed and approved T-003**, with
done T-001 as a dependency and the same `src/Greeting.php` ownership. It was ready
without overlap from the done owner. Source bytes evolved from `v1\n` to `v2\n`
under T-003, which was separately checked and closed; T-001's original item record
and evidence bytes remained unchanged. No snapshot/fingerprint infrastructure
was needed. Thus historical proof remains a past observation, not a claim that
the current source still contains v1. AGENTS' follow-up instruction and
Architecture's conventional, non-content-hashed freeze do not introduce a
permanent source-path lock.

**Method/environment:** R lifecycle/document contract comparison; C 82-command
contract fixture, negative byte-preservation checks and explicit same-path
follow-up; full lifecycle/store regressions. **Result: pass. Limits:** same-path
external content edits are not detected by the CLI. Preserving historical
records/evidence and reporting/reverifying changes remains a convention; a new
item does not retroactively certify old proof against today's source.

### AC7 — complete isolated public examples, valid hierarchy and retained history

**Expected:** execute the complete README quickstart and install smoke safely,
with real commands, valid task parent/standalone reason, repository-relative
artifacts, actual check/receipt/close flow and no history deletion.

**Observed:** independently extracted each marked `sh` block unchanged and ran
it with `bash -euo pipefail` from the kit root, redirecting only temporary-root
environment variables. Both exited 0 and validated with zero warnings.

- README root: `workflow-demo.YnrGgz` under the evidence directory. E-001 and
  its task T-001 remained present and done, with exact `hello\n` output,
  project-relative artifact references, worker then verifier receipt and
  separate closure. The epic also has its integration verifier/close flow.
- Install root: `workflow-install.rezeNb`. Only its standalone T-001 was
  created; it retains its standalone reason and cancelled history. The source
  project's items were not imported. Twelve distributable prose files match
  the frozen kit; project PRD/Architecture were initialized from templates,
  not copied from the source project's `.workflow/`.

**Method/environment:** C exact block execution and independent file/record
assertions; I scaffold inspection; focused example tests and full suite.
**Result: pass. Limits:** comments are genuine human/role checkpoints for real
use; automated walkthrough execution simulates those checkpoints and is not
proof of human consent or actual independent LLM sessions. Independent example
roots and their histories were retained; PHPUnit removes only its disposable
test fixtures through the existing test teardown.

### AC8 — implemented runtime, installation, packets and optional command flags

**Expected:** actual named commands/options/role values; correct runtime versus
development requirements; safe scaffold/force/manual merge semantics; optional
phase/start behavior and exhaustive all output; exact role packets.

**Observed:** machine-readable CLI definitions confirm all **20 documented
commands** and their documented options. Global `--project` exists. Actual
`--for orchestrator`, `explorer`, `worker`, `verifier` all execute in text and
JSON; invented `--for coordinator` is rejected. Brief and result defaults really
are worker. Verifier packets omit producer/progress sentinels while retaining
acceptance, Plan/Design, parent Design, decisions and artifact references;
explorer output targets are repository-relative.

Separate checks show phase-only does not start work, start-only does not select
a new phase, content-only updates do not rewind state, and invalid flags or dual
content sources leave both item and board bytes unchanged. The focused test
also exercises the documented file-plus-phase/start design example.

An independent **50-item** projection fixture checked all status groups:
8 drafts, 12 active, 21 ready, 7 waiting, 7 decisions (a subset of active),
1 done and 1 cancelled. Default text is capped; `status --all` text/JSON
contains every expected row and terminal group; default JSON's included groups
are uncapped. `ready` caps text at 20 while `ready --all` and JSON include 21.

Lock-file inspection finds runtime extension requirement **iconv**, Symfony PHP
floor **8.4.1**, and the mbstring polyfill. The documented development extension
list exactly matches the locked development requirements: dom, filter, json,
libxml, mbstring, phar, tokenizer, xmlwriter. In I, actual
`composer install --no-dev` installed 15 locked runtime packages with no updates
or removals; the installed binary's `init` and `validate` succeeded.
`check-platform-reqs --no-dev` succeeded for PHP/iconv. Default/force host-copy
checks also preserved held symlinks, custom root instructions/configuration,
target-only files, evidence and history; only the reviewed ordinary kit file
was refreshed by force.

**Method/environment:** R command definitions/implementations and Composer lock;
C role, flag, projection and host fixtures; I actual bootstrap and installed CLI;
full regressions including scaffold containment/type/link cases.
**Result: pass. Limits:** PHP 8.4.1 and an actual clean mbstring-absent deployment
were not available. Passing on PHP 8.5.10 with native mbstring is not evidence
that those two environments were executed. Hostile concurrent filesystem
replacement and real harness/schema startup were not tested or promised.

### AC9 — product documents, corrected references and unowned preservation

**Expected:** PRD/Architecture explain product mechanisms and limits, not a
competing operating loop; no stale fake paths/task-parent examples or warning-only
closure claims; historical/user-owned files are untouched and identified as
historical where actively referenced.

**Observed:** PRD is organized around product requirements and evidence;
Architecture names real components and boundaries. Neither restates the full
operating sequence. There is no second protocol document, discovery-item gate,
automatic reopen/successor policy, snapshot/fingerprint feature or managed
merger. Roles are short responsibility packets; method skills do not reinstate
the removed lifecycle. Closure is described as requiring current proof unless
deliberately forced, not merely issuing a warning. All **6 relative Markdown
links** and **16 architecture source references** resolve; FrontMatter's real
location is `cli/src/Domain/FrontMatter.php`. The public task has an epic parent;
the smoke task has its explicit standalone reason. README and Architecture label
`docs/design-notes.md` historical, and PRD says those notes are not authority.

**Method/environment:** R full owned-prose and focused-test inspection, active
method-skill/template review, reference existence checks, independent HEAD word
counts and preservation hashes; C/I examples and installed references.
**Result: pass. Limits:** historical notes and the user's handoff comparison were
not rewritten or treated as current operating authority; no repository-wide
slimming claim is made from the selected prose set.

## Executed checks and reproducibility

Final isolated test commands, from `cli/` (with the evidence directory supplied
as both temporary-root environment variables):

```sh
php vendor/bin/phpunit tests/Console/ProtocolExamplesTest.php --do-not-cache-result --display-phpunit-deprecations
php vendor/bin/phpunit --do-not-cache-result --display-phpunit-deprecations
```

Both exit 0. Exactly one runner deprecation in each: `--do-not-cache-result` is
deprecated for PHPUnit 14 in favor of `--do-not-record-test-run-history`.
The requested flag was still used. Final read-only `cli/bin/workflow validate`
at 2026-10-21T06:44:40Z returned `OK (0 warnings)`.

Supplemental raw logs/fixtures are retained under
`/tmp/opencode/T018-independent-vz83wr2w/`: `focused-isolated.log`,
`full-isolated.log`, `quickstart.log`, `install-smoke.log`,
`runtime-bootstrap.log`, `runtime-platform.log`, `validation-final.log`,
`cli-definition.json`, `contract-transcript.json`,
`host-install-transcript.json`, `status-all-transcript.json` and preservation/
reference/count manifests. This report carries the durable findings; temporary
logs are supplemental, not a new workflow snapshot system. Verifier harness
format/placeholder assumptions were corrected locally after inspecting actual
output; no checked product file was changed to obtain passing results.

## Independent word counts and frozen hashes

Counts use Python `str.split()` whitespace tokens, including Markdown/front
matter/code fences; baseline bytes come directly from `git show HEAD:<path>`.
The **14 owned prose files total 9,831 → 6,778 words**, a reduction of **3,053
(31.05%)**. The new test is excluded from that prose total. Installation guidance
grows by 201 words; this is not a claim that every file became shorter.

SHA-256 identifies the inspected freeze, not new product freshness metadata.
The initial 2026-10-21T06:35:16Z hashes and final pre-report check agree for every
frozen path.

| Frozen path | HEAD words | Frozen words | SHA-256 |
| --- | ---: | ---: | --- |
| `AGENTS.md` | 1951 | 1185 | `41f62a68e6150325f1ff20d4ca0d532fcb32d9f9c7d999734d386eda833aaf7a` |
| `README.md` | 1075 | 714 | `facf28fec93859916354f772146bbe0298fb5ab17dcba73b5cadaf8e94826cce` |
| `docs/install.md` | 406 | 607 | `809844b1ffea990aa2d267f09d072a1eb4caf1b95990af8756a592697ab2fa97` |
| `.agents/roles/orchestrator.md` | 695 | 234 | `b020e5a62bd421b28c1f86bfaca3aebd37ee135413f4053f2093ba601b55b6ca` |
| `.agents/roles/explorer.md` | 261 | 181 | `19558d1e9300fa0ea0cee0c1530e587251d6659c1ae18b0212dc756e989f6173` |
| `.agents/roles/worker.md` | 299 | 203 | `aebfc14120125ba56bdb918d33f10796f55d67e8de55eb2b86ccf297041d017e` |
| `.agents/roles/verifier.md` | 331 | 206 | `1dc8d6e566ba6c875278f8f9d2d55f0a9eda27e21303d2c697f927e69ca235ac` |
| `.agents/skills/workflow-cli/SKILL.md` | 902 | 690 | `888fbaaa5281c749b467c3b9835f0667af8fea259d356b4f0cea1138ca0f799b` |
| `.agents/skills/decompose-work/SKILL.md` | 574 | 299 | `06f0798dfbe3182e5b45fbb34482e7b8dd70fa21b0e06078250759c6f4069254` |
| `.agents/skills/install-workflow/SKILL.md` | 304 | 216 | `1ab42e362ba360da78171213b06f6ee84c5454f4d7ebe83c1f45cf55d508f974` |
| `.agents/skills/fresh-session-handoff/SKILL.md` | 199 | 165 | `2351fa7062b1f5973b1094af4384da9b2664704b20e89d9f222d098c364460c4` |
| `.agents/skills/verify-outcome/SKILL.md` | 308 | 244 | `12efa92e17e255bf500201199317d6db2d91f5b8e65edd929a609b2abceb1d9f` |
| `.workflow/PRD.md` | 1363 | 831 | `a5f625209b208ca9efe0d602026c3cbc92739218b540a5599fd5d7503757a983` |
| `.workflow/Architecture.md` | 1163 | 1003 | `639194264736917bd55a056c3037cf838b1d4cb78c3d44403353c91fa6fe8a7a` |
| `cli/tests/Console/ProtocolExamplesTest.php` | new | 862 | `de6b1b3354bc2a54158d90be60f595da3a955252383e898f94c619c27ece0e31` |

## Historical and unowned preservation

- All **87** initially hashed frozen/tracked/user-owned baseline files were
  unchanged at the final pre-report check. The only repository write by this
  verifier is this assigned `verification.md`; no project record mutation,
  board refresh, code/doc repair or history deletion was invoked.
- `git diff --exit-code HEAD -- docs/design-notes.md .workflow/items` returned
  0. Independently compared bytes of tracked E-001 and T-001–T-008 histories
  with `git show HEAD:<path>`: all equal. The newer E-002/T-009–T-017 records
  are not in HEAD; their hashes/mtimes were recorded without displaying their
  receipts, and all modification times predate this verification session.
- Historical `docs/design-notes.md` is HEAD-equal, SHA-256
  `5c92ad00ad4f7fbc9802b988749622ccace7eff7854e0f0176e92da01eb31811`.
- User-owned, untracked `docs/handoff-compare.md` remained session-byte-identical,
  SHA-256 `cbfbe0c15de0e97cb7a5b21b2ac180652e421abaf7bd94cfca55f9a39dcbf963`.
  No HEAD comparison is claimed for that untracked file.

**Next:** coordinator records this verifier receipt explicitly with
`--role verifier`, then closes T-018 in a separate CLI call if the frozen target
has remained unchanged. No force exception or target repair is needed.
