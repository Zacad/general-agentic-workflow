# T-012 independent full verification

**Result: fail.** The candidate's own structural checks and ordinary atomic refusals work, but persistence drops malformed legacy records from the graph before examining affected relationships. A kind repair can therefore invalidate an existing child's parent relationship without refusal. Creating a missing parent can likewise introduce a wrong-kind relationship, including through `new`.

## Authority, target and environment

- First command: `cli/bin/workflow brief T-012 --for verifier`. Expectations came from its six acceptance criteria and Design, before inspecting implementation. Producer receipts/conclusions were not read. Loaded `verify-outcome` and read root `AGENTS.md`.
- This is **full verification of the assigned eight-file T-012 target**, not a fix-only recheck. It does not certify other items or disjoint targets assigned to another verifier.
- Environment E1, used for every criterion below: Linux; repository `/var/home/adam/Projects/general-agentic-workflow`; PHP CLI 8.5.10 NTS; PHPUnit 13.4.3; existing `cli/vendor/autoload.php` and locked dependencies. Native `dom`, `libxml`, `mbstring`, `iconv`, `xml`, `xmlwriter` and `pcntl` were available. No installation, dependency update or prerequisite repair occurred.
- All PHP probes and PHPUnit runs used `TMPDIR=/tmp/opencode`. Fixtures were independent temporary projects; real project records and code were not modified. The sole repository output written by this verifier is this report.
- Source/test SHA-256 values below were identical before inspection and after execution (final comparison at 2026-10-21 05:11:54 UTC; rechecked after writing this report). No core writer was authorized during verification. Existing unrelated working-tree changes were preserved.

## Per-criterion results

All methods ran in E1. `pass` below is bounded by the stated observations; the overall outcome remains failed because criterion 3 and the affected-graph Design contract fail.

| AC | Expected | Method | Observed | Result and limits |
| --- | --- | --- | --- | --- |
| 1 | Create/save/mutate reject candidate ID-kind mismatches, epic parents, wrong-kind task parents, and absent/wrong-kind subtask parents. | Inspect pure checks and all three store routes; execute focused PHPUnit and an independent 16-case × 3-route persistence matrix. | Correct refusal for mismatched kind, path/newline IDs, epic parents, task→task/subtask, subtask→null/epic/subtask/missing/self. Valid epic/task/subtask setup persisted. | **pass** for candidate checks; reverse-child incompleteness is AC3, not evidence that all affected records were checked. |
| 2 | Reject missing references, self-parent/dependency edges and multi-item cycles before persistence. | Actual store/command persistence probes; pure independent reachability oracle over all 65,536 directed four-node graphs, including self-edges; compare candidate and full validation against the oracle. | Missing/self references and three-item cycles refused. All 327,680 oracle comparisons agreed, including overlapping legacy cycles not involving the candidate. | **pass** on loaded graph records. Exhaustive scope is four nodes, not arbitrary graph size; graph construction can omit malformed records as described below. |
| 3 | Changing a parent's kind cannot silently invalidate existing children; affected reverse relationships must also be checked when a missing parent is created. | Normal reverse-child rejection/repair controls, then independent adversarial fixtures whose child has a directory/front-matter identity mismatch or invalid status. Execute save, mutate, create, and CLI `new`; compare full diagnostics before/after. | Normal loaded-child checks work. A mismatched-directory child is skipped: both save and mutate change the parent kind and introduce a new `task parent must be a epic, got task` error. Create/save/`new` similarly resolve a missing parent to the wrong kind. Invalid-status affected children are also skipped. | **fail**, reproduced across five routes/cases with directory mismatch and two more with invalid status. Exact reproduction below. |
| 4 | Refused store/command writes preserve original item bytes and leave no new records. | Recursive snapshots of file bytes and directory paths for 48 independent refused store writes; focused tests; fresh independent command project with board included. | All 48 matrix attempts were refused with identical item trees and no temporary/new record directories. Thirteen independent invalid commands plus a long-cycle `set` left all item/board bytes and paths unchanged. | **pass for actually refused writes**. The AC3 cases are wrongly accepted and do change/create records; this must not be mistaken for complete atomic structural safety. OS/I/O failure injection was not performed. |
| 5 | Valid edits/repairs remain possible despite unrelated legacy structural defects. | Raw invalid-subtask, missing-reference, two-node-cycle, invalid-status and directory-mismatch fixtures; create/save/mutate a separate valid item; repair parent and dependency structure; compare untouched bytes. | Valid operations succeeded even with a reachable legacy cycle not involving the candidate. Own parent/cycle repairs succeeded while unrelated bad records remained byte-identical. A parent-kind repair that also repairs a normal subtask child succeeded despite a separate invalid subtask. | **pass** for these cases. Allowing unrelated defects does not authorize ignoring a child's relationship to the changed parent (AC3). |
| 6 | Full validate still reports legacy defects; standalone-reason guidance is warning-only, not a new workflow gate. | Inspect shared full validation; run ItemValidator tests and independent full diagnostics/application `validate`; exercise approval/phase/Plan/Design omissions through all store routes and commands. | Full diagnostics retained legacy identity, hierarchy, missing/self references and cycles; malformed status/directory identity were reported. A standalone task was created without a reason at phase verify; warning-only `validate` exited 0. Unapproved in-progress writes remained allowed, while subsequent `validate` exited 1 for approval and emitted Plan/Design warnings. | **pass** for retained diagnostics/guidance. An invalid-status record reports that parsing error rather than all downstream structural errors. |

