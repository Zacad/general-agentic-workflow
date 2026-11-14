<?php

declare(strict_types=1);

namespace Workflow\Tests\Store;

use PHPUnit\Framework\Attributes\DataProvider;
use Workflow\Domain\FrontMatter;
use Workflow\Domain\Item;
use Workflow\Domain\Run;
use Workflow\Domain\WorkflowException;
use Workflow\Store\ItemStore;
use Workflow\Store\RunStore;
use Workflow\Tests\WorkflowTestCase;

final class RunAncestryMatrixTest extends WorkflowTestCase
{
    private const RUN = 'R-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private ItemStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = new ItemStore($this->root);
        foreach ([['E-001', 'epic', null], ['T-001', 'task', 'E-001'], ['S-001', 'subtask', 'T-001']] as [$id, $kind, $parent]) {
            $this->store->create(Item::fromArray(['id' => $id, 'kind' => $kind, 'parent' => $parent, 'title' => $id], ''));
        }
        (new RunStore($this->root))->create(Run::fromArray([
            'version' => 1, 'id' => self::RUN, 'autonomous' => true, 'goal' => 'Goal', 'success_criteria' => ['criterion'],
            'permissions' => ['actions' => ['decide'], 'roots' => ['E-001']], 'limits' => ['expires_at' => '2099-01-01T00:00:00Z', 'max_roots' => 1],
            'status' => 'active', 'status_reason' => null, 'recovery' => null,
            'created_at' => '2020-01-01T00:00:00Z', 'updated_at' => '2020-01-01T00:00:00Z',
        ]));
    }

    public static function malformedAncestry(): iterable
    {
        foreach (['missing', 'duplicate', 'wrong-id', 'wrong-kind', 'missing-parent', 'invalid-parent', 'cyclic', 'malformed'] as $case) {
            yield $case => [$case];
        }
    }

    #[DataProvider('malformedAncestry')]
    public function testUntrustedAncestryIsRejectedWithoutItemReplacement(string $case): void
    {
        $targetPath = $this->store->itemPath('S-001');
        $taskPath = $this->store->itemPath('T-001');
        $before = file_get_contents($targetPath);
        $parsed = FrontMatter::parse((string) file_get_contents($taskPath));
        $data = $parsed['data'];
        switch ($case) {
            case 'missing': unlink($taskPath); break;
            case 'duplicate':
                $dir = $this->root.'/.workflow/items/E-001/T-001'; mkdir($dir, 0777, true);
                file_put_contents($dir.'/item.md', FrontMatter::render($data, $parsed['body'])); break;
            case 'wrong-id': $data['id'] = 'T-999'; break;
            case 'wrong-kind': $data['kind'] = 'epic'; break;
            case 'missing-parent': unset($data['parent']); break;
            case 'invalid-parent': $data['parent'] = ['E-001']; break;
            case 'cyclic': $data['parent'] = 'S-001'; break;
            case 'malformed': file_put_contents($taskPath, "---\nid: [\n---\n"); break;
        }
        if (!in_array($case, ['missing', 'duplicate', 'malformed'], true)) {
            file_put_contents($taskPath, FrontMatter::render($data, $parsed['body']));
        }
        try { $this->store->decideForRun('S-001', self::RUN, 'must fail'); self::fail('Accepted '.$case); }
        catch (WorkflowException) { self::assertSame($before, file_get_contents($targetPath)); }
    }

    public function testGrandchildAndExplicitStandaloneRoot(): void
    {
        $this->store->decideForRun('S-001', self::RUN, 'grandchild');
        self::assertSame('grandchild', $this->store->load('S-001')->automatedEvents[0]['text']);
        $this->store->create(Item::fromArray(['id' => 'T-002', 'kind' => 'task', 'parent' => null, 'title' => 'Standalone', 'standalone_reason' => 'Independent'], ''));
        $data = (new RunStore($this->root))->load(self::RUN)->toArray();
        $data['id'] = 'R-'.str_repeat('b', 32); $data['permissions']['roots'] = ['T-002'];
        (new RunStore($this->root))->create(Run::fromArray($data));
        $this->store->decideForRun('T-002', $data['id'], 'standalone');
        self::assertSame('standalone', $this->store->load('T-002')->automatedEvents[0]['text']);
    }

    public function testExpiryEqualityAtInitialAndFinalAuthorization(): void
    {
        $path = $this->store->itemPath('S-001'); $before = file_get_contents($path);
        foreach ([['2099-01-01T00:00:00Z'], ['2098-12-31T23:59:59Z', '2099-01-01T00:00:00Z']] as $times) {
            $script = <<<'PHP'
namespace Workflow\Store {
    function time() { $index = $GLOBALS['index']++; return strtotime($GLOBALS['times'][min($index, count($GLOBALS['times']) - 1)]); }
}
namespace {
    require $argv[1]; $GLOBALS['index'] = 0; $GLOBALS['times'] = json_decode($argv[4], true);
    try { (new Workflow\Store\ItemStore($argv[2]))->decideForRun('S-001', $argv[3], 'expired'); exit(2); }
    catch (Workflow\Domain\WorkflowException $e) { echo $GLOBALS['index']; }
}
PHP;
            $proc = proc_open([PHP_BINARY, '-r', $script, dirname(__DIR__, 2).'/vendor/autoload.php', $this->root, self::RUN, json_encode($times, JSON_THROW_ON_ERROR)], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            fclose($pipes[0]); $out = stream_get_contents($pipes[1]); fclose($pipes[1]); $err = stream_get_contents($pipes[2]); fclose($pipes[2]);
            self::assertSame(0, proc_close($proc), $err);
            self::assertSame((string) count($times), $out);
            self::assertSame($before, file_get_contents($path));
            self::assertSame([], glob(dirname($path).'/.item.md.*.tmp'));
        }
    }

    public function testUnreadableAncestorDeniesWithoutReplacement(): void
    {
        $target = $this->store->itemPath('S-001'); $before = file_get_contents($target);
        $ancestor = $this->store->itemPath('T-001');
        $board = $this->root.'/.workflow/board.html'; file_put_contents($board, 'unchanged');
        $script = <<<'PHP'
namespace Workflow\Store {
    function file_get_contents($path, ...$args) {
        if ($path === $GLOBALS['unreadable']) return false;
        return \file_get_contents($path, ...$args);
    }
}
namespace {
    require $argv[1]; $GLOBALS['unreadable'] = $argv[4];
    try { (new Workflow\Store\ItemStore($argv[2]))->decideForRun('S-001', $argv[3], 'must fail'); exit(2); }
    catch (Workflow\Domain\WorkflowException $e) { echo $e->getMessage(); }
}
PHP;
        $proc = proc_open([PHP_BINARY, '-r', $script, dirname(__DIR__, 2).'/vendor/autoload.php', $this->root, self::RUN, $ancestor], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        fclose($pipes[0]); $out = stream_get_contents($pipes[1]); fclose($pipes[1]); $err = stream_get_contents($pipes[2]); fclose($pipes[2]);
        self::assertSame(0, proc_close($proc), $err);
        self::assertStringContainsString('Cannot read item ancestry: T-001', $out);
        self::assertSame($before, file_get_contents($target));
        self::assertSame('unchanged', file_get_contents($board));
    }

    public function testEveryActionRejectsAuthorityForEachOtherAction(): void
    {
        $path = $this->store->itemPath('T-001'); $before = file_get_contents($path);
        $actions = ['scope-approve', 'design-approve', 'decide', 'accept-review'];
        $record = (new RunStore($this->root))->load(self::RUN)->toArray();
        foreach ($actions as $index => $allowed) {
            $record['id'] = 'R-'.str_repeat(dechex($index + 1), 32);
            $record['permissions']['actions'] = [$allowed];
            (new RunStore($this->root))->create(Run::fromArray($record));
            foreach ($actions as $requested) {
                if ($requested === $allowed) continue;
                try {
                    match ($requested) {
                        'scope-approve' => $this->store->approveScopeForRun('T-001', $record['id']),
                        'design-approve' => $this->store->approveDesignForRun('T-001', $record['id'], 'design'),
                        'decide' => $this->store->decideForRun('T-001', $record['id'], 'decision'),
                        'accept-review' => $this->store->acceptReviewForRun('T-001', $record['id'], 'rationale'),
                    };
                    self::fail($requested.' accepted '.$allowed.' authority');
                } catch (WorkflowException $e) {
                    self::assertStringContainsString('authority denied', $e->getMessage());
                    self::assertSame($before, file_get_contents($path));
                }
            }
        }
    }
}
