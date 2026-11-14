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
use Workflow\Store\ItemStore;
use Workflow\Store\RunStore;
use Workflow\Tests\WorkflowTestCase;

final class RunAcceptanceMatrixTest extends WorkflowTestCase
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
            'permissions' => ['actions' => ['accept-review'], 'roots' => ['E-001']],
            'limits' => ['expires_at' => '2099-01-01T00:00:00Z', 'max_roots' => 1], 'status' => 'active',
            'status_reason' => null, 'recovery' => null, 'created_at' => '2020-01-01T00:00:00Z', 'updated_at' => '2020-01-01T00:00:00Z',
        ]));
    }

    public static function states(): iterable
    {
        foreach (['E-001', 'T-001', 'S-001'] as $id) {
            foreach (Status::cases() as $status) yield $id.' '.$status->value => [$id, $status];
        }
    }

    #[DataProvider('states')]
    public function testOnlyVerifiedTaskOrSubtaskInReviewMayBeAccepted(string $id, Status $status): void
    {
        $this->store->recordReceipt($id, ['role' => 'verifier', 'status' => 'done', 'at' => '2026-01-01T00:00:00Z'], [], '- verified');
        if ($status === Status::Done) {
            $this->store->close($id, Status::Done, 'fixture', '2026-01-01T00:00:00Z');
        } else {
            $this->store->mutate($id, static function (Item $item) use ($status): void { $item->status = $status; });
        }
        $path = $this->store->itemPath($id); $before = file_get_contents($path); $old = $this->store->load($id);
        $app = new Application(); $app->setAutoExit(false); $out = new BufferedOutput();
        $code = $app->run(new ArrayInput(['--project' => $this->root, 'command' => 'close', 'id' => $id, '--run' => self::RUN, '--reason' => 'automated rationale', '--json' => true]), $out);
        if ($id === 'E-001' || $status !== Status::Review) {
            self::assertNotSame(0, $code, $out->fetch()); self::assertSame($before, file_get_contents($path));
            return;
        }
        self::assertSame(0, $code, $out->fetch());
        $after = $this->store->load($id);
        self::assertSame(Status::Done, $after->status);
        self::assertSame($old->verifiedAt, $after->verifiedAt);
        self::assertSame($old->lastResult, $after->lastResult);
        self::assertSame('accept-review', $after->automatedEvents[0]['action']);
        self::assertSame(self::RUN, $after->automatedEvents[0]['run_id']);
    }
}
