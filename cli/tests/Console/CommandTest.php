<?php

declare(strict_types=1);

namespace Workflow\Tests\Console;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workflow\Console\Application;
use Workflow\Tests\WorkflowTestCase;

final class CommandTest extends WorkflowTestCase
{
    /**
     * @return array{0: int, 1: string}
     */
    private function runCli(array $input): array
    {
        $application = new Application();
        $application->setAutoExit(false);

        $output = new BufferedOutput();
        $exit = $application->run(new ArrayInput(['--project' => $this->root] + $input), $output);

        return [$exit, $output->fetch()];
    }

    private function idFrom(string $output): string
    {
        if (preg_match('/([ETS]-\d+)/', $output, $match) !== 1) {
            self::fail('No item id in output: '.$output);
        }

        return $match[1];
    }

    public function testHappyPathAndApprovalStability(): void
    {
        [$exit] = $this->runCli(['command' => 'init', '--name' => 'test']);
        self::assertSame(0, $exit);

        [$exit, $output] = $this->runCli(['command' => 'new', 'title' => 'Epic', '--kind' => 'epic']);
        self::assertSame(0, $exit);
        $epic = $this->idFrom($output);

        [$exit, $output] = $this->runCli([
            'command' => 'new',
            'title' => 'Build the codec',
            '--kind' => 'task',
            '--parent' => $epic,
            '--owns' => ['cli/src/'],
        ]);
        self::assertSame(0, $exit);
        $task = $this->idFrom($output);

        [$exit] = $this->runCli([
            'command' => 'set',
            'id' => $task,
            'assignments' => [
                'acceptance=Round-trips without loss; Unknown keys preserved',
                'scope_in=Parsing; Round-trip',
                'scope_out=Migration',
            ],
        ]);
        self::assertSame(0, $exit);

        [$exit] = $this->runCli(['command' => 'approve', 'id' => $task, '--by' => 'adam']);
        self::assertSame(0, $exit);

        [$exit] = $this->runCli([
            'command' => 'plan',
            'id' => $task,
            '--text' => "1. Parse the front matter.\n2. Test the round-trip.",
        ]);
        self::assertSame(0, $exit);

        [$exit] = $this->runCli([
            'command' => 'decide',
            'id' => $task,
            'decision' => 'Use symfony/yaml; it is already a dependency.',
        ]);
        self::assertSame(0, $exit);

        [$exit, $output] = $this->runCli(['command' => 'brief', 'id' => $task]);
        self::assertSame(0, $exit);
        self::assertStringContainsString('approval: current', $output);
        self::assertStringContainsString('Round-trips without loss', $output);
        self::assertStringContainsString('1. Parse the front matter.', $output);
        self::assertStringContainsString('Use symfony/yaml', $output);

        // Routine changes must not invalidate the approval.
        $this->runCli(['command' => 'set', 'id' => $task, 'assignments' => ['status=in-progress', 'phase=implement']]);
        $this->runCli([
            'command' => 'result',
            'id' => $task,
            '--role' => 'worker',
            '--status' => 'done',
            '--finding' => 'Codec implemented.',
            '--artifacts' => ['cli/src/Domain/FrontMatter.php'],
        ]);

        [$exit, $output] = $this->runCli(['command' => 'validate']);
        self::assertSame(0, $exit, $output);
        self::assertStringNotContainsString('stale', $output);

        // A scope change must invalidate it.
        $this->runCli(['command' => 'set', 'id' => $task, 'assignments' => ['acceptance=A different criterion']]);

        [$exit, $output] = $this->runCli(['command' => 'validate']);
        self::assertSame(1, $exit);
        self::assertStringContainsString('approval is stale', $output);

        [$exit, $output] = $this->runCli(['command' => 'brief', 'id' => $task]);
        self::assertSame(0, $exit);
        self::assertStringContainsString('STALE', $output);

        // Re-approval restores readiness.
        $this->runCli(['command' => 'approve', 'id' => $task, '--by' => 'adam']);
        [$exit] = $this->runCli(['command' => 'validate']);
        self::assertSame(0, $exit);
    }

    public function testResultCreatesNoExtraFiles(): void
    {
        $this->runCli(['command' => 'init', '--name' => 'test']);
        [, $output] = $this->runCli(['command' => 'new', 'title' => 'Item', '--kind' => 'epic']);
        $id = $this->idFrom($output);
        $this->runCli(['command' => 'approve', 'id' => $id]);

        $this->runCli([
            'command' => 'result',
            'id' => $id,
            '--status' => 'done',
            '--finding' => 'Done.',
            '--artifacts' => ['one.md', 'two.md'],
        ]);

        $entries = array_values(array_diff(scandir($this->root.'/.workflow/items/'.$id), ['.', '..']));
        self::assertSame(['item.md'], $entries);

        $item = file_get_contents($this->root.'/.workflow/items/'.$id.'/item.md');
        self::assertStringContainsString('Finding: Done.', $item);
        self::assertStringContainsString('artifacts:', $item);
    }

