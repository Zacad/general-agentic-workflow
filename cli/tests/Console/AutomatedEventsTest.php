<?php

declare(strict_types=1);

namespace Workflow\Tests\Console;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workflow\Console\Application;
use Workflow\Domain\FrontMatter;
use Workflow\Domain\InvalidItemException;
use Workflow\Domain\Item;
use Workflow\Domain\Run;
use Workflow\Domain\Status;
use Workflow\Domain\WorkflowException;
use Workflow\Store\ItemStore;
use Workflow\Store\RunStore;
use Workflow\Tests\WorkflowTestCase;

final class AutomatedEventsTest extends WorkflowTestCase
{
    private const RUN = 'R-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private ItemStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = new ItemStore($this->root);
        $this->store->create(Item::fromArray(['id' => 'E-001', 'kind' => 'epic', 'parent' => null, 'title' => 'Root'], ''));
        $this->store->create(Item::fromArray(['id' => 'T-001', 'kind' => 'task', 'parent' => 'E-001', 'title' => 'Task'], "## Decisions\n\nNarrative\n"));
        (new RunStore($this->root))->create(Run::fromArray([
            'version' => 1, 'id' => self::RUN, 'autonomous' => true, 'goal' => 'Goal', 'success_criteria' => ['criterion'],
            'permissions' => ['actions' => ['decide', 'accept-review'], 'roots' => ['E-001']],
            'limits' => ['expires_at' => '2099-01-01T00:00:00Z', 'max_roots' => 1], 'status' => 'active',
            'status_reason' => null, 'recovery' => null, 'created_at' => '2020-01-01T00:00:00Z', 'updated_at' => '2020-01-01T00:00:00Z',
        ]));
    }

    private function command(array $args): array
    {
        $app = new Application(); $app->setAutoExit(false); $out = new BufferedOutput();
        $status = $app->run(new ArrayInput(['--project' => $this->root] + $args), $out);
        return [$status, $out->fetch()];
    }

    private function verify(): void
    {
        $this->store->recordReceipt('T-001', ['role' => 'verifier', 'status' => 'done', 'at' => '2026-01-01T00:00:00Z'], [], '- verified');
    }

    public function testDecisionInvalidatesProofAndAcceptanceRetainsFreshProof(): void
    {
        $this->verify();
        $text = "decision\n<error>literal</error>";
        [$code, $output] = $this->command(['command' => 'decide', 'id' => 'T-001', 'decision' => $text, '--run' => self::RUN]);
        self::assertSame(0, $code, $output);
        $item = $this->store->load('T-001');
        self::assertNull($item->verifiedAt);
        self::assertSame($text, $item->automatedEvents[0]['text']);
        self::assertSame('Narrative', $item->section('Decisions'));
        [$code, $brief] = $this->command(['command' => 'brief', 'id' => 'T-001', '--for' => 'verifier', '--json' => true]);
        self::assertSame(0, $code, $brief);
        self::assertSame($text, json_decode($brief, true, flags: JSON_THROW_ON_ERROR)['automated_decisions'][0]['text']);
        [$code, $briefText] = $this->command(['command' => 'brief', 'id' => 'T-001', '--for' => 'verifier']);
        self::assertSame(0, $code); self::assertStringContainsString('<error>literal</error>', $briefText);
        self::assertStringContainsString('automated decision:', $briefText);
        self::assertStringContainsString(self::RUN, $briefText);
        $this->store->mutate('T-001', static function (Item $item): void { $item->status = Status::Review; });
        $path = $this->store->itemPath('T-001'); $before = file_get_contents($path);
        [$code] = $this->command(['command' => 'close', 'id' => 'T-001', '--run' => self::RUN, '--reason' => 'Machine rationale']);
        self::assertNotSame(0, $code); self::assertSame($before, file_get_contents($path));
        $this->verify();
        foreach ([['--force' => true], ['--direct' => true], ['--confirmation' => 'yes'], ['--status' => 'cancelled']] as $extra) {
            $before = file_get_contents($path);
            [$code] = $this->command(['command' => 'close', 'id' => 'T-001', '--run' => self::RUN, '--reason' => 'Machine rationale'] + $extra);
            self::assertNotSame(0, $code); self::assertSame($before, file_get_contents($path));
        }
        [$code, $output] = $this->command(['command' => 'close', 'id' => 'T-001', '--run' => self::RUN, '--reason' => 'Machine rationale', '--json' => true]);
        self::assertSame(0, $code, $output);
        self::assertSame(self::RUN, json_decode($output, true, flags: JSON_THROW_ON_ERROR)['automated_acceptance']['run_id']);
        $item = $this->store->load('T-001');
        self::assertSame(Status::Done, $item->status); self::assertNotNull($item->verifiedAt);
        self::assertSame('accept-review', $item->automatedEvents[1]['action']);
        [$code, $brief] = $this->command(['command' => 'brief', 'id' => 'T-001', '--for' => 'verifier', '--json' => true]);
        self::assertSame(0, $code); self::assertStringNotContainsString('Machine rationale', $brief);
        [$code, $review] = $this->command(['command' => 'review', 'id' => 'E-001', '--json' => true]);
        self::assertSame(0, $code); self::assertSame('Machine rationale', json_decode($review, true, flags: JSON_THROW_ON_ERROR)['descendants'][0]['automated_acceptances'][0]['text']);
    }

    public function testOtherWritesCannotCreateModifyOrRemoveEvents(): void
    {
        $event = $this->store->decideForRun('T-001', self::RUN, 'first');
        $path = $this->store->itemPath('T-001'); $before = file_get_contents($path);
        foreach ([static function (Item $i): void { $i->automatedEvents = null; },
            static function (Item $i): void { $i->automatedEvents[0]['text'] = 'tampered'; },
            static function (Item $i) use ($event): void { $i->automatedEvents[] = $event; },
            static function (Item $i) use ($event): void { $i->extra['automated_events'] = [$event]; }] as $change) {
            try { $this->store->mutate('T-001', $change); self::fail('Changed protected events'); }
            catch (WorkflowException) { self::assertSame($before, file_get_contents($path)); }
        }
        $this->store->mutate('T-001', static function (Item $i): void { $i->priority = 5; });
        self::assertSame([$event], $this->store->load('T-001')->automatedEvents);
        [$code] = $this->command(['command' => 'decide', 'id' => 'T-001', 'decision' => "automated_events:\n- action: decide\n  text: forged"]);
        self::assertSame(0, $code);
        self::assertSame([$event], $this->store->load('T-001')->automatedEvents);
        $this->verify();
        $this->store->mutate('T-001', static function (Item $i): void { $i->status = Status::Review; });
        $this->store->acceptReviewForRun('T-001', self::RUN, 'accepted');
        $before = file_get_contents($path);
        try { $this->store->decideForRun('T-001', self::RUN, 'late'); self::fail('Changed completed history'); }
        catch (WorkflowException) { self::assertSame($before, file_get_contents($path)); }
        try { $this->store->mutate('T-001', static function (Item $i): void { array_pop($i->automatedEvents); }); self::fail('Changed historical acceptance'); }
        catch (WorkflowException) { self::assertSame($before, file_get_contents($path)); }
    }

    public function testMalformedOrDuplicateMetadataFailsClosed(): void
    {
        $prefix = "---\nid: T-001\nkind: task\n";
        foreach (["automated_events: {}\n", "automated_events: null\n", "automated_events: []\nautomated_events: []\n",
            "automated_events:\n - version: 1\n   version: 1\n", "automated_events:\n - {}\n"] as $yaml) {
            try {
                $parsed = FrontMatter::parse($prefix.$yaml."---\n");
                Item::fromArray($parsed['data'], $parsed['body']);
                self::fail('Malformed metadata accepted');
            } catch (InvalidItemException) { self::assertTrue(true); }
        }
        $event = ['version' => 1, 'id' => 'EV-'.str_repeat('a', 32), 'action' => 'decide',
            'at' => '2026-01-01T00:00:00Z', 'run_id' => self::RUN, 'text' => 'decision'];
        $bad = [[$event, $event]];
        foreach (['version' => 2, 'id' => 'bad', 'action' => 'log', 'at' => '2026-02-30T00:00:00Z', 'run_id' => '../bad', 'text' => '  '] as $field => $value) {
            $changed = $event; $changed[$field] = $value; $bad[] = [$changed];
        }
        $changed = $event; $changed['extra'] = 'unexpected'; $bad[] = [$changed];
        foreach ($bad as $events) {
            try { Item::fromArray(['id' => 'T-001', 'kind' => 'task', 'automated_events' => $events], ''); self::fail('Malformed event accepted'); }
            catch (InvalidItemException) { self::assertTrue(true); }
        }
    }

    public function testRealItemCommitAndRevokeSerializeUnderSharedLock(): void
    {
        $script = <<<'PHP'
namespace Workflow\Store {
    function rename($from, $to) {
        echo "READY\n"; flush();
        if (trim(fgets(STDIN)) !== 'commit') { exit(9); }
        return \rename($from, $to);
    }

}
namespace {
    require $argv[1];
    try { (new Workflow\Store\ItemStore($argv[2]))->decideForRun('T-001', $argv[3], 'committed decision'); echo "COMMITTED\n"; }
    catch (\Throwable $e) { fwrite(STDERR, $e->getMessage()); exit(2); }
}
PHP;
        $proc = proc_open([PHP_BINARY, '-r', $script, dirname(__DIR__, 2).'/vendor/autoload.php', $this->root, self::RUN], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($proc);
        self::assertSame("READY\n", fgets($pipes[1]));
        $runs = new RunStore($this->root);
        $revoke = static function (Run $run): Run {
            $data = $run->toArray(); $data['status'] = 'revoked'; $data['status_reason'] = 'stop';
            return Run::fromArray($data);
        };
        try { $runs->mutate(self::RUN, $revoke); self::fail('Lock contention did not block revoke'); }
        catch (WorkflowException $exception) { self::assertStringContainsString('retry', $exception->getMessage()); }
        fwrite($pipes[0], "commit\n"); fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
        $error = stream_get_contents($pipes[2]); fclose($pipes[2]);
        self::assertSame(0, proc_close($proc), $error);
        self::assertSame("COMMITTED\n", $output);
        $runs->mutate(self::RUN, $revoke);
        self::assertSame('committed decision', $this->store->load('T-001')->automatedEvents[0]['text']);
        $path = $this->store->itemPath('T-001'); $before = file_get_contents($path);
        try { $this->store->decideForRun('T-001', self::RUN, 'late'); self::fail('Revoked run accepted'); }
        catch (WorkflowException) { self::assertSame($before, file_get_contents($path)); }
    }

    public function testFinalAuthorityRecheckRefusesExpiryAfterPreparation(): void
    {
        $path = $this->store->itemPath('T-001'); $before = file_get_contents($path);
        $script = <<<'PHP'
namespace Workflow\Store {
    function fclose($handle) {
        $closed = \fclose($handle);
        $data = json_decode(file_get_contents($GLOBALS['runpath']), true, flags: JSON_THROW_ON_ERROR);
        $data['limits']['expires_at'] = '2021-01-01T00:00:00Z';
        file_put_contents($GLOBALS['runpath'], json_encode($data, JSON_THROW_ON_ERROR));
        return $closed;
    }
}
namespace {
    require $argv[1]; $GLOBALS['runpath'] = $argv[2].'/.workflow/runs/'.$argv[3].'.json';
    try { (new Workflow\Store\ItemStore($argv[2]))->decideForRun('T-001', $argv[3], 'never committed'); exit(2); }
    catch (Workflow\Domain\WorkflowException $e) { echo $e->getMessage(); }
}
PHP;
        $proc = proc_open([PHP_BINARY, '-r', $script, dirname(__DIR__, 2).'/vendor/autoload.php', $this->root, self::RUN], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        fclose($pipes[0]); $out = stream_get_contents($pipes[1]); fclose($pipes[1]); $err = stream_get_contents($pipes[2]); fclose($pipes[2]);
        self::assertSame(0, proc_close($proc), $err);
        self::assertStringContainsString('authority denied', $out);
        self::assertSame($before, file_get_contents($path));
        self::assertSame([], glob(dirname($path).'/.item.md.*.tmp'));
    }

    public function testBlockedAuthorityCanRecoverWithoutChangingDeniedItemOrBoard(): void
    {
        $runs = new RunStore($this->root);
        $path = $this->store->itemPath('T-001'); $before = file_get_contents($path);
        $board = $this->root.'/.workflow/board.html'; file_put_contents($board, 'unchanged');
        foreach (['blocked', 'interrupted', 'limit-exceeded'] as $status) {
            $runs->mutate(self::RUN, static function (Run $run) use ($status): Run {
                $data = $run->toArray(); $data['status'] = $status; $data['status_reason'] = 'pause'; $data['recovery'] = 'inspect';
                return Run::fromArray($data);
            });
            [$code, $output] = $this->command(['command' => 'decide', 'id' => 'T-001', 'decision' => 'denied', '--run' => self::RUN]);
            self::assertNotSame(0, $code);
            self::assertStringNotContainsString('decision recorded', strtolower($output));
            self::assertSame($before, file_get_contents($path));
            self::assertSame('unchanged', file_get_contents($board));
            $runs->mutate(self::RUN, static function (Run $run): Run {
                $data = $run->toArray(); $data['status'] = 'active'; $data['status_reason'] = null; $data['recovery'] = 'inspected';
                return Run::fromArray($data);
            });
        }
        foreach (['R-'.str_repeat('b', 32), '../bad'] as $run) {
            [$code, $output] = $this->command(['command' => 'decide', 'id' => 'T-001', 'decision' => 'denied', '--run' => $run]);
            self::assertNotSame(0, $code);
            self::assertStringNotContainsString('decision recorded', strtolower($output));
            self::assertSame($before, file_get_contents($path));
            self::assertSame('unchanged', file_get_contents($board));
        }
        [$code, $output] = $this->command(['command' => 'decide', 'id' => 'T-001', 'decision' => 'after recovery', '--run' => self::RUN]);
        self::assertSame(0, $code, $output);
        self::assertSame('after recovery', $this->store->load('T-001')->automatedEvents[0]['text']);
    }

    public function testWrongActionAndUnrelatedRootCannotAuthorizeEvents(): void
    {
        $this->store->create(Item::fromArray(['id' => 'E-002', 'kind' => 'epic', 'parent' => null, 'title' => 'Other'], ''));
        $this->store->create(Item::fromArray(['id' => 'T-002', 'kind' => 'task', 'parent' => 'E-002', 'title' => 'Other task'], ''));
        $path = $this->store->itemPath('T-002'); $before = file_get_contents($path);
        try { $this->store->decideForRun('T-002', self::RUN, 'outside root'); self::fail('Other tree authorized'); }
        catch (WorkflowException) { self::assertSame($before, file_get_contents($path)); }
        $newId = 'R-'.str_repeat('b', 32);
        $data = (new RunStore($this->root))->load(self::RUN)->toArray();
        $data['id'] = $newId; $data['permissions']['actions'] = ['scope-approve'];
        (new RunStore($this->root))->create(Run::fromArray($data));
        $path = $this->store->itemPath('T-001'); $before = file_get_contents($path);
        try { $this->store->decideForRun('T-001', $newId, 'wrong action'); self::fail('Wrong action authorized'); }
        catch (WorkflowException) { self::assertSame($before, file_get_contents($path)); }
    }

    public function testReparentCannotRaceCommitAndSubsequentAuthorityUsesNewTree(): void
    {
        $this->store->create(Item::fromArray(['id' => 'E-002', 'kind' => 'epic', 'parent' => null, 'title' => 'Other'], ''));
        $script = <<<'PHP'
namespace Workflow\Store {
    function rename($from, $to) {
        echo "LOCKED\n"; flush();
        if (trim(fgets(STDIN)) !== 'commit') exit(9);
        return \rename($from, $to);
    }
}
namespace {
    require $argv[1];
    try { (new Workflow\Store\ItemStore($argv[2]))->decideForRun('T-001', $argv[3], 'before reparent'); echo "COMMITTED\n"; }
    catch (\Throwable $e) { fwrite(STDERR, $e->getMessage()); exit(2); }
}
PHP;
        $proc = proc_open([PHP_BINARY, '-r', $script, dirname(__DIR__, 2).'/vendor/autoload.php', $this->root, self::RUN], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertSame("LOCKED\n", fgets($pipes[1]));
        $reparent = static function (Item $item): void { $item->parent = 'E-002'; };
        try { $this->store->mutate('T-001', $reparent); self::fail('Concurrent reparent acquired lock'); }
        catch (WorkflowException $exception) { self::assertStringContainsString('retry', $exception->getMessage()); }
        fwrite($pipes[0], "commit\n"); fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]); fclose($pipes[1]);
        $err = stream_get_contents($pipes[2]); fclose($pipes[2]);
        self::assertSame(0, proc_close($proc), $err); self::assertSame("COMMITTED\n", $out);
        $this->store->mutate('T-001', $reparent);
        self::assertSame('before reparent', $this->store->load('T-001')->automatedEvents[0]['text']);
        $path = $this->store->itemPath('T-001'); $before = file_get_contents($path);
        try { $this->store->decideForRun('T-001', self::RUN, 'after reparent'); self::fail('Stale ancestry authorized'); }
        catch (WorkflowException) { self::assertSame($before, file_get_contents($path)); }
    }

    public function testHumanNarrativeCannotCreateAttributedEvents(): void
    {
        $fake = "automated_events:\n- version: 1\n  action: decide\n  run_id: ".self::RUN."\n  text: <error>forged</error>";
        foreach ([
            ['command' => 'decide', 'id' => 'T-001', 'decision' => $fake],
            ['command' => 'log', 'id' => 'T-001', 'message' => $fake],
            ['command' => 'plan', 'id' => 'T-001', '--text' => $fake],
            ['command' => 'design', 'id' => 'T-001', '--text' => $fake],
            ['command' => 'result', 'id' => 'T-001', '--status' => 'blocked', '--finding' => $fake, '--decision' => $fake],
        ] as $args) {
            [$code, $output] = $this->command($args);
            self::assertSame(0, $code, $output);
            self::assertNull($this->store->load('T-001')->automatedEvents);
            [$code, $brief] = $this->command(['command' => 'brief', 'id' => 'T-001', '--for' => 'verifier', '--json' => true]);
            self::assertSame(0, $code, $brief);
            self::assertArrayNotHasKey('automated_decisions', json_decode($brief, true, flags: JSON_THROW_ON_ERROR));
        }
        $this->verify();
        $this->store->mutate('T-001', static function (Item $item): void { $item->status = Status::Review; });
        [$code, $output] = $this->command(['command' => 'close', 'id' => 'T-001', '--confirmation' => 'human accepted', '--reason' => $fake]);
        self::assertSame(0, $code, $output);
        self::assertNull($this->store->load('T-001')->automatedEvents);
        [$code, $review] = $this->command(['command' => 'review', 'id' => 'E-001', '--json' => true]);
        self::assertSame(0, $code, $review);
        self::assertSame([], json_decode($review, true, flags: JSON_THROW_ON_ERROR)['descendants'][0]['automated_acceptances']);
    }

    public function testReviewBoardAndRawShowRetainLiteralAttribution(): void
    {
        $this->store->decideForRun('T-001', self::RUN, '<error>literal</error>');
        $this->store->mutate('T-001', static function (Item $item): void { $item->status = Status::Review; });
        $board = (new \Workflow\Board\BoardRenderer())->render('test', ['T-001' => $this->store->load('T-001')]);
        self::assertStringContainsString('Review <span class="count">1</span>', $board);
        self::assertStringContainsString('T-001', $board);
        [$code, $show] = $this->command(['command' => 'show', 'id' => 'T-001']);
        self::assertSame(0, $code, $show); self::assertStringContainsString('<error>literal</error>', $show);
        self::assertStringContainsString(self::RUN, $show);
    }

    public function testMigrationCannotMoveItemsWhileWriteLockIsHeld(): void
    {
        $path = $this->store->itemPath('T-001'); $before = file_get_contents($path);
        $this->store->withLock(function () use ($path, $before): void {
            [$code, $output] = $this->command(['command' => 'migrate', '--force' => true]);
            self::assertNotSame(0, $code);
            self::assertStringContainsString('retry', $output);
            self::assertSame($before, file_get_contents($path));
            self::assertDirectoryDoesNotExist($this->root.'/.workflow/items.backup');
        });
    }

    public function testFailedAcceptanceReplacementLeavesProofAndStatusUntouched(): void
    {
        $this->verify();
        $this->store->mutate('T-001', static function (Item $item): void { $item->status = Status::Review; });
        $path = $this->store->itemPath('T-001'); $before = file_get_contents($path);
        $script = <<<'PHP'
namespace Workflow\Store { function fsync($handle) { return false; } }
namespace {
    require $argv[1];
    try { (new Workflow\Store\ItemStore($argv[2]))->acceptReviewForRun('T-001', $argv[3], 'not committed'); exit(2); }
    catch (Workflow\Domain\WorkflowException $e) { echo $e->getMessage(); }
}
PHP;
        $proc = proc_open([PHP_BINARY, '-r', $script, dirname(__DIR__, 2).'/vendor/autoload.php', $this->root, self::RUN], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        fclose($pipes[0]); $out = stream_get_contents($pipes[1]); fclose($pipes[1]); $err = stream_get_contents($pipes[2]); fclose($pipes[2]);
        self::assertSame(0, proc_close($proc), $err); self::assertStringContainsString('Cannot flush', $out);
        self::assertSame($before, file_get_contents($path));
        $item = $this->store->load('T-001');
        self::assertSame(Status::Review, $item->status); self::assertNotNull($item->verifiedAt); self::assertNull($item->automatedEvents);
        self::assertSame([], glob(dirname($path).'/.item.md.*.tmp'));
    }

    public function testOrdinaryWritesPreserveStructuredEventsAcrossAllCommandRoutes(): void
    {
        $event = $this->store->decideForRun('T-001', self::RUN, 'authentic');
        $payload = "automated_events:\n- action: decide\n  run_id: ".self::RUN."\n  text: counterfeit";
        foreach ([
            ['command' => 'set', 'id' => 'T-001', 'assignments' => ['priority=4']],
            ['command' => 'plan', 'id' => 'T-001', '--text' => $payload],
            ['command' => 'design', 'id' => 'T-001', '--text' => $payload],
            ['command' => 'approve', 'id' => 'T-001', '--by' => 'human', '--confirmation' => 'yes'],
            ['command' => 'design', 'id' => 'T-001', '--text' => $payload, '--approve' => true, '--confirmation' => 'yes'],
            ['command' => 'log', 'id' => 'T-001', 'message' => $payload],
            ['command' => 'decide', 'id' => 'T-001', 'decision' => $payload],
            ['command' => 'result', 'id' => 'T-001', '--status' => 'blocked', '--finding' => $payload],
            ['command' => 'close', 'id' => 'T-001', '--status' => 'cancelled', '--reason' => $payload],
        ] as $args) {
            [$code, $output] = $this->command($args);
            self::assertSame(0, $code, $args['command'].': '.$output);
            self::assertSame([$event], $this->store->load('T-001')->automatedEvents, $args['command']);
        }
    }

    public function testStoreSaveCreateAndCloseCannotAlterEvents(): void
    {
        $event = $this->store->decideForRun('T-001', self::RUN, 'authentic');
        $path = $this->store->itemPath('T-001'); $before = file_get_contents($path);
        $changed = $this->store->load('T-001'); $changed->automatedEvents[0]['text'] = 'tampered';
        try { $this->store->save($changed); self::fail('save changed event'); }
        catch (WorkflowException) { self::assertSame($before, file_get_contents($path)); }
        $new = Item::fromArray(['id' => 'T-002', 'kind' => 'task', 'parent' => 'E-001', 'title' => 'forged', 'automated_events' => [$event]], '');
        foreach (['create', 'save'] as $route) {
            try { $this->store->{$route}($new); self::fail($route.' created event'); }
            catch (WorkflowException) { self::assertFalse($this->store->exists('T-002')); }
        }
        try {
            $this->store->close('T-001', Status::Cancelled, 'reason', '2026-01-01T00:00:00Z', false, static function (Item $item, string $reason): string {
                $item->automatedEvents = null; return $reason;
            });
            self::fail('close callback removed event');
        } catch (WorkflowException) { self::assertSame($before, file_get_contents($path)); }
        $this->store->recordReceipt('T-001', ['role' => 'worker', 'status' => 'blocked', 'at' => '2026-01-01T00:00:00Z'], [], 'progress');
        self::assertSame([$event], $this->store->load('T-001')->automatedEvents);
    }

    public function testMalformedRunFileDeniesWithoutChangingItemOrBoardAndCanRecover(): void
    {
        $runPath = $this->root.'/.workflow/runs/'.self::RUN.'.json'; $valid = file_get_contents($runPath);
        $path = $this->store->itemPath('T-001'); $before = file_get_contents($path);
        $board = $this->root.'/.workflow/board.html'; file_put_contents($board, 'unchanged');
        foreach (['{"version":1,"version":1}', '{"version":', '[]'] as $raw) {
            file_put_contents($runPath, $raw);
            [$code, $output] = $this->command(['command' => 'decide', 'id' => 'T-001', 'decision' => 'denied', '--run' => self::RUN]);
            self::assertNotSame(0, $code); self::assertStringNotContainsString('decision recorded', strtolower($output));
            self::assertSame($before, file_get_contents($path)); self::assertSame('unchanged', file_get_contents($board));
        }
        file_put_contents($runPath, $valid);
        [$code, $output] = $this->command(['command' => 'decide', 'id' => 'T-001', 'decision' => 'after recovery', '--run' => self::RUN]);
        self::assertSame(0, $code, $output);
        self::assertSame('after recovery', $this->store->load('T-001')->automatedEvents[0]['text']);
    }
}
