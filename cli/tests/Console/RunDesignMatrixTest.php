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

final class RunDesignMatrixTest extends WorkflowTestCase
{
    private const RUN = 'R-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private ItemStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = new ItemStore($this->root);
        foreach ([['E-001', 'epic', null], ['T-001', 'task', 'E-001'], ['S-001', 'subtask', 'T-001']] as [$id, $kind, $parent]) {
            $this->store->create(Item::fromArray(['id' => $id, 'kind' => $kind, 'parent' => $parent, 'title' => $id], "## Plan\n\nP\n\n## Design\n\nOld\n\n## Decisions\n\nD\n\n## Progress\n\nG\n"));
        }
        (new RunStore($this->root))->create(Run::fromArray([
            'version' => 1, 'id' => self::RUN, 'autonomous' => true, 'goal' => 'Goal', 'success_criteria' => ['criterion'],
            'permissions' => ['actions' => ['scope-approve', 'design-approve', 'decide'], 'roots' => ['E-001']],
            'limits' => ['expires_at' => '2099-01-01T00:00:00Z', 'max_roots' => 1], 'status' => 'active',
            'status_reason' => null, 'recovery' => null, 'created_at' => '2020-01-01T00:00:00Z', 'updated_at' => '2020-01-01T00:00:00Z',
        ]));
    }

    private function design(string $id): array
    {
        $app = new Application(); $app->setAutoExit(false); $out = new BufferedOutput();
        $status = $app->run(new ArrayInput(['--project' => $this->root, 'command' => 'design', 'id' => $id, '--run' => self::RUN, '--approve' => true, '--text' => "New\n### Detail\nAllowed"]), $out);
        return [$status, $out->fetch()];
    }

    public static function states(): iterable
    {
        foreach (['T-001', 'S-001'] as $id) {
            foreach (Status::cases() as $status) {
                yield $id.' '.$status->value => [$id, $status];
            }
        }
    }

    #[DataProvider('states')]
    public function testDesignApprovalStatusKindAndUnrelatedFields(string $id, Status $status): void
    {
        $this->store->approveScopeForRun($id, self::RUN);
        $event = $this->store->decideForRun($id, self::RUN, 'protected');
        $this->store->recordReceipt($id, ['role' => 'verifier', 'status' => 'done', 'at' => '2026-01-01T00:00:00Z'], [], '- verified');
        if ($status === Status::Done) {
            $this->store->close($id, Status::Done, 'fixture', '2026-01-01T00:00:00Z');
        } else {
            $this->store->mutate($id, static function (Item $item) use ($status): void { $item->status = $status; });
        }
        $beforeItem = $this->store->load($id); $path = $this->store->itemPath($id); $before = file_get_contents($path);
        [$code, $output] = $this->design($id);
        if (!in_array($status, [Status::Draft, Status::Todo, Status::InProgress], true)) {
            self::assertNotSame(0, $code, $output); self::assertSame($before, file_get_contents($path));
            return;
        }
        self::assertSame(0, $code, $output);
        $after = $this->store->load($id);
        self::assertSame($status, $after->status); self::assertSame($beforeItem->phase, $after->phase);
        self::assertSame([$event], $after->automatedEvents);
        foreach (['Plan', 'Decisions', 'Progress'] as $section) self::assertSame($beforeItem->section($section), $after->section($section));
        self::assertSame("New\n### Detail\nAllowed", $after->section('Design'));
        self::assertSame($after->designHash(), $after->designApproval->hash);
        self::assertSame(self::RUN, $after->designApproval->provenance['run_id']);
        self::assertNull($after->verifiedAt);
        $old = $beforeItem->toArray(); $new = $after->toArray();
        foreach (['design_approval', 'verified_at', 'updated'] as $key) { unset($old[$key], $new[$key]); }
        self::assertSame($old, $new);
    }

    public function testMissingAndStaleScopeApprovalAreDenied(): void
    {
        $path = $this->store->itemPath('T-001'); $before = file_get_contents($path);
        [$code] = $this->design('T-001'); self::assertNotSame(0, $code); self::assertSame($before, file_get_contents($path));
        $this->store->approveScopeForRun('T-001', self::RUN);
        $this->store->mutate('T-001', static function (Item $item): void { $item->title = 'Changed'; });
        $before = file_get_contents($path);
        [$code] = $this->design('T-001'); self::assertNotSame(0, $code); self::assertSame($before, file_get_contents($path));
    }
}
