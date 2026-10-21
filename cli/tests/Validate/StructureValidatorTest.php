<?php

declare(strict_types=1);

namespace Workflow\Tests\Validate;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Workflow\Domain\Item;
use Workflow\Domain\Kind;
use Workflow\Validate\StructureValidator;

final class StructureValidatorTest extends TestCase
{
    private function make(string $id, Kind $kind = Kind::Task, ?string $parent = null, array $depends = []): Item
    {
        return Item::fromArray([
            'id' => $id, 'kind' => $kind->value, 'parent' => $parent, 'depends_on' => $depends,
        ], '');
    }

    public static function invalidStructures(): iterable
    {
        yield 'identity' => ['T-002', Kind::Epic, null, [], 'id does not match kind epic'];
        yield 'malformed id' => ['task-2', Kind::Task, null, [], 'id does not match kind task'];
        yield 'epic parent' => ['E-002', Kind::Epic, 'E-001', [], 'an epic cannot have a parent'];
        yield 'task parent task' => ['T-002', Kind::Task, 'T-001', [], 'task parent must be a epic'];
        yield 'task parent subtask' => ['T-002', Kind::Task, 'S-001', [], 'task parent must be a epic'];
        yield 'missing subtask parent' => ['S-002', Kind::Subtask, null, [], 'a subtask requires a task parent'];
        yield 'subtask parent epic' => ['S-002', Kind::Subtask, 'E-001', [], 'subtask parent must be a task'];
        yield 'subtask parent subtask' => ['S-002', Kind::Subtask, 'S-001', [], 'subtask parent must be a task'];
        yield 'missing parent reference' => ['T-002', Kind::Task, 'E-999', [], 'parent E-999 does not exist'];
        yield 'self parent' => ['T-002', Kind::Task, 'T-002', [], 'own parent'];
        yield 'missing dependency' => ['T-002', Kind::Task, null, ['T-999'], 'dependency T-999 does not exist'];
        yield 'self dependency' => ['T-002', Kind::Task, null, ['T-002'], 'depends on itself'];
    }

    #[DataProvider('invalidStructures')]
    public function testFullAndCandidateShareStructuralDiagnostics(string $id, Kind $kind, ?string $parent, array $depends, string $message): void
    {
        $items = [
            'E-001' => $this->make('E-001', Kind::Epic),
            'T-001' => $this->make('T-001', Kind::Task, 'E-001'),
            'S-001' => $this->make('S-001', Kind::Subtask, 'T-001'),
        ];
        $candidate = $this->make($id, $kind, $parent, $depends);
        $validator = new StructureValidator();

        self::assertStringContainsString($message, implode("\n", $validator->validateCandidate($candidate, $items)->errors));
        $items[$id] = $candidate;
        self::assertStringContainsString($message, implode("\n", $validator->validate($items)->errors));
    }

    public function testIdentityRejectsTrailingNewlineAndPathSegments(): void
    {
        foreach (["T-001\n", '../T-001', 'T-001/other', '', '123', 'T-', 'T-1x'] as $id) {
            $candidate = $this->make('T-001');
            $candidate->id = $id;
            $validator = new StructureValidator();
            self::assertFalse($validator->validateCandidate($candidate, [])->ok(), $id);
            self::assertFalse($validator->validate([$candidate])->ok(), $id);
        }
    }

    public function testValidHierarchyAndStandaloneGuidance(): void
    {
        $items = [
            $this->make('E-001', Kind::Epic),
            $this->make('T-001', Kind::Task, 'E-001'),
            $this->make('S-001', Kind::Subtask, 'T-001', ['T-001']),
            $this->make('T-002'),
        ];
        $result = (new StructureValidator())->validate($items);
        self::assertSame([], $result->errors);
        self::assertSame(['T-002: standalone task without standalone_reason.'], $result->warnings);
        $items[3]->extra['standalone_reason'] = 'Independent maintenance';
        self::assertSame([], (new StructureValidator())->validate($items)->warnings);
    }

    public function testCandidateFindsLongCycleDespiteOtherBackEdges(): void
    {
        $items = [
            'T-002' => $this->make('T-002', depends: ['T-003']),
            'T-003' => $this->make('T-003', depends: ['T-002', 'T-004']),
            'T-004' => $this->make('T-004', depends: ['T-001']),
        ];
        $candidate = $this->make('T-001', depends: ['T-002']);
        $result = (new StructureValidator())->validateCandidate($candidate, $items);
        self::assertSame(['Dependency cycle: T-001 -> T-002 -> T-003 -> T-004 -> T-001'], $result->errors);
    }

    public function testLegacyDefectsIncludingReachableCyclesDoNotGateCandidate(): void
    {
        $items = [
            'S-001' => $this->make('S-001', Kind::Subtask),
            'T-002' => $this->make('T-002', depends: ['T-003', 'T-999']),
            'T-003' => $this->make('T-003', depends: ['T-002']),
        ];
        $candidate = $this->make('T-001', depends: ['T-002']);
        $before = serialize([$candidate, $items]);
        $validator = new StructureValidator();

        self::assertTrue($validator->validateCandidate($candidate, $items)->ok());
        self::assertSame($before, serialize([$candidate, $items]), 'Pure checks must not repair or mutate records.');
        $all = $validator->validate($items);
        self::assertStringContainsString('requires a task parent', implode("\n", $all->errors));
        self::assertStringContainsString('dependency T-999 does not exist', implode("\n", $all->errors));
        self::assertStringContainsString('Dependency cycle', implode("\n", $all->errors));
    }

    public function testParentKindRepairChecksExistingChildren(): void
    {
        $items = [
            'T-001' => $this->make('T-001', Kind::Epic),
            'T-002' => $this->make('T-002', Kind::Task, 'T-001'),
        ];
        $result = (new StructureValidator())->validateCandidate($this->make('T-001'), $items);
        self::assertSame(['T-002: task parent must be a epic, got task.'], $result->errors);
    }

    public function testCreatingMissingParentAlsoChecksReverseRelationships(): void
    {
        $child = $this->make('S-001', Kind::Subtask, 'E-001');
        $result = (new StructureValidator())->validateCandidate($this->make('E-001', Kind::Epic), ['S-001' => $child]);
        self::assertSame(['S-001: subtask parent must be a task, got epic.'], $result->errors);
    }

    public function testUnchangedParentKindDoesNotGateOnLegacyChildDefect(): void
    {
        $items = [
            'E-001' => $this->make('E-001', Kind::Epic),
            'S-001' => $this->make('S-001', Kind::Subtask, 'E-001'),
        ];
        self::assertTrue((new StructureValidator())->validateCandidate($this->make('E-001', Kind::Epic), $items)->ok());
    }
}