    public function testReadyRespectsDependenciesAndApproval(): void
    {
        $this->runCli(['command' => 'init', '--name' => 'test']);

        [, $output] = $this->runCli(['command' => 'new', 'title' => 'First', '--kind' => 'epic']);
        $epic = $this->idFrom($output);

        [, $output] = $this->runCli(['command' => 'new', 'title' => 'A', '--kind' => 'task', '--parent' => $epic]);
        $a = $this->idFrom($output);

        [, $output] = $this->runCli([
            'command' => 'new',
            'title' => 'B',
            '--kind' => 'task',
            '--parent' => $epic,
            '--depends' => [$a],
        ]);
        $b = $this->idFrom($output);

        $this->runCli(['command' => 'approve', 'id' => $a]);
        $this->runCli(['command' => 'approve', 'id' => $b]);

        // A is ready. B waits for A.
        [, $output] = $this->runCli(['command' => 'ready', '--json' => true]);
        $ready = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
        self::assertContains($a, array_column($ready, 'id'));
        self::assertNotContains($b, array_column($ready, 'id'));

        // Completing A makes B ready.
        $this->runCli(['command' => 'close', 'id' => $a, '--status' => 'done']);
        [, $output] = $this->runCli(['command' => 'ready', '--json' => true]);
        $ready = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
        self::assertContains($b, array_column($ready, 'id'));

        // A stale approval withholds B again.
        $this->runCli(['command' => 'set', 'id' => $b, 'assignments' => ['acceptance=changed']]);
        [, $output] = $this->runCli(['command' => 'ready', '--json' => true]);
        $ready = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
        self::assertNotContains($b, array_column($ready, 'id'));
    }

    public function testVerifierReceiptAndBoard(): void
    {
        $this->runCli(['command' => 'init', '--name' => 'test']);
        [, $output] = $this->runCli(['command' => 'new', 'title' => 'Board item', '--kind' => 'epic']);
        $id = $this->idFrom($output);
        $this->runCli(['command' => 'approve', 'id' => $id]);

        $this->runCli([
            'command' => 'result',
            'id' => $id,
            '--role' => 'verifier',
            '--status' => 'done',
            '--finding' => 'Criterion passes.',
        ]);
        $this->runCli(['command' => 'close', 'id' => $id, '--status' => 'done']);

        [$exit, $output] = $this->runCli(['command' => 'validate']);
        self::assertSame(0, $exit, $output);
        self::assertStringNotContainsString('done without a verifier receipt', $output);

        [$exit] = $this->runCli(['command' => 'board']);
        self::assertSame(0, $exit);

        $board = $this->root.'/.workflow/board.html';
        self::assertFileExists($board);
        self::assertStringContainsString('Board item', (string) file_get_contents($board));
    }

    public function testStatusCapsLongSections(): void
    {
        $this->runCli(['command' => 'init', '--name' => 'test']);

        for ($i = 1; $i <= 8; $i++) {
            $this->runCli(['command' => 'new', 'title' => 'Draft '.$i, '--kind' => 'task']);
        }

        [$exit, $output] = $this->runCli(['command' => 'status']);
        self::assertSame(0, $exit);
        self::assertStringContainsString('drafts (8)', $output);
        self::assertStringContainsString('… and 3 more', $output);
    }

    public function testVerifierBriefOmitsTheProducerClaim(): void
    {
        $this->runCli(['command' => 'init', '--name' => 'test']);
        [, $output] = $this->runCli(['command' => 'new', 'title' => 'Item', '--kind' => 'epic']);
        $id = $this->idFrom($output);
        $this->runCli(['command' => 'approve', 'id' => $id]);
        $this->runCli([
            'command' => 'result',
            'id' => $id,
            '--role' => 'worker',
            '--status' => 'done',
            '--finding' => 'Producer says it is fine.',
        ]);

        [, $output] = $this->runCli(['command' => 'brief', 'id' => $id, '--for' => 'verifier']);
        self::assertStringNotContainsString('Producer says it is fine.', $output);
        self::assertStringContainsString('producer claim: omitted', $output);
    }

