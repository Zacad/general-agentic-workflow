<?php

declare(strict_types=1);

namespace Workflow\Tests\Validate;

use Workflow\Domain\Approval;
use Workflow\Domain\Item;
use Workflow\Domain\Kind;
use Workflow\Domain\Phase;
use Workflow\Domain\Status;
use Workflow\Store\ItemStore;
use Workflow\Store\ProjectInitializer;
use Workflow\Tests\WorkflowTestCase;
use Workflow\Validate\ItemValidator;

final class ItemValidatorTest extends WorkflowTestCase
{
    private ItemStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        (new ProjectInitializer(dirname(__DIR__, 2).'/templates'))->initialize($this->root, 'test');
        $this->store = new ItemStore($this->root);
    }

    private function make(string $id, Kind $kind, ?string $parent = null): Item
    {
        $now = '2026-01-01T00:00:00+00:00';
        $item = new Item(
            id: $id,
            kind: $kind,
            parent: $parent,
            title: 'Item '.$id,
            status: Status::Todo,
            phase: Phase::Explore,
            priority: 2,
            dependsOn: [],
            owns: [],
            artifacts: [],
            approval: null,
            lastResult: null,
            verifiedAt: null,
            created: $now,
            updated: $now,
            outcome: 'Outcome '.$id,
            scopeIn: [],
            scopeOut: [],
            acceptance: ['criterion'],
            body: "## Plan\n\n## Progress\n",
        );
        $item->approval = new Approval($now, 'test', $item->scopeHash());

        return $item;
    }

    public function testValidProjectPasses(): void
    {
        $this->store->create($this->make('E-001', Kind::Epic));
        $this->store->create($this->make('T-001', Kind::Task, 'E-001'));
        $this->store->create($this->make('S-001', Kind::Subtask, 'T-001'));

        $result = (new ItemValidator($this->store))->validate();

        self::assertSame([], $result->errors);
    }

    public function testDependencyCycleIsDetected(): void
    {
        $first = $this->make('T-001', Kind::Task);
        $first->dependsOn = ['T-002'];
        $second = $this->make('T-002', Kind::Task);
        $second->dependsOn = ['T-001'];
        $this->store->create($first);
        $this->store->create($second);

        $result = (new ItemValidator($this->store))->validate();

        self::assertNotEmpty($result->errors);
        self::assertStringContainsString('Dependency cycle', implode("\n", $result->errors));
    }

    public function testBadParentKindIsDetected(): void
    {
        $this->store->create($this->make('E-001', Kind::Epic));
        $this->store->create($this->make('S-001', Kind::Subtask, 'E-001'));

        $result = (new ItemValidator($this->store))->validate();

        self::assertStringContainsString('subtask parent must be a task', implode("\n", $result->errors));
    }

    public function testStaleApprovalIsDetected(): void
    {
        $item = $this->make('T-001', Kind::Task);
        $this->store->create($item);

        $loaded = $this->store->load('T-001');
        $loaded->acceptance = ['changed'];
        $this->store->save($loaded);

        $result = (new ItemValidator($this->store))->validate();

        self::assertStringContainsString('approval is stale', implode("\n", $result->errors));
    }

    public function testUnexpectedFileIsDetected(): void
    {
        $this->store->create($this->make('T-001', Kind::Task));
        file_put_contents($this->store->itemsDir().'/T-001/notes.txt', 'stray');

        $result = (new ItemValidator($this->store))->validate();

        self::assertStringContainsString('unexpected entry "notes.txt"', implode("\n", $result->errors));
    }

    public function testOwnershipOverlapIsReported(): void
    {
        $first = $this->make('T-001', Kind::Task);
        $first->owns = ['src/Store/'];
        $second = $this->make('T-002', Kind::Task);
        $second->owns = ['src/Store/File.php'];
        $this->store->create($first);
        $this->store->create($second);

        $result = (new ItemValidator($this->store))->validate();

        self::assertStringContainsString('parallel overlap', implode("\n", $result->warnings));
    }

    public function testImplementationWithoutPlanIsReported(): void
    {
        $item = $this->make('E-001', Kind::Epic);
        $item->phase = Phase::Implement;
        $this->store->create($item);

        $result = (new ItemValidator($this->store))->validate();

        self::assertStringContainsString('implement without a plan', implode("\n", $result->warnings));

        $item->body = "## Plan\n\n1. Do the work.\n\n## Progress\n";
        $this->store->save($item);

        $result = (new ItemValidator($this->store))->validate();

        self::assertStringNotContainsString('without a plan', implode("\n", $result->warnings));
    }
}
