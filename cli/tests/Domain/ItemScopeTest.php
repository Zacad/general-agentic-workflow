<?php

declare(strict_types=1);

namespace Workflow\Tests\Domain;

use PHPUnit\Framework\TestCase;
use Workflow\Domain\Approval;
use Workflow\Domain\Item;
use Workflow\Domain\Kind;
use Workflow\Domain\Phase;
use Workflow\Domain\Status;

final class ItemScopeTest extends TestCase
{
    private function item(): Item
    {
        return new Item(
            id: 'T-001',
            kind: Kind::Task,
            parent: 'E-001',
            title: 'Title',
            status: Status::Todo,
            phase: Phase::Explore,
            priority: 2,
            dependsOn: ['T-002', 'T-003'],
            owns: ['src/'],
            artifacts: [],
            approval: null,
            lastResult: null,
            verifiedAt: null,
            created: '2026-01-01T00:00:00+00:00',
            updated: '2026-01-01T00:00:00+00:00',
            outcome: 'Outcome',
            scopeIn: ['one', 'two'],
            scopeOut: ['three'],
            acceptance: ['criterion one', 'criterion two'],
            body: "## Plan\n\n## Progress\n",
        );
    }

    private function approve(Item $item): void
    {
        $item->approval = new Approval('2026-01-01T00:00:00+00:00', 'adam', $item->scopeHash());
    }

    public function testApprovalSurvivesRoutineChanges(): void
    {
        $item = $this->item();
        $this->approve($item);
        self::assertTrue($item->isApproved());

        $item->status = Status::InProgress;
        $item->phase = Phase::Implement;
        $item->priority = 1;
        $item->owns = ['src/', 'tests/'];
        $item->artifacts = ['outputs/report.md'];
        $item->body .= "- 2026-01-02 progress\n";

        self::assertTrue($item->isApproved());
    }

    public function testScopeChangesInvalidateApproval(): void
    {
        $changes = [
            static fn (Item $item) => $item->title = 'New title',
            static fn (Item $item) => $item->outcome = 'New outcome',
            static fn (Item $item) => $item->acceptance[] = 'criterion three',
            static fn (Item $item) => $item->scopeIn[] = 'four',
            static fn (Item $item) => $item->scopeOut = [],
            static fn (Item $item) => $item->dependsOn = ['T-002'],
            static fn (Item $item) => $item->parent = 'E-002',
            static fn (Item $item) => $item->kind = Kind::Subtask,
        ];

        foreach ($changes as $index => $change) {
            $item = $this->item();
            $this->approve($item);
            $change($item);

            self::assertTrue($item->hasStaleApproval(), sprintf('Change %d did not invalidate approval.', $index));
        }
    }

    public function testScopeHashIgnoresArrayOrder(): void
    {
        $left = $this->item();
        $right = $this->item();
        $right->dependsOn = ['T-003', 'T-002'];
        $right->scopeIn = ['two', 'one'];

        self::assertSame($left->scopeHash(), $right->scopeHash());
    }

    public function testAcceptanceOrderIsMaterial(): void
    {
        $left = $this->item();
        $right = $this->item();
        $right->acceptance = ['criterion two', 'criterion one'];

        self::assertNotSame($left->scopeHash(), $right->scopeHash());
    }
}
