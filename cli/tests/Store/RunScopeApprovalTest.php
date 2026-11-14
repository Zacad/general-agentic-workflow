<?php

declare(strict_types=1);

namespace Workflow\Tests\Store;

use Workflow\Domain\Approval;
use Workflow\Domain\Item;
use Workflow\Domain\Run;
use Workflow\Domain\WorkflowException;
use Workflow\Store\ItemStore;
use Workflow\Store\RunStore;
use Workflow\Tests\WorkflowTestCase;

final class RunScopeApprovalTest extends WorkflowTestCase
{
    private const RUN = 'R-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private function setupRun(): ItemStore
    {
        $store = new ItemStore($this->root);
        $store->create(Item::fromArray(['id' => 'E-001', 'kind' => 'epic', 'parent' => null, 'title' => 'Root'], ''));
        $store->create(Item::fromArray(['id' => 'T-001', 'kind' => 'task', 'parent' => 'E-001', 'title' => 'Task'], ''));
        (new RunStore($this->root))->create(Run::fromArray([
            'version' => 1, 'id' => self::RUN, 'autonomous' => true, 'goal' => 'Goal', 'success_criteria' => ['criterion'],
            'permissions' => ['actions' => ['scope-approve', 'design-approve'], 'roots' => ['E-001']],
            'limits' => ['expires_at' => '2099-01-01T00:00:00Z', 'max_roots' => 1],
            'status' => 'active', 'status_reason' => null, 'recovery' => null,
            'created_at' => '2020-01-01T00:00:00Z', 'updated_at' => '2020-01-01T00:00:00Z',
        ]));
        return $store;
    }

    public function testAuthorizedApprovalPersistsProvenanceAndRevocationBlocksFutureWrites(): void
    {
        $store = $this->setupRun();
        $result = $store->approveScopeForRun('T-001', self::RUN);
        $item = $store->load('T-001');
        self::assertTrue($item->isApproved());
        self::assertSame(['kind' => 'automated', 'run_id' => self::RUN], $item->approval->provenance);
        self::assertSame($item->scopeHash(), $result['approval']['scope_hash']);
        self::assertArrayNotHasKey('confirmation', $result['approval']);
        (new RunStore($this->root))->mutate(self::RUN, static function (Run $run): Run {
            $data = $run->toArray(); $data['status'] = 'revoked'; $data['status_reason'] = 'stop';
            return Run::fromArray($data);
        });
        $path = $store->itemPath('T-001');
        $before = file_get_contents($path);
        try { $store->approveScopeForRun('T-001', self::RUN); self::fail('Revoked run accepted'); }
        catch (WorkflowException) { self::assertSame($before, file_get_contents($path)); }
        self::assertSame(self::RUN, $store->load('T-001')->approval->provenance['run_id']);
    }

    public function testAmbiguousAncestryAndDeltaMaterialityAreRejectedWithoutWrites(): void
    {
        $store = $this->setupRun();
        $store->approveScopeForRun('T-001', self::RUN);
        $store->mutate('T-001', static function (Item $item): void { $item->outcome = 'Changed'; });
        $path = $store->itemPath('T-001'); $before = file_get_contents($path);
        try { $store->approveScopeForRun('T-001', self::RUN, true); self::fail('Material delta accepted'); }
        catch (WorkflowException $e) { self::assertStringContainsString('material', $e->getMessage()); }
        self::assertSame($before, file_get_contents($path));
        mkdir($this->root.'/.workflow/items/E-001/T-001', 0777, true);
        file_put_contents($this->root.'/.workflow/items/E-001/T-001/item.md', $before);
        try { $store->approveScopeForRun('T-001', self::RUN); self::fail('Ambiguous target accepted'); }
        catch (WorkflowException $e) { self::assertStringContainsString('ambiguous', $e->getMessage()); }
        self::assertSame($before, file_get_contents($path));
    }

    public function testMalformedAutomatedProvenanceFailsClosed(): void
    {
        foreach ([null, [], ['kind' => 'automated', 'run_id' => '../bad'], ['kind' => 'human', 'run_id' => self::RUN], ['kind' => 'automated', 'run_id' => self::RUN, 'extra' => true]] as $bad) {
            try { Approval::fromArray(['at' => '2026-01-01T00:00:00Z', 'by' => 'automated', 'scope_hash' => str_repeat('a', 64), 'provenance' => $bad]); self::fail('Malformed provenance accepted'); }
            catch (WorkflowException) { self::assertTrue(true); }
        }
        try { Approval::fromArray(['at' => '2026-01-01T00:00:00Z', 'by' => 'automated', 'scope_hash' => str_repeat('a', 64), 'provenance' => ['kind' => 'automated', 'run_id' => self::RUN], 'confirmation' => 'yes']); self::fail('Contradictory quote accepted'); }
        catch (WorkflowException) { self::assertTrue(true); }
    }

    public function testDesignApprovalRequiresScopeAndCannotChangeOtherSections(): void
    {
        $store = $this->setupRun();
        $store->mutate('T-001', static function (Item $item): void {
            $item->body = "## Plan\n\nPlan text\n\n## Design\n\nOld\n\n## Decisions\n\nDecision\n\n## Progress\n\nProgress\n";
        });
        $path = $store->itemPath('T-001'); $before = file_get_contents($path);
        try { $store->approveDesignForRun('T-001', self::RUN, 'Design'); self::fail('Unapproved scope accepted'); }
        catch (WorkflowException) { self::assertSame($before, file_get_contents($path)); }
        $store->approveScopeForRun('T-001', self::RUN);
        $beforeItem = $store->load('T-001'); $before = file_get_contents($path);
        foreach (["Bad\n## Plan\nInjected", "Bad\r\n## Decisions\r\nInjected", " ## Progress\nInjected"] as $text) {
            try { $store->approveDesignForRun('T-001', self::RUN, $text); self::fail('Injected heading accepted'); }
            catch (WorkflowException) { self::assertSame($before, file_get_contents($path)); }
        }
        $store->approveDesignForRun('T-001', self::RUN, "New design\n\n### Detail\nAllowed");
        $after = $store->load('T-001');
        self::assertTrue($after->isDesignApproved());
        self::assertSame(self::RUN, $after->designApproval->provenance['run_id']);
        self::assertSame($beforeItem->section('Plan'), $after->section('Plan'));
        self::assertSame($beforeItem->section('Decisions'), $after->section('Decisions'));
        self::assertSame($beforeItem->section('Progress'), $after->section('Progress'));
        self::assertSame($beforeItem->status, $after->status);
        self::assertSame($beforeItem->phase, $after->phase);
        $store->mutate('T-001', static function (Item $item): void { $item->body .= "\n## Design\n\nDuplicate\n"; });
        $before = file_get_contents($path);
        try { $store->approveDesignForRun('T-001', self::RUN, 'New'); self::fail('Duplicate heading accepted'); }
        catch (WorkflowException) { self::assertSame($before, file_get_contents($path)); }
    }
}