    public function testClosingAParentWarnsAboutOpenChildren(): void
    {
        $this->runCli(['command' => 'init', '--name' => 'test']);
        [, $output] = $this->runCli(['command' => 'new', 'title' => 'Epic', '--kind' => 'epic']);
        $epic = $this->idFrom($output);
        [, $output] = $this->runCli([
            'command' => 'new',
            'title' => 'Child',
            '--kind' => 'task',
            '--parent' => $epic,
        ]);
        $child = $this->idFrom($output);

        [$exit, $output] = $this->runCli(['command' => 'close', 'id' => $epic, '--status' => 'done']);
        self::assertSame(0, $exit);
        self::assertStringContainsString('children still open: '.$child, $output);
    }

    public function testJsonReadsAndGraph(): void
    {
        $this->runCli(['command' => 'init', '--name' => 'test']);

        [, $output] = $this->runCli(['command' => 'new', 'title' => 'Epic', '--kind' => 'epic']);
        $epic = $this->idFrom($output);

        [, $output] = $this->runCli([
            'command' => 'new',
            'title' => 'Task',
            '--kind' => 'task',
            '--parent' => $epic,
            '--depends' => [$epic],
        ]);
        $task = $this->idFrom($output);
        $this->runCli(['command' => 'approve', 'id' => $epic]);
        $this->runCli(['command' => 'approve', 'id' => $task]);

        foreach ([
            ['command' => 'status', '--json' => true],
            ['command' => 'ready', '--json' => true],
            ['command' => 'brief', 'id' => $task, '--json' => true],
            ['command' => 'deps', 'id' => $task, '--json' => true],
            ['command' => 'validate', '--json' => true],
        ] as $input) {
            [$exit, $output] = $this->runCli($input);
            self::assertSame(0, $exit, $output);
            $decoded = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($decoded);
        }

        [$exit, $output] = $this->runCli(['command' => 'deps', '--graph' => true]);
        self::assertSame(0, $exit);
        self::assertStringContainsString(sprintf('"%s" --> "%s"', $epic, $task), $output);
    }

    public function testShowSectionAndInitIdempotence(): void
    {
        $this->runCli(['command' => 'init', '--name' => 'test']);
        [$exit, $output] = $this->runCli(['command' => 'init', '--name' => 'test']);
        self::assertSame(0, $exit);
        self::assertStringContainsString('Already initialized', $output);

        [, $output] = $this->runCli(['command' => 'new', 'title' => 'Item', '--kind' => 'epic']);
        $id = $this->idFrom($output);
        $this->runCli(['command' => 'plan', 'id' => $id, '--text' => "1. Do the work.\n2. Check it."]);

        [$exit, $output] = $this->runCli(['command' => 'show', 'id' => $id, '--section' => 'Plan']);
        self::assertSame(0, $exit);
        self::assertStringContainsString('1. Do the work.', $output);
    }

    public function testSetRejectsUnknownFieldWithoutWriting(): void
    {
        $this->runCli(['command' => 'init', '--name' => 'test']);
        [, $output] = $this->runCli(['command' => 'new', 'title' => 'Original', '--kind' => 'epic']);
        $id = $this->idFrom($output);

        [$exit] = $this->runCli(['command' => 'set', 'id' => $id, 'assignments' => ['nope=1']]);
        self::assertSame(2, $exit);
        self::assertStringContainsString('Original', (string) file_get_contents($this->root.'/.workflow/items/'.$id.'/item.md'));
    }

    public function testConcurrentWriteIsRefused(): void
    {
        $this->runCli(['command' => 'init', '--name' => 'test']);
        [, $output] = $this->runCli(['command' => 'new', 'title' => 'Item', '--kind' => 'epic']);
        $id = $this->idFrom($output);

        $store = new \Workflow\Store\ItemStore($this->root);
        $lockDirectory = $this->root.'/.workflow/.locks';
        $factory = new \Symfony\Component\Lock\LockFactory(new \Symfony\Component\Lock\Store\FlockStore($lockDirectory));
        $lock = $factory->createLock('workflow-write');
        self::assertTrue($lock->acquire(false));

        try {
            $store->mutate($id, static function ($item): void {
                $item->title = 'Should not be written';
            });
            self::fail('A concurrent write should have been refused.');
        } catch (\Workflow\Domain\WorkflowException) {
            // Expected: the lock is held by another writer.
        } finally {
            $lock->release();
        }

        $store->mutate($id, static function ($item): void {
            $item->title = 'Sequential write works';
        });
        self::assertStringContainsString('Sequential write works', (string) file_get_contents($this->root.'/.workflow/items/'.$id.'/item.md'));
    }

    public function testValidateReportsMissingItemsGracefully(): void
    {
        $this->runCli(['command' => 'init', '--name' => 'test']);

        [$exit, $output] = $this->runCli(['command' => 'validate']);
        self::assertSame(0, $exit);
        self::assertStringContainsString('OK', $output);
    }
}