## Executed checks

### Supplied regressions, independently executed

```sh
env TMPDIR=/tmp/opencode cli/vendor/bin/phpunit -c cli/phpunit.xml \
  --do-not-cache-result --display-phpunit-deprecations \
  --filter 'Workflow\\Tests\\(Validate\\(ItemValidatorTest|StructureValidatorTest)|Store\\StructureWriteTest|Console\\StructureCommandTest)'
```

Exit 0: **109 tests, 424 assertions**, no test failures. The sole runner deprecation was the explicitly requested `--do-not-cache-result` option (replacement suggested by PHPUnit: `--do-not-record-test-run-history`). It was not hidden or repaired. These tests also exercise mutate-ID immutability, save-as-create validation, graph rereading after the mutate callback, and refusal when the existing write lock is held. Source inspection confirms create/save/mutate reach `persist()` while holding that lock and perform structural checks before rendering or atomic writing.

`git diff --check -- <the eight target paths>` exited 0. The untracked new targets were inspected directly and are covered by hashes/tests; this command alone does not check untracked files.

### Independent probes

- **Store matrix:** `/tmp/opencode/t012-store-matrix-f4b8caa806`; 48/48 attempts correctly refused with exact recursive item-tree preservation. Cases were wrong kind, path ID, trailing-newline ID, epic parent, task→task/subtask/missing/self parent, subtask→null/epic/subtask/missing/self parent, missing/self dependency and three-node cycle; each ran through create/save/mutate with an extra title edit that must not persist.
- **Command probes:** `/tmp/opencode/t012-commands-f694dc1413`; 22/22 checks passed (four setup operations, thirteen invalid command snapshots, one long-cycle snapshot, and four warning/no-gate checks). Snapshots included all `.workflow` bytes/directories except lock files, including an existing generated board. Invalid commands exited 2; standalone warning-only validation exited 0.
- **Graph oracle:** for every 16-bit adjacency matrix on four task IDs, independent iterative reachability decided whether each candidate can reach itself and whether any cycle exists. Compared those expectations with `validateCandidate()` for four candidates and `validate()` once: **327,680 comparisons, zero disagreement**. Items were well-formed and parentless; standalone warnings did not count as graph errors.
- **Repair/no-gate controls:** retained under `/tmp/opencode/t012-independent-7240f069fb/{legacy-edits,no-gates,repair-save,repair-mutate,reverse-normal-save,reverse-normal-mutate,reverse-create-create,reverse-create-save}`. Successful controls and unchanged unrelated bytes are described per criterion above.
- Probe-harness limit: the first combined probe's command branch omitted creation of its temporary project directory, so its command/board comparisons were invalid and discarded. This was a verifier-fixture error, not a target defect. The command branch was rerun from a new, explicitly created directory with the 22 valid checks above. The clean 48-case store matrix was also rerun separately. No failed target was repaired.

## Failure: affected children disappear from the persistence graph

**Location:** `cli/src/Store/ItemStore.php:191-200` catches every `WorkflowException` from `load()` and omits that record. `load()` throws for a directory/front-matter ID mismatch at lines 90-92, even though the record has parseable kind and parent information. `StructureValidator::validateCandidate()` checks reverse children at lines 44-49, but never receives the omitted child.

The distinction is material: a broken child's *other* fields do not make its relationship to the changed parent unrelated. This is not a request to run full workflow validation on every write or to block unrelated repairs.

### Exact independent reproduction

Run from the repository root with the existing dependencies. It creates only isolated `/tmp/opencode` fixtures and leaves them intact:

