<?php

declare(strict_types=1);

namespace Workflow\Tests\Console;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workflow\Console\Application;
use Workflow\Console\Command\MigrateCommand;
use Workflow\Store\ItemStore;
use Workflow\Tests\WorkflowTestCase;
use Workflow\Validate\ItemValidator;

final class MigrateCommandTest extends WorkflowTestCase
{
    private ItemStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        self::assertSame(0, $this->runCli(['command' => 'init', '--name' => 'migrate'])[0]);
        $this->store = new ItemStore((string) realpath($this->root));
    }

    /** @return array{int, string} */
    private function runCli(array $input, ?callable $rename = null): array
    {
        $application = new Application();
        if ($rename !== null) {
            $application->addCommand(new MigrateCommand($rename));
        }
        $application->setAutoExit(false);
        $output = new BufferedOutput();
        $exit = $application->run(new ArrayInput(['--project' => $this->root] + $input), $output);

        return [$exit, $output->fetch()];
    }

    public function testMigrateThreeLevelsAndStandalonePreservesFiles(): void
    {
        $items = [
            ['E-001', ['title' => 'Epic', '--kind' => 'epic'], 'E-001'],
            ['T-001', ['title' => 'Task', '--parent' => 'E-001'], 'E-001/T-001'],
            ['S-001', ['title' => 'Subtask', '--kind' => 'subtask', '--parent' => 'T-001'], 'E-001/T-001/S-001'],
            ['T-002', ['title' => 'Standalone', '--standalone-reason' => 'Independent work'], 'T-002'],
            ['S-002', ['title' => 'Standalone subtask', '--kind' => 'subtask', '--parent' => 'T-002'], 'T-002/S-002'],
        ];
        $snapshots = [];
        foreach ($items as [$id, $options, $target]) {
            [$exit, $output] = $this->runCli(['command' => 'new'] + $options);
            self::assertSame(0, $exit, $output);
            $dir = $this->store->itemsDir().'/'.$id;
            mkdir($dir.'/outputs');
            file_put_contents($dir.'/outputs/evidence.bin', "evidence for $id\n\0");
            $snapshots[$target] = [file_get_contents($dir.'/item.md'), file_get_contents($dir.'/outputs/evidence.bin')];
        }

        [$exit, $output] = $this->runCli(['command' => 'migrate', '--force' => true]);
        self::assertSame(0, $exit, $output);
        foreach ($snapshots as $target => [$item, $evidence]) {
            $dir = $this->store->itemsDir().'/'.$target;
            self::assertFileExists($dir.'/item.md');
            self::assertSame($item, file_get_contents($dir.'/item.md'));
            self::assertFileExists($dir.'/outputs/evidence.bin');
            self::assertSame($evidence, file_get_contents($dir.'/outputs/evidence.bin'));
        }
        foreach (['T-001', 'S-001', 'S-002'] as $id) {
            self::assertDirectoryDoesNotExist($this->store->itemsDir().'/'.$id);
        }
        self::assertDirectoryExists($this->store->itemsDir().'.backup');
        [$exit, $output] = $this->runCli(['command' => 'validate', '--json' => true]);
        self::assertSame(0, $exit, $output);
        $validation = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
        self::assertTrue($validation['ok']);
        self::assertSame([], $validation['errors']);
    }

    /** Create an epic, task and any number of subtasks, each with distinct bytes. */
    private function createNestedFixture(int $subtasks = 1): void
    {
        $items = [
            ['E-001', ['title' => 'Epic', '--kind' => 'epic']],
            ['T-001', ['title' => 'Task', '--parent' => 'E-001']],
        ];
        for ($i = 1; $i <= $subtasks; ++$i) {
            $items[] = [sprintf('S-%03d', $i), ['title' => 'Subtask '.$i, '--kind' => 'subtask', '--parent' => 'T-001']];
        }
        foreach ($items as [$id, $options]) {
            [$exit, $output] = $this->runCli(['command' => 'new'] + $options);
            self::assertSame(0, $exit, $output);
            $dir = $this->store->itemsDir().'/'.$id.'/outputs';
            mkdir($dir);
            file_put_contents($dir.'/evidence.bin', "data for $id\n\0");
        }
    }

    /** @return array<string, string> Relative directory and file names with bytes. */
    private function snapshotTree(string $root): array
    {
        $snapshot = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $entry) {
            $name = substr($entry->getPathname(), strlen($root) + 1);
            $snapshot[$name] = $entry->isLink() ? 'link:'.readlink($entry->getPathname())
                : ($entry->isDir() ? 'directory' : 'file:'.file_get_contents($entry->getPathname()));
        }
        ksort($snapshot);
        return $snapshot;
    }

    public function testFailureAfterNestedMovesRestoresFlatTree(): void
    {
        $this->createNestedFixture(2);
        $items = $this->store->itemsDir();
        mkdir($items.'/T-001/S-002');
        file_put_contents($items.'/T-001/S-002/sentinel', "do not overwrite\0");
        $before = $this->snapshotTree($items);
        $moves = [];
        $rename = static function (string $from, string $to) use (&$moves): bool {
            $result = @rename($from, $to);
            if ($result) {
                $moves[] = [$from, $to];
            }
            return $result;
        };
        [$exit, $output] = $this->runCli(['command' => 'migrate', '--force' => true], $rename);
        self::assertNotSame(0, $exit, $output);
        self::assertStringContainsString('Cannot move item: S-002', $output);
        self::assertContains([$items.'.backup/T-001', $items.'/E-001/T-001'], $moves);
        self::assertContains([$items.'.backup/S-001', $items.'/E-001/T-001/S-001'], $moves);
        self::assertSame($before, $this->snapshotTree($items));
        self::assertDirectoryDoesNotExist($items.'.backup');
    }

    public function testFailureDuringRemainingMergeRestoresFlatTree(): void
    {
        $this->createNestedFixture();
        $items = $this->store->itemsDir();
        $before = $this->snapshotTree($items);
        $moves = [];
        $failed = false;
        $rename = static function (string $from, string $to) use ($items, &$moves, &$failed): bool {
            if ($from === $items.'.backup/E-001/outputs' && !$failed) {
                $failed = true;
                return false;
            }
            $result = @rename($from, $to);
            if ($result) {
                $moves[] = [$from, $to];
            }
            return $result;
        };
        [$exit, $output] = $this->runCli(['command' => 'migrate', '--force' => true], $rename);
        self::assertNotSame(0, $exit, $output);
        self::assertTrue($failed);
        self::assertStringContainsString('Cannot move entry: E-001/outputs', $output);
        self::assertContains([$items.'.backup/S-001', $items.'/E-001/T-001/S-001'], $moves);
        self::assertContains([$items.'.backup/E-001/item.md', $items.'/E-001/item.md'], $moves);
        self::assertSame($before, $this->snapshotTree($items));
        self::assertDirectoryDoesNotExist($items.'.backup');
    }

    public function testFailureAfterRemainingWholeDirectoryRestoresFlatTree(): void
    {
        $this->createNestedFixture();
        foreach ([
            ['title' => 'Unnested', '--standalone-reason' => 'Independent'],
            ['title' => 'Other parent', '--standalone-reason' => 'Independent'],
            ['title' => 'Other child', '--kind' => 'subtask', '--parent' => 'T-003'],
        ] as $options) {
            [$exit, $output] = $this->runCli(['command' => 'new'] + $options);
            self::assertSame(0, $exit, $output);
        }
        $items = $this->store->itemsDir();
        mkdir($items.'/T-003/outputs');
        file_put_contents($items.'/T-003/outputs/evidence', 'other parent');
        $before = $this->snapshotTree($items);
        $moves = [];
        $failed = false;
        $rename = static function (string $from, string $to) use ($items, &$moves, &$failed): bool {
            if ($from === $items.'.backup/T-003/outputs') {
                $failed = true;
                return false;
            }
            $result = @rename($from, $to);
            if ($result) {
                $moves[] = [$from, $to];
            }
            return $result;
        };
        [$exit, $output] = $this->runCli(['command' => 'migrate', '--force' => true], $rename);
        self::assertNotSame(0, $exit, $output);
        self::assertTrue($failed);
        self::assertContains([$items.'.backup/T-002', $items.'/T-002'], $moves);
        self::assertContains([$items.'/T-002', $items.'.backup/T-002'], $moves);
        self::assertSame($before, $this->snapshotTree($items));
    }

    public function testDanglingSymlinkTargetIsNotOverwritten(): void
    {
        $this->createNestedFixture();
        $items = $this->store->itemsDir();
        symlink('missing-target', $items.'/T-001/S-001');
        $before = $this->snapshotTree($items);
        [$exit, $output] = $this->runCli(['command' => 'migrate', '--force' => true]);
        self::assertNotSame(0, $exit, $output);
        self::assertStringContainsString('Cannot move item: S-001', $output);
        self::assertSame($before, $this->snapshotTree($items));
        self::assertTrue(is_link($items.'/T-001/S-001'));
    }

    public function testUndoFailureRetainsBothTrees(): void
    {
        $this->createNestedFixture();
        $items = $this->store->itemsDir();
        $failedMove = false;
        $failedUndo = false;
        $rename = static function (string $from, string $to) use ($items, &$failedMove, &$failedUndo): bool {
            if ($from === $items.'.backup/E-001/outputs') {
                $failedMove = true;
                return false;
            }
            if ($from === $items.'/E-001/item.md' && $to === $items.'.backup/E-001/item.md') {
                $failedUndo = true;
                return false;
            }
            return @rename($from, $to);
        };
        [$exit, $output] = $this->runCli(['command' => 'migrate', '--force' => true], $rename);
        self::assertNotSame(0, $exit, $output);
        self::assertTrue($failedMove);
        self::assertTrue($failedUndo);
        self::assertStringContainsString('Cannot move entry: E-001/outputs', $output);
        self::assertStringContainsString('recovery failed: cannot undo move', $output);
        self::assertDirectoryExists($items);
        self::assertDirectoryExists($items.'.backup');
        self::assertFileExists($items.'/E-001/item.md');
        self::assertFileExists($items.'/E-001/T-001/S-001/outputs/evidence.bin');
        self::assertFileExists($items.'.backup/E-001/outputs/evidence.bin');
    }

    public function testMissingParentsKeepExistingLayoutRules(): void
    {
        $items = $this->store->itemsDir();
        // Deliberately invalid relationships: only the migration's path computation is under test.
        foreach (['T-001' => 'E-999', 'S-001' => 'T-001', 'S-002' => 'T-999'] as $id => $parent) {
            mkdir($items.'/'.$id);
            file_put_contents($items.'/'.$id.'/item.md', "---\nid: $id\nparent: $parent\n---\nbody $id\n");
            mkdir($items.'/'.$id.'/outputs');
            file_put_contents($items.'/'.$id.'/outputs/evidence', "bytes $id\0");
        }
        $before = $this->snapshotTree($items);
        [$exit, $output] = $this->runCli(['command' => 'migrate', '--force' => true]);
        self::assertSame(0, $exit, $output);
        self::assertStringContainsString('S-001 → T-001/S-001', $output);
        self::assertStringNotContainsString('S-002 →', $output);
        self::assertStringNotContainsString('T-001 →', $output);
        self::assertDirectoryDoesNotExist($items.'/E-999');
        self::assertDirectoryDoesNotExist($items.'/T-999');
        foreach (['T-001' => 'T-001', 'S-001' => 'T-001/S-001', 'S-002' => 'S-002'] as $id => $target) {
            foreach (['item.md', 'outputs/evidence'] as $file) {
                self::assertSame($before[$id.'/'.$file], 'file:'.file_get_contents($items.'/'.$target.'/'.$file));
            }
        }
    }

    public function testDryRunLeavesTreeUntouchedAndDisplaysOriginalPlan(): void
    {
        $this->createNestedFixture();
        $items = $this->store->itemsDir();
        $before = $this->snapshotTree($items);
        [$exit, $output] = $this->runCli(['command' => 'migrate', '--dry-run' => true]);
        self::assertSame(0, $exit, $output);
        self::assertStringContainsString("S-001 → E-001/T-001/S-001\n  T-001 → E-001/T-001", $output);
        self::assertStringContainsString('Dry run complete. No files were moved.', $output);
        self::assertSame($before, $this->snapshotTree($items));
        self::assertDirectoryDoesNotExist($items.'.backup');
    }

    /**
     * Regression: the migration previously moved only an epic's item.md when
     * the epic's nested directory had already been created by child moves,
     * orphaning `outputs/` in the backup. Now every remaining entry in a
     * pre-existing parent directory is relocated.
     */
    public function testEpicOutputsTravelWithTheEpic(): void
    {
        // Build a flat layout by hand (workflow new creates items at the
        // top level; `migrate` is what puts them nested).
        $this->runCli(['command' => 'new', 'title' => 'Epic', '--kind' => 'epic']);
        $this->runCli(['command' => 'new', 'title' => 'Task', '--parent' => 'E-001']);
        $outputs = $this->store->itemsDir().'/E-001/outputs';
        mkdir($outputs, 0777, true);
        file_put_contents($outputs.'/evidence.md', 'epic evidence');

        [$exit] = $this->runCli(['command' => 'migrate', '--force' => true]);
        self::assertSame(0, $exit);

        self::assertFileExists($outputs.'/evidence.md');
        self::assertDirectoryDoesNotExist($this->store->itemsDir().'.backup/E-001/outputs');
    }

    public function testValidatePassesOnMigratedLayout(): void
    {
        $this->runCli(['command' => 'new', 'title' => 'Epic', '--kind' => 'epic']);
        $this->runCli(['command' => 'new', 'title' => 'Task', '--parent' => 'E-001']);

        [$exit] = $this->runCli(['command' => 'migrate', '--force' => true]);
        self::assertSame(0, $exit);

        $result = (new ItemValidator($this->store))->validate();
        $errors = implode("\n", $result->errors);

        self::assertStringNotContainsString('unreadable', $errors);
        self::assertStringNotContainsString('unexpected entry', $errors);
        self::assertStringNotContainsString('item.md is missing', $errors);
    }
}
