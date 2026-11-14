<?php

declare(strict_types=1);

namespace Workflow\Tests\Console;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workflow\Console\Application;
use Workflow\Domain\Item;
use Workflow\Domain\Run;
use Workflow\Domain\Status;
use Workflow\Domain\WorkflowException;
use Workflow\Store\ItemStore;
use Workflow\Store\RunStore;
use Workflow\Tests\WorkflowTestCase;

final class RunGateRaceTest extends WorkflowTestCase
{
    private const RUN = 'R-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private ItemStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = new ItemStore($this->root);
        $this->store->create(Item::fromArray(['id' => 'E-001', 'kind' => 'epic', 'parent' => null, 'title' => 'Root'], ''));
        $this->store->create(Item::fromArray(['id' => 'T-001', 'kind' => 'task', 'parent' => 'E-001', 'title' => 'Task', 'outcome' => 'before'], ''));
        (new RunStore($this->root))->create(Run::fromArray([
            'version' => 1, 'id' => self::RUN, 'autonomous' => true, 'goal' => 'Goal', 'success_criteria' => ['criterion'],
            'permissions' => ['actions' => ['scope-approve', 'accept-review'], 'roots' => ['E-001']],
            'limits' => ['expires_at' => '2099-01-01T00:00:00Z', 'max_roots' => 1], 'status' => 'active',
            'status_reason' => null, 'recovery' => null, 'created_at' => '2020-01-01T00:00:00Z', 'updated_at' => '2020-01-01T00:00:00Z',
        ]));
        $this->store->approveScopeForRun('T-001', self::RUN);
        $this->store->recordReceipt('T-001', ['role' => 'verifier', 'status' => 'done', 'at' => '2026-01-01T00:00:00Z'], [], '- verified');
        $this->store->mutate('T-001', static function (Item $item): void { $item->status = Status::Review; });
    }

    public static function races(): iterable
    {
        yield 'material scope' => ['scope'];
        yield 'review state' => ['review'];
    }

    private function command(array $args): array
    {
        $app = new Application(); $app->setAutoExit(false); $out = new BufferedOutput();
        $status = $app->run(new ArrayInput(['--project' => $this->root] + $args), $out);
        return [$status, $out->fetch()];
    }

    #[DataProvider('races')]
    public function testGateIsReevaluatedAfterContendingWriterCommits(string $case): void
    {
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
    try {
        (new Workflow\Store\ItemStore($argv[2]))->mutate('T-001', function ($item) use ($argv) {
            if ($argv[3] === 'scope') $item->outcome = 'after';
            else $item->status = Workflow\Domain\Status::InProgress;
        });
        echo "COMMITTED\n";
    } catch (\Throwable $e) { fwrite(STDERR, $e->getMessage()); exit(2); }
}
PHP;
        $proc = proc_open([PHP_BINARY, '-r', $script, dirname(__DIR__, 2).'/vendor/autoload.php', $this->root, $case], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertSame("LOCKED\n", fgets($pipes[1]));
        $action = function () use ($case): void {
            if ($case === 'scope') $this->store->approveScopeForRun('T-001', self::RUN, true);
            else $this->store->acceptReviewForRun('T-001', self::RUN, 'rationale');
        };
        try { $action(); self::fail('Concurrent action acquired lock'); }
        catch (WorkflowException $e) { self::assertStringContainsString('retry', $e->getMessage()); }
        fwrite($pipes[0], "commit\n"); fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]); fclose($pipes[1]); $err = stream_get_contents($pipes[2]); fclose($pipes[2]);
        self::assertSame(0, proc_close($proc), $err); self::assertSame("COMMITTED\n", $out);
        $path = $this->store->itemPath('T-001'); $before = file_get_contents($path);
        try { $action(); self::fail('Stale gate accepted'); }
        catch (WorkflowException $e) { self::assertStringContainsString($case === 'scope' ? 'material' : 'in review', $e->getMessage()); }
        self::assertSame($before, file_get_contents($path));
        $args = $case === 'scope'
            ? ['command' => 'approve', 'id' => 'T-001', '--delta' => true]
            : ['command' => 'close', 'id' => 'T-001', '--confirmation' => 'accepted'];
        [$code] = $this->command($args); self::assertNotSame(0, $code);
        self::assertSame($before, file_get_contents($path));
    }
}