```sh
env TMPDIR=/tmp/opencode php <<'PHP'
<?php
require getcwd().'/cli/vendor/autoload.php';
use Workflow\Domain\{Item, Kind, FrontMatter};
use Workflow\Store\ItemStore;
use Workflow\Validate\ItemValidator;
use Workflow\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

$base = sys_get_temp_dir().'/t012-repro-'.bin2hex(random_bytes(5));
mkdir($base, 0777, true);
function fixture(ItemStore $s, string $dir, string $id, Kind $kind, ?string $parent = null): void {
    $i = Item::fromArray(['id'=>$id, 'kind'=>$kind->value, 'parent'=>$parent], '');
    mkdir(dirname($s->itemPath($dir)), 0777, true);
    file_put_contents($s->itemPath($dir), FrontMatter::render($i->toArray(), ''));
}
foreach (['save', 'mutate'] as $route) {
    $s = new ItemStore($base.'/kind-change-'.$route);
    fixture($s, 'T-010', 'T-010', Kind::Epic);
    fixture($s, 'T-020', 'T-021', Kind::Task, 'T-010');
    $before = (new ItemValidator($s))->validate()->errors;
    $hash = hash_file('sha256', $s->itemPath('T-010'));
    if ($route === 'save') {
        $s->save(Item::fromArray(['id'=>'T-010', 'kind'=>'task'], ''));
    } else {
        $s->mutate('T-010', fn(Item $i) => $i->kind = Kind::Task);
    }
    echo json_encode([$s->root(), $route, $before,
        (new ItemValidator($s))->validate()->errors,
        'parent bytes changed' => $hash !== hash_file('sha256', $s->itemPath('T-010'))]), "\n";
}
foreach (['create', 'save'] as $route) {
    $s = new ItemStore($base.'/new-parent-'.$route);
    fixture($s, 'S-020', 'S-021', Kind::Subtask, 'E-010');
    $s->$route(Item::fromArray(['id'=>'E-010', 'kind'=>'epic'], ''));
    echo json_encode([$s->root(), $route, 'created'=>$s->exists('E-010'),
        (new ItemValidator($s))->validate()->errors]), "\n";
}
function cmd(string $r, array $args): array {
    $a = new Application(); $a->setAutoExit(false); $o = new BufferedOutput();
    $exit = $a->run(new ArrayInput(['--project'=>$r] + $args), $o);
    return [$exit, $o->fetch()];
}
$root = $base.'/new-parent-command'; mkdir($root, 0777, true);
cmd($root, ['command'=>'init']); $s = new ItemStore($root);
fixture($s, 'S-020', 'S-021', Kind::Subtask, 'E-001');
echo json_encode([$root, cmd($root, ['command'=>'new', 'title'=>'Affected parent', '--kind'=>'epic']),
    (new ItemValidator($s))->validate()->errors]), "\n";
PHP
```

**Expected:** kind-changing save/mutate refuse the newly invalid child relationship and preserve parent bytes. Creating the missing epic by create/save/`new` refuses the affected subtask→epic relationship and leaves no new parent record.

**Observed, retained at `/tmp/opencode/t012-repro-a7f030973d`:**

| Fixture | Actual result |
| --- | --- |
| `kind-change-save` | No exception; parent bytes changed. Before: `T-010: id does not match kind epic.` After: that error is replaced by **`T-021: task parent must be a epic, got task.`** Directory mismatch remains in both states. |
| `kind-change-mutate` | Same silent kind change and newly invalid child relationship. |
| `new-parent-create` | `E-010/item.md` created; child diagnostic changes from missing parent to **`S-021: subtask parent must be a task, got epic.`** |
| `new-parent-save` | Same acceptance and new record. |
| `new-parent-command` | CLI exit **0**, `E-001/item.md` created; output includes `warning: board not refreshed: Item S-020 declares id S-021.` followed by `Created E-001 (epic) ...`; full validation reports the newly wrong-kind subtask parent. |

Additional independent variations retained in `/tmp/opencode/t012-independent-7240f069fb/affected-invalid-status-{save,mutate}` use a correctly identified task child with `status: not-a-status`, otherwise parseable parent/kind fields. Both routes likewise change the parent's kind without refusal; full validation reports the invalid status. This is the same omission mechanism, not a separate proposed redesign.

No production file or failed fixture was repaired. The bounded next action is to address omitted **affected** children and add the missing regression while preserving unrelated-defect repair behavior, then freeze a new target for verification.

## Frozen SHA-256 manifest

```text
3a0023fd98cb250f94f0edf08d1f5c041d141c3b8411bd8429bb25e541ea3e37  cli/src/Validate/StructureValidator.php
5de0f88352aad82c8bad82f73b5108ffbd3831ecb1af100706038f770cde1feb  cli/src/Validate/ItemValidator.php
d324d2967e6f75240695e683deba2a76c8aee1ec8f50ac9e6c647b7bea648a74  cli/src/Store/ItemStore.php
60cc90d08b6ad22eef595e17a219e442c1bf205f555477b50f754429934dc2a4  cli/src/Console/Command/NewCommand.php
b82e554687ea34497f2103cfa30df029886861288539048c7dc1de6c10c5888b  cli/tests/Validate/ItemValidatorTest.php
959d7ab520fbf833c8da2ff3a518d62793c787fec86bc78fb10085982ec05fdc  cli/tests/Validate/StructureValidatorTest.php
10a3a33bbe4262fbdd7e9c52314c11a956df71f29d02b3e240c0124548126548  cli/tests/Store/StructureWriteTest.php
54e2be5fcb118667976bf3fe1c2aa02c8904a9ade0c9faeb437971c4a2166299  cli/tests/Console/StructureCommandTest.php
```

## Remaining limits

- No project-wide suite, alternate PHP version/OS, true simultaneous multi-process writes, or disk-full/fsync/rename failure injection was run. The four assigned test files, real store/application persistence and existing lock rejection were executed; unrelated items are not certified.
- Failed-target artifacts remain in place. Temporary fixture paths are local evidence, not portable dependencies; the reproduction above recreates the failure independently.
- This verifier did not modify item state, record a verifier receipt, close T-012 or repair implementation. The orchestrator must record the failed receipt, not a successful verification.
