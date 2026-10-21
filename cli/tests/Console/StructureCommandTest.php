<?php

declare(strict_types=1);

namespace Workflow\Tests\Console;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workflow\Console\Application;
use Workflow\Domain\FrontMatter;
use Workflow\Store\ItemStore;
use Workflow\Tests\WorkflowTestCase;

final class StructureCommandTest extends WorkflowTestCase
{
    private ItemStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        self::assertSame(0, $this->runCli(['command' => 'init', '--name' => 'structure'])[0]);
        self::assertSame(0, $this->runCli(['command' => 'new', 'title' => 'Epic', '--kind' => 'epic'])[0]);
        self::assertSame(0, $this->runCli(['command' => 'new', 'title' => 'Task', '--parent' => 'E-001'])[0]);
        self::assertSame(0, $this->runCli(['command' => 'new', 'title' => 'Subtask', '--kind' => 'subtask', '--parent' => 'T-001'])[0]);
        $this->store = new ItemStore($this->root);
    }

    /** @return array{int, string} */
    private function runCli(array $input, ?string $root = null): array
    {
        $application = new Application();
        $application->setAutoExit(false);
        $output = new BufferedOutput();
        $exit = $application->run(new ArrayInput(['--project' => $root ?? $this->root] + $input), $output);

        return [$exit, $output->fetch()];
    }

    private function bytes(): array
    {
        $bytes = ['board' => file_get_contents($this->store->root().'/.workflow/board.html')];
        foreach ($this->store->ids() as $id) {
            $bytes[$id] = file_get_contents($this->store->itemPath($id));
        }

        return $bytes;
    }

    public static function invalidNew(): iterable
    {
        yield 'epic parent' => [['--kind' => 'epic', '--parent' => 'E-001'], 'an epic cannot have a parent'];
        yield 'task parent task' => [['--parent' => 'T-001'], 'task parent must be a epic'];
        yield 'task parent subtask' => [['--parent' => 'S-001'], 'task parent must be a epic'];
        yield 'subtask no parent' => [['--kind' => 'subtask'], 'a subtask requires a task parent'];
        yield 'subtask explicit none' => [['--kind' => 'subtask', '--parent' => 'none'], 'a subtask requires a task parent'];
        yield 'subtask parent epic' => [['--kind' => 'subtask', '--parent' => 'E-001'], 'subtask parent must be a task'];
        yield 'subtask parent subtask' => [['--kind' => 'subtask', '--parent' => 'S-001'], 'subtask parent must be a task'];
        yield 'missing parent' => [['--parent' => 'E-999'], 'parent E-999 does not exist'];
        yield 'self parent' => [['--parent' => 'T-002'], 'own parent'];
        yield 'missing dependency' => [['--depends' => ['T-999']], 'dependency T-999 does not exist'];
        yield 'self dependency' => [['--depends' => ['T-002']], 'depends on itself'];
    }

    #[DataProvider('invalidNew')]
    public function testNewRefusesStructureWithoutLeavingAnItem(array $options, string $message): void
    {
        $before = $this->bytes();
        [$exit, $output] = $this->runCli(['command' => 'new', 'title' => 'Invalid'] + $options);

        self::assertSame(2, $exit, $output);
        self::assertStringContainsString($message, $output);
        self::assertSame($before, $this->bytes());
        self::assertSame(['E-001', 'S-001', 'T-001'], $this->store->ids());
    }

    public static function invalidSet(): iterable
    {
        yield 'task parent task' => ['T-001', ['parent=T-002'], 'task parent must be a epic'];
        yield 'epic parent' => ['E-001', ['parent=T-001'], 'an epic cannot have a parent'];
        yield 'subtask no parent' => ['S-001', ['parent=none'], 'a subtask requires a task parent'];
        yield 'subtask parent epic' => ['S-001', ['parent=E-001'], 'subtask parent must be a task'];
        yield 'missing parent' => ['T-001', ['parent=E-999'], 'parent E-999 does not exist'];
        yield 'self parent' => ['T-001', ['parent=T-001'], 'own parent'];
        yield 'missing dependency' => ['T-001', ['depends_on=T-999'], 'dependency T-999 does not exist'];
        yield 'self dependency' => ['T-001', ['depends_on=T-001'], 'depends on itself'];
        yield 'long cycle' => ['T-001', ['depends_on=T-003'], 'Dependency cycle'];
    }

    #[DataProvider('invalidSet')]
    public function testSetRejectsWholeMultiAssignmentAtomically(string $id, array $assignments, string $message): void
    {
        self::assertSame(0, $this->runCli(['command' => 'new', 'title' => 'Second', '--depends' => ['T-001']])[0]);
        self::assertSame(0, $this->runCli(['command' => 'new', 'title' => 'Third', '--depends' => ['T-002']])[0]);
        $before = $this->bytes();
        [$exit, $output] = $this->runCli([
            'command' => 'set', 'id' => $id,
            'assignments' => ['title=Must not persist', ...$assignments, 'priority=7'],
        ]);

        self::assertSame(2, $exit, $output);
        self::assertStringContainsString($message, $output);
        self::assertSame($before, $this->bytes());
    }

    public function testNewDetectsCycleThroughLegacyIncomingReference(): void
    {
        // A historical missing reference can become a cycle when its target is created.
        $legacy = $this->store->load('T-001');
        $legacy->dependsOn = ['T-002'];
        file_put_contents($this->store->itemPath('T-001'), FrontMatter::render($legacy->toArray(), $legacy->body));
        $before = $this->bytes();
        [$exit, $output] = $this->runCli(['command' => 'new', 'title' => 'Invalid cycle', '--depends' => ['T-001']]);

        self::assertSame(2, $exit, $output);
        self::assertStringContainsString('Dependency cycle', $output);
        self::assertSame($before, $this->bytes());
        self::assertDirectoryDoesNotExist($this->store->itemsDir().'/T-002');
    }

    public function testStandaloneTaskIsAllowedAndGuidanceRemainsWarningOnly(): void
    {
        [$exit, $output] = $this->runCli(['command' => 'new', 'title' => 'Standalone', '--phase' => 'verify']);
        self::assertSame(0, $exit, $output);
        [$exit, $output] = $this->runCli(['command' => 'set', 'id' => 'T-002', 'assignments' => ['status=in-progress']]);
        self::assertSame(0, $exit, $output);
        [$exit, $output] = $this->runCli(['command' => 'validate']);
        self::assertSame(1, $exit, $output);
        self::assertStringContainsString('standalone task without standalone_reason', $output);
        self::assertStringContainsString('in-progress without approval', $output);
        self::assertStringContainsString('verify without a plan', $output);
        self::assertStringContainsString('verify without a design', $output);
    }

    public static function malformedChildren(): iterable
    {
        yield 'directory mismatch' => [['id' => 'S-021']];
        yield 'declares parent id' => [['id' => 'E-001']];
        yield 'missing id' => [['id' => null]];
        yield 'invalid status' => [['status' => 'not-a-status']];
        yield 'invalid phase' => [['phase' => 'not-a-phase']];
    }

    #[DataProvider('malformedChildren')]
    public function testNewRejectsWrongKindParentOfMalformedChildWithoutRefreshingBoard(array $overrides): void
    {
        // The verifier's exact new-parent-command reproduction starts empty.
        $root = $this->root.'/new-parent-command';
        mkdir($root);
        self::assertSame(0, $this->runCli(['command' => 'init'], $root)[0]);
        $this->store = new ItemStore($root);
        mkdir($this->store->itemsDir().'/S-020');
        file_put_contents($this->store->itemPath('S-020'), FrontMatter::render(array_replace([
            'id' => 'S-020', 'kind' => 'subtask', 'parent' => 'E-001',
        ], $overrides), ''));
        $before = $this->bytes();
        [$exit, $output] = $this->runCli(['command' => 'new', 'title' => 'Affected parent', '--kind' => 'epic'], $root);

        self::assertSame(2, $exit, $output);
        self::assertStringContainsString('subtask parent must be a task, got epic.', $output);
        self::assertSame($before, $this->bytes());
        self::assertDirectoryDoesNotExist($this->store->itemsDir().'/E-001');
    }
}
