<?php

declare(strict_types=1);

namespace Workflow\Tests\Domain;

use PHPUnit\Framework\TestCase;
use Workflow\Domain\Approval;
use Workflow\Domain\FrontMatter;
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

    private function designApprove(Item $item): void
    {
        $item->designApproval = Approval::forDesign('2026-01-01T00:00:00+00:00', 'adam', $item->designHash());
    }

    private function itemWithDesign(): Item
    {
        $item = $this->item();
        $item->body = "## Plan\n\nOriginal Plan\n\n## Design\n\nOriginal design\n\n## Progress\n";
        $this->approve($item);
        $this->designApprove($item);

        return $item;
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

    public function testDesignApprovalSurvivesRoutineAndScopeChanges(): void
    {
        $item = $this->itemWithDesign();
        self::assertTrue($item->isDesignApproved());

        $item->status = Status::InProgress;
        $item->phase = Phase::Implement;
        $item->priority = 1;
        $item->owns = ['src/', 'tests/'];
        $item->artifacts = ['outputs/report.md'];
        $item->body .= "- 2026-01-02 progress\n";
        $item->body = str_replace('Original Plan', 'Changed Plan', $item->body);
        self::assertTrue($item->isDesignApproved());

        // The asymmetry: scope changes stale scope approval, never design
        // approval — the two hashes stay independent.
        $item->title = 'New title';
        $item->outcome = 'New outcome';
        $item->acceptance[] = 'criterion three';
        $item->scopeIn[] = 'four';
        self::assertTrue($item->isDesignApproved());
        self::assertTrue($item->hasStaleApproval());
    }

    public function testDesignChangesInvalidateDesignApproval(): void
    {
        $changes = [
            'content' => static fn (Item $item) => $item->body = str_replace(
                'Original design',
                'Changed design',
                $item->body,
            ),
            'appended lines' => static fn (Item $item) => $item->body = str_replace(
                "Original design\n",
                "Original design\n\n### Checks\n\nAdded",
                $item->body,
            ),
            'whitespace only is not material' => static fn (Item $item) => $item->body = str_replace(
                "## Design\n\nOriginal design\n\n## Progress",
                "## Design\n\n\n  Original design  \n\n## Progress",
                $item->body,
            ),
            'duplicated heading' => static fn (Item $item) => $item->body .= "\n## Design\n\nChanged duplicate\n",
        ];

        foreach ($changes as $label => $change) {
            $item = $this->itemWithDesign();
            $change($item);
            $stale = $label !== 'whitespace only is not material';

            self::assertSame($stale, $item->hasStaleDesignApproval(), $label);
            self::assertSame(!$stale, $item->isDesignApproved(), $label);
        }
    }

    public function testDesignHashIsTakenFromTheBodyAndCoversDuplicates(): void
    {
        $missing = $this->item();
        $missing->body = "## Plan\n\n## Progress\n";
        self::assertSame(hash('sha256', ''), $missing->designHash());
        self::assertSame($missing->designHash(), (clone $missing)->designHash());

        // section() shows the first occurrence; the hash still covers the
        // duplicate so a repeated heading is material.
        $duplicated = $this->itemWithDesign();
        $duplicated->body .= "\n## Design\n\nChanged duplicate\n";
        self::assertSame('Original design', $duplicated->section('Design'));
        self::assertNotSame(hash('sha256', $duplicated->section('Design')), $duplicated->designHash());

        // Trimming: only the section content is hashed, not surrounding body.
        $padded = $this->itemWithDesign();
        $padded->body = str_replace("## Plan\n\n", "## Plan\n\nIntro\n\n", $padded->body);
        self::assertSame($this->itemWithDesign()->designHash(), $padded->designHash());
    }

    public function testDesignApprovalRecordRoundTripsUnderItsOwnKey(): void
    {
        $item = $this->itemWithDesign();
        $item->designApproval = Approval::forDesign('2026-01-01T00:00:00+00:00', 'adam', $item->designHash(), 'Proceed');

        $parsed = FrontMatter::parse(FrontMatter::render($item->toArray(), $item->body));
        $roundTripped = Item::fromArray($parsed['data'], $parsed['body']);

        self::assertSame('design_hash', $roundTripped->designApproval?->hashKey);
        self::assertSame($item->designHash(), $roundTripped->designApproval?->hash);
        self::assertSame('Proceed', $roundTripped->designApproval?->confirmation);
        self::assertTrue($roundTripped->isDesignApproved());
        // Both blocks keep their own hash keys in one record.
        self::assertSame('scope_hash', $roundTripped->approval?->hashKey);
        self::assertTrue($roundTripped->isApproved());
    }
}
