<?php

declare(strict_types=1);

namespace Workflow\Tests\Store;

use PHPUnit\Framework\Attributes\DataProvider;
use Workflow\Domain\FrontMatter;
use Workflow\Domain\InvalidItemException;
use Workflow\Domain\Item;
use Workflow\Domain\Kind;
use Workflow\Domain\WorkflowException;
use Workflow\Store\ItemStore;
use Workflow\Tests\WorkflowTestCase;
use Workflow\Validate\ItemValidator;

final class StructureWriteTest extends WorkflowTestCase
{
    private ItemStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = new ItemStore($this->root);
        $this->store->create($this->make('E-001', Kind::Epic));
        $this->store->create($this->make('T-001', parent: 'E-001'));
        $this->store->create($this->make('S-001', Kind::Subtask, 'T-001'));
    }

    private function make(string $id, Kind $kind = Kind::Task, ?string $parent = null, array $depends = []): Item
    {
        return Item::fromArray([
            'id' => $id, 'kind' => $kind->value, 'parent' => $parent, 'depends_on' => $depends,
        ], "## Plan\n\n## Design\n");
    }

    /** Explicitly unsafe fixture setup, never a supported write route. */
    private function writeRaw(Item $item, ?string $directoryId = null, array $overrides = []): void
    {
        $directory = dirname($this->store->itemPath($directoryId ?? $item->id));
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        file_put_contents($directory.'/item.md', FrontMatter::render(array_replace($item->toArray(), $overrides), $item->body));
    }

    private function writeMalformedChild(Kind $kind, string $parent, string $fault): void
    {
        $id = $kind->prefix().'-020';
        $overrides = match ($fault) {
            'directory mismatch' => ['id' => $kind->prefix().'-021'],
            'declares parent id' => ['id' => $parent],
            'missing id' => ['id' => null],
            'invalid status' => ['status' => 'not-a-status'],
            'invalid phase' => ['phase' => 'not-a-phase'],
        };
        $this->writeRaw($this->make($id, $kind, $parent), $id, $overrides);
    }

    private function bytes(): array
    {
        $bytes = [];
        foreach ($this->store->ids() as $id) {
            $bytes[$id] = file_get_contents($this->store->itemPath($id));
        }

        return $bytes;
    }

    public static function invalidWrites(): iterable
    {
        $cases = [
            'kind identity' => [['kind' => Kind::Subtask], 'id does not match kind'],
            'id identity' => [['id' => 'E-005'], 'id'],
            'malformed id' => [['id' => '../T-005'], 'id'],
            'epic parent' => [['kind' => Kind::Epic], 'an epic cannot have a parent'],
            'task parent task' => [['parent' => 'T-001'], 'task parent must be a epic'],
            'task parent subtask' => [['parent' => 'S-001'], 'task parent must be a epic'],
            'missing parent' => [['parent' => 'E-999'], 'parent E-999 does not exist'],
            'self parent' => [['parent' => 'T-002'], 'own parent'],
            'self dependency' => [['dependsOn' => ['T-002']], 'depends on itself'],
            'missing dependency' => [['dependsOn' => ['T-999']], 'dependency T-999 does not exist'],
            'long cycle' => [['dependsOn' => ['T-003']], 'Dependency cycle'],
        ];
        foreach (['create', 'save', 'mutate'] as $route) {
            foreach ($cases as $name => [$changes, $message]) {
                yield $route.' '.$name => [$route, $changes, $message];
            }
        }
    }

    #[DataProvider('invalidWrites')]
    public function testInvalidWritesAreAtomic(string $route, array $changes, string $message): void
    {
        $candidate = $this->make('T-002', parent: 'E-001');
        if ($route !== 'create') {
            $this->store->create($candidate);
        }
        $this->writeRaw($this->make('T-003', depends: ['T-004']));
        $this->writeRaw($this->make('T-004', depends: ['T-002']));
        $before = $this->bytes();

        $change = static function (Item $item) use ($changes): void {
            $item->title = 'Must not persist';
            foreach ($changes as $field => $value) {
                $item->{$field} = $value;
            }
        };
        try {
            if ($route === 'mutate') {
                $this->store->mutate('T-002', $change);
            } else {
                $change($candidate);
                $this->store->{$route}($candidate);
            }
            self::fail('Invalid '.$route.' succeeded.');
        } catch (InvalidItemException $exception) {
            self::assertStringContainsString($message, $exception->getMessage());
        }

        self::assertSame($before, $this->bytes());
        self::assertSame(array_keys($before), $this->store->ids(), 'No new item directory may remain.');
    }

    public static function subtaskWrites(): iterable
    {
        foreach (['create', 'save', 'mutate'] as $route) {
            foreach ([null, 'E-001', 'S-001', 'T-999', 'S-002'] as $parent) {
                yield $route.' '.($parent ?? 'none') => [$route, $parent];
            }
        }
    }

    #[DataProvider('subtaskWrites')]
    public function testSubtaskParentRulesApplyToEveryRoute(string $route, ?string $parent): void
    {
        $candidate = $this->make('S-002', Kind::Subtask, 'T-001');
        if ($route !== 'create') {
            $this->store->create($candidate);
        }
        $before = $this->bytes();
        try {
            if ($route === 'mutate') {
                $this->store->mutate('S-002', static function (Item $item) use ($parent): void {
                    $item->parent = $parent;
                });
            } else {
                $candidate->parent = $parent;
                $this->store->{$route}($candidate);
            }
            self::fail('Invalid subtask parent persisted.');
        } catch (InvalidItemException) {
            self::assertSame($before, $this->bytes());
            self::assertSame(array_keys($before), $this->store->ids());
        }
    }

    public function testMutateCannotRenameAnItemEvenToAnotherValidId(): void
    {
        $before = $this->bytes();
        try {
            $this->store->mutate('T-001', static function (Item $item): void {
                $item->id = 'T-999';
            });
            self::fail('Mutation renamed the item.');
        } catch (InvalidItemException $exception) {
            self::assertStringContainsString('Cannot change item id', $exception->getMessage());
        }
        self::assertSame($before, $this->bytes());
        self::assertDirectoryDoesNotExist($this->store->itemsDir().'/T-999');
    }

    public function testSaveCanCreateButCannotBypassStructureChecks(): void
    {
        $candidate = $this->make('S-002', Kind::Subtask);
        try {
            $this->store->save($candidate);
            self::fail('Invalid save created an item.');
        } catch (InvalidItemException) {
            self::assertDirectoryDoesNotExist($this->store->itemsDir().'/S-002');
        }
        $candidate->parent = 'T-001';
        $this->store->save($candidate);
        self::assertSame('T-001', $this->store->load('S-002')->parent);
    }

    public function testKindRepairCannotInvalidateExistingChildren(): void
    {
        $this->writeRaw($this->make('T-002', Kind::Epic));
        $this->writeRaw($this->make('T-003', parent: 'T-002'));
        $before = $this->bytes();
        foreach (['save', 'mutate'] as $route) {
            try {
                if ($route === 'save') {
                    $this->store->save($this->make('T-002'));
                } else {
                    $this->store->mutate('T-002', static function (Item $item): void {
                        $item->kind = Kind::Task;
                    });
                }
                self::fail('Kind repair invalidated a child.');
            } catch (InvalidItemException $exception) {
                self::assertStringContainsString('T-003: task parent must be a epic, got task.', $exception->getMessage());
            }
            self::assertSame($before, $this->bytes());
        }
    }

    public function testCreationCannotResolveLegacyParentToWrongKind(): void
    {
        $this->writeRaw($this->make('S-002', Kind::Subtask, 'E-002'));
        $before = $this->bytes();
        try {
            $this->store->create($this->make('E-002', Kind::Epic));
            self::fail('Creation left an affected child with a wrong-kind parent.');
        } catch (InvalidItemException) {
            self::assertSame($before, $this->bytes());
            self::assertDirectoryDoesNotExist($this->store->itemsDir().'/E-002');
        }
    }

    public static function malformedChildKindChanges(): iterable
    {
        foreach (['save', 'mutate'] as $route) {
            foreach (['directory mismatch', 'declares parent id', 'missing id', 'invalid status', 'invalid phase'] as $fault) {
                yield $route.' '.$fault => [$route, $fault];
            }
        }
    }

    #[DataProvider('malformedChildKindChanges')]
    public function testKindRepairCannotDropMalformedAffectedChildren(string $route, string $fault): void
    {
        // Includes the verifier's exact T-010 / T-020-declares-T-021 reproduction.
        $this->writeRaw($this->make('T-010', Kind::Epic));
        $this->writeMalformedChild(Kind::Task, 'T-010', $fault);
        $before = $this->bytes();
        try {
            if ($route === 'save') {
                $this->store->save($this->make('T-010'));
            } else {
                $this->store->mutate('T-010', static function (Item $item): void {
                    $item->kind = Kind::Task;
                    $item->title = 'Must not persist';
                });
            }
            self::fail('Kind repair skipped an affected malformed child.');
        } catch (InvalidItemException $exception) {
            self::assertStringContainsString('task parent must be a epic, got task.', $exception->getMessage());
        }
        self::assertSame($before, $this->bytes());
        self::assertSame(array_keys($before), $this->store->ids());
    }

    public static function malformedChildParentCreations(): iterable
    {
        foreach (['create', 'save'] as $route) {
            foreach (['directory mismatch', 'declares parent id', 'missing id', 'invalid status', 'invalid phase'] as $fault) {
                yield $route.' '.$fault => [$route, $fault];
            }
        }
    }

    #[DataProvider('malformedChildParentCreations')]
    public function testParentCreationCannotDropMalformedAffectedChildren(string $route, string $fault): void
    {
        // Includes the verifier's exact S-020-declares-S-021 / missing E-010 reproduction.
        $this->writeMalformedChild(Kind::Subtask, 'E-010', $fault);
        $before = $this->bytes();
        try {
            $this->store->{$route}($this->make('E-010', Kind::Epic));
            self::fail('Parent creation skipped an affected malformed child.');
        } catch (InvalidItemException $exception) {
            self::assertStringContainsString('subtask parent must be a task, got epic.', $exception->getMessage());
        }
        self::assertSame($before, $this->bytes());
        self::assertSame(array_keys($before), $this->store->ids());
        self::assertDirectoryDoesNotExist($this->store->itemsDir().'/E-010');
    }

    #[DataProvider('malformedChildKindChanges')]
    public function testMalformedChildDoesNotBlockARepairThatFixesItsRelationship(string $route, string $fault): void
    {
        $this->writeRaw($this->make('T-010', Kind::Epic));
        $this->writeMalformedChild(Kind::Subtask, 'T-010', $fault);
        $childBytes = file_get_contents($this->store->itemPath('S-020'));
        if ($route === 'save') {
            $this->store->save($this->make('T-010'));
        } else {
            $this->store->mutate('T-010', static function (Item $item): void {
                $item->kind = Kind::Task;
            });
        }
        self::assertSame(Kind::Task, $this->store->load('T-010')->kind);
        self::assertSame($childBytes, file_get_contents($this->store->itemPath('S-020')));
        self::assertNotEmpty((new ItemValidator($this->store))->validate()->errors, 'Do not auto-repair other malformed fields.');
    }

    public function testUnrelatedMalformedChildrenStillAllowValidWritesAndStructuralRepairs(): void
    {
        $this->writeRaw($this->make('T-010', Kind::Epic));
        $this->writeMalformedChild(Kind::Task, 'T-010', 'directory mismatch');
        $this->writeMalformedChild(Kind::Subtask, 'E-999', 'invalid status');
        $this->writeRaw($this->make('S-030', Kind::Subtask));
        $before = $this->bytes();

        $this->store->create($this->make('E-010', Kind::Epic));
        $this->store->save($this->make('T-030'));
        $this->store->mutate('S-030', static function (Item $item): void {
            $item->parent = 'T-001';
        });
        foreach (['T-010', 'T-020', 'S-020'] as $id) {
            self::assertSame($before[$id], file_get_contents($this->store->itemPath($id)));
        }
        self::assertSame('T-001', $this->store->load('S-030')->parent);
    }

    public static function malformedCycleWrites(): iterable
    {
        foreach (['create', 'save', 'mutate'] as $route) {
            foreach (['directory mismatch' => ['id' => 'T-021'], 'invalid status' => ['status' => 'not-a-status']] as $fault => $overrides) {
                yield $route.' '.$fault => [$route, $overrides];
            }
        }
    }

    #[DataProvider('malformedCycleWrites')]
    public function testCyclesCannotDisappearBehindMalformedMetadata(string $route, array $overrides): void
    {
        $candidate = $this->make('T-010');
        if ($route !== 'create') {
            $this->store->create($candidate);
        }
        $this->writeRaw($this->make('T-020', depends: ['T-010']), 'T-020', $overrides);
        $before = $this->bytes();
        try {
            if ($route === 'mutate') {
                $this->store->mutate('T-010', static function (Item $item): void {
                    $item->dependsOn = ['T-020'];
                });
            } else {
                $candidate->dependsOn = ['T-020'];
                $this->store->{$route}($candidate);
            }
            self::fail('Cycle disappeared behind nonstructural legacy metadata.');
        } catch (InvalidItemException $exception) {
            self::assertStringContainsString('Dependency cycle: T-010 -> T-020 -> T-010', $exception->getMessage());
        }
        self::assertSame($before, $this->bytes());
        self::assertSame(array_keys($before), $this->store->ids());
    }

    public function testKindRepairCanAlsoRepairAnAffectedChild(): void
    {
        foreach (['save', 'mutate'] as $route) {
            $this->writeRaw($this->make('T-002', Kind::Epic));
            $this->writeRaw($this->make('S-002', Kind::Subtask, 'T-002'));
            $childBytes = file_get_contents($this->store->itemPath('S-002'));
            if ($route === 'save') {
                $this->store->save($this->make('T-002'));
            } else {
                $this->store->mutate('T-002', static function (Item $item): void {
                    $item->kind = Kind::Task;
                });
            }

            self::assertSame(Kind::Task, $this->store->load('T-002')->kind);
            self::assertSame($childBytes, file_get_contents($this->store->itemPath('S-002')));
            self::assertSame([], (new ItemValidator($this->store))->validate()->errors);
        }
    }

    public function testUnrelatedLegacyDefectsDoNotBlockValidWritesOrRepairs(): void
    {
        $this->writeRaw($this->make('S-002', Kind::Subtask));
        $this->writeRaw($this->make('T-002', depends: ['T-003', 'T-999']));
        $this->writeRaw($this->make('T-003', depends: ['T-002']));
        mkdir($this->store->itemsDir().'/T-004');
        file_put_contents($this->store->itemPath('T-004'), "---\nkind: [invalid\n---\n");
        $legacy = $this->bytes();

        $this->store->create($this->make('T-005', depends: ['T-002']));
        $valid = $this->store->load('T-005');
        $valid->title = 'Updated';
        $this->store->save($valid);
        self::assertSame('Updated', $this->store->load('T-005')->title);
        $this->store->mutate('S-002', static function (Item $item): void {
            $item->parent = 'T-001';
        });
        $this->store->mutate('T-002', static function (Item $item): void {
            $item->dependsOn = [];
        });

        self::assertSame('T-001', $this->store->load('S-002')->parent);
        self::assertSame([], $this->store->load('T-002')->dependsOn);
        foreach (['T-003', 'T-004'] as $id) {
            self::assertSame($legacy[$id], file_get_contents($this->store->itemPath($id)));
        }
        self::assertNotEmpty((new ItemValidator($this->store))->validate()->errors, 'Full validate must still report malformed legacy data.');
    }

    public function testNoWorkflowGatesAreAdded(): void
    {
        $candidate = $this->make('T-002', depends: ['T-001']);
        $candidate->status = \Workflow\Domain\Status::InProgress;
        $candidate->phase = \Workflow\Domain\Phase::Verify;
        $candidate->owns = ['shared/path'];
        $candidate->artifacts = ['missing.md'];
        $this->store->create($candidate);
        $this->store->save($candidate);
        $this->store->mutate('T-002', static function (Item $item): void {
            $item->priority = 1;
        });

        $loaded = $this->store->load('T-002');
        self::assertNull($loaded->approval);
        self::assertSame(1, $loaded->priority);
        self::assertSame('', $loaded->section('Plan'));
        self::assertSame('', $loaded->section('Design'));
        $diagnostics = (new ItemValidator($this->store))->validate();
        self::assertStringContainsString('in-progress without approval', implode("\n", $diagnostics->errors));
        self::assertStringContainsString('standalone task without standalone_reason', implode("\n", $diagnostics->warnings));
        self::assertStringContainsString('without a plan', implode("\n", $diagnostics->warnings));
        self::assertStringContainsString('without a design', implode("\n", $diagnostics->warnings));
    }

    public function testGraphIsReadAfterMutationInsideWriteLock(): void
    {
        $before = file_get_contents($this->store->itemPath('T-001'));
        try {
            $this->store->mutate('T-001', function (Item $item): void {
                // Simulate an affected edge appearing before final validation.
                $this->writeRaw($this->make('T-002', depends: ['T-001']));
                $item->dependsOn = ['T-002'];
            });
            self::fail('Persist did not check the current graph.');
        } catch (InvalidItemException $exception) {
            self::assertStringContainsString('Dependency cycle', $exception->getMessage());
        }
        self::assertSame($before, file_get_contents($this->store->itemPath('T-001')));

        $this->store->withLock(function (): void {
            try {
                $this->store->save($this->make('T-003'));
                self::fail('Save bypassed the existing lock.');
            } catch (WorkflowException $exception) {
                self::assertStringContainsString('Another workflow write', $exception->getMessage());
            }
        });
        self::assertDirectoryDoesNotExist($this->store->itemsDir().'/T-003');
    }
}
