<?php

declare(strict_types=1);

namespace Workflow\Tests\Console;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workflow\Console\Application;
use Workflow\Domain\Phase;
use Workflow\Domain\Status;
use Workflow\Store\ItemStore;
use Workflow\Tests\WorkflowTestCase;

final class PlanDesignStateTest extends WorkflowTestCase
{
    private ItemStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            ['command' => 'init', '--name' => 'plan-design-state'],
            ['command' => 'new', 'title' => 'Epic', '--kind' => 'epic'],
            ['command' => 'new', 'title' => 'Target', '--parent' => 'E-001'],
            ['command' => 'plan', 'id' => 'T-001', '--text' => 'Original Plan'],
            ['command' => 'design', 'id' => 'T-001', '--text' => 'Original Design'],
            ['command' => 'approve', 'id' => 'T-001'],
            ['command' => 'set', 'id' => 'T-001', 'assignments' => ['phase=verify']],
        ] as $input) {
            $this->succeed($input);
        }
        $this->store = new ItemStore($this->root);
    }

    private function runCli(array|string $input): array
    {
        $application = new Application();
        $application->setAutoExit(false);
        $output = new BufferedOutput();
        $input = is_array($input)
            ? new ArrayInput(['--project' => $this->root] + $input)
            : new StringInput('--project '.escapeshellarg($this->root).' '.$input);
        $exit = $application->run($input, $output);

        return [$exit, $output->fetch()];
    }

    private function succeed(array $input): void
    {
        [$exit, $output] = $this->runCli($input);
        self::assertSame(0, $exit, $output);
    }

    private function source(string $source, string $content): array
    {
        if ($source === 'text') {
            return ['--text' => $content];
        }

        $path = $this->root.'/content.md';
        file_put_contents($path, $content);

        return ['--file' => $path];
    }

    private function staleBoard(): void
    {
        file_put_contents($this->root.'/.workflow/board.html', 'stale board');
    }

    private function assertBoardRefreshed(Phase $phase): void
    {
        $board = (string) file_get_contents($this->root.'/.workflow/board.html');
        self::assertStringNotContainsString('stale board', $board);
        self::assertStringContainsString('task · '.$phase->value.' · p2', $board);
    }

    private function snapshot(): array
    {
        $tree = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root.'/.workflow', \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $file) {
            $path = substr($file->getPathname(), strlen($this->root));
            if (str_starts_with($path, '/.workflow/.locks')) {
                continue;
            }
            $tree[$path] = $file->isDir() ? null : file_get_contents($file->getPathname());
        }
        ksort($tree);

        return $tree;
    }

    private function verify(): void
    {
        $this->succeed([
            'command' => 'result', 'id' => 'T-001', '--role' => 'verifier', '--status' => 'done',
            '--finding' => 'Checked frozen target.',
        ]);
        self::assertNotNull($this->store->load('T-001')->verifiedAt);
    }

    public static function commands(): iterable
    {
        yield 'plan' => ['plan'];
        yield 'design' => ['design'];
    }

    public static function contentOnlyInputs(): iterable
    {
        foreach (['plan', 'design'] as $command) {
            foreach (['text', 'file'] as $source) {
                foreach ([Status::Draft, Status::Todo, Status::InProgress, Status::Cancelled] as $status) {
                    yield "$command $source {$status->value}" => [$command, $source, $status];
                }
            }
        }
    }

    #[DataProvider('contentOnlyInputs')]
    public function testNoFlagsReplaceOnlyContentWithoutRewindingState(string $command, string $source, Status $status): void
    {
        $this->succeed(['command' => 'set', 'id' => 'T-001', 'assignments' => ['status='.$status->value]]);
        $before = $this->store->load('T-001');
        $this->staleBoard();
        $content = "\n  Revised content\n\n### Checks\n\nKeep $1 and \\ literal.\n";

        $this->succeed(['command' => $command, 'id' => 'T-001'] + $this->source($source, $content));

        $after = $this->store->load('T-001');
        self::assertSame(trim($content), $after->section(ucfirst($command)));
        $other = $command === 'plan' ? 'Design' : 'Plan';
        self::assertSame('Original '.$other, $after->section($other));
        self::assertSame($before->status, $after->status);
        self::assertSame($before->phase, $after->phase);
        self::assertSame($before->approval?->toArray(), $after->approval?->toArray());
        self::assertTrue($after->isApproved());
        $this->assertBoardRefreshed(Phase::Verify);
    }

    public static function phases(): iterable
    {
        foreach (['plan', 'design'] as $command) {
            foreach (Phase::cases() as $phase) {
                yield "$command {$phase->value}" => [$command, $phase];
            }
        }
    }

    #[DataProvider('phases')]
    public function testExplicitPhaseDoesNotInferStatusOrRequirePhaseOrder(string $command, Phase $phase): void
    {
        $approval = $this->store->load('T-001')->approval?->toArray();
        $this->staleBoard();
        $this->succeed([
            'command' => $command, 'id' => 'T-001', '--text' => 'Revised content',
            '--phase' => $phase->value,
        ]);

        $item = $this->store->load('T-001');
        self::assertSame('Revised content', $item->section(ucfirst($command)));
        self::assertSame($phase, $item->phase);
        self::assertSame(Status::Todo, $item->status);
        self::assertSame($approval, $item->approval?->toArray());
        self::assertTrue($item->isApproved());
        $this->assertBoardRefreshed($phase);
    }

    #[DataProvider('commands')]
    public function testStartAloneChangesStatusWithoutChangingPhase(string $command): void
    {
        $approval = $this->store->load('T-001')->approval?->toArray();
        $this->staleBoard();
        $this->succeed(['command' => $command, 'id' => 'T-001', '--text' => 'Revised content', '--start' => true]);

        $item = $this->store->load('T-001');
        self::assertSame('Revised content', $item->section(ucfirst($command)));
        self::assertSame(Status::InProgress, $item->status);
        self::assertSame(Phase::Verify, $item->phase);
        self::assertSame($approval, $item->approval?->toArray());
        self::assertTrue($item->isApproved());
        $this->assertBoardRefreshed(Phase::Verify);
        self::assertStringContainsString('In progress <span class="count">1</span>', (string) file_get_contents($this->root.'/.workflow/board.html'));
    }

    public static function flaggedSources(): iterable
    {
        foreach (['plan', 'design'] as $command) {
            foreach (['text', 'file'] as $source) {
                yield "$command $source" => [$command, $source];
            }
        }
    }

    #[DataProvider('flaggedSources')]
    public function testCombinedFlagsUpdateContentAndStateAndReuseVerificationInvalidation(string $command, string $source): void
    {
        $this->verify();
        $before = $this->store->load('T-001');
        $this->staleBoard();
        $this->succeed([
            'command' => $command, 'id' => 'T-001', '--phase' => 'implement', '--start' => true,
        ] + $this->source($source, 'Revised content'));

        $item = $this->store->load('T-001');
        self::assertSame('Revised content', $item->section(ucfirst($command)));
        self::assertSame(Phase::Implement, $item->phase);
        self::assertSame(Status::InProgress, $item->status);
        self::assertNull($item->verifiedAt);
        self::assertSame($before->approval?->toArray(), $item->approval?->toArray());
        self::assertSame($before->scopeHash(), $item->scopeHash());
        self::assertTrue($item->isApproved());
        $this->assertBoardRefreshed(Phase::Implement);
    }

    #[DataProvider('commands')]
    public function testFlagsAddNoApprovalReadinessOrMissingSectionGate(string $command): void
    {
        $this->succeed([
            'command' => 'new', 'title' => 'Unapproved dependent', '--parent' => 'E-001', '--depends' => ['T-001'],
        ]);
        $this->succeed([
            'command' => $command, 'id' => 'T-002', '--text' => 'First content', '--phase' => 'verify', '--start' => true,
        ]);

        $item = $this->store->load('T-002');
        self::assertSame('First content', $item->section(ucfirst($command)));
        self::assertNull($item->approval);
        self::assertSame(Status::InProgress, $item->status);
        self::assertSame(Phase::Verify, $item->phase);
        self::assertSame(['T-001'], $item->dependsOn);
    }

    public static function invalidInputs(): iterable
    {
        foreach (['plan', 'design'] as $command) {
            foreach (['no source', 'both sources', 'missing file', 'directory', 'invalid phase', 'empty phase',
                'unsupported status', 'unsupported close', 'missing phase value', 'start value', 'extra argument'] as $case) {
                yield "$command $case" => [$command, $case];
            }
        }
    }

    #[DataProvider('invalidInputs')]
    public function testInvalidInputsPreserveAllRecordAndBoardBytes(string $command, string $case): void
    {
        $source = $this->source('file', 'Revised content');
        $options = match ($case) {
            'no source' => [],
            'both sources' => ['--text' => 'Revised content'] + $source,
            'missing file' => ['--file' => $this->root.'/missing.md'],
            'directory' => ['--file' => $this->root],
            'invalid phase' => ['--text' => 'Revised content', '--phase' => 'release'],
            'empty phase' => ['--text' => 'Revised content', '--phase' => ''],
            'unsupported status' => ['--text' => 'Revised content', '--status' => 'done'],
            'unsupported close' => ['--text' => 'Revised content', '--close' => true],
            default => ['--text' => 'Revised content'],
        };
        $input = ['command' => $command, 'id' => 'T-001'] + $options + ['--phase' => 'implement', '--start' => true];
        $input = match ($case) {
            'missing phase value' => "$command T-001 --text Revised --start --phase",
            'start value' => "$command T-001 --text Revised --phase implement --start=yes",
            'extra argument' => "$command T-001 --text Revised --phase implement --start extra",
            default => $input,
        };
        $before = $this->snapshot();

        [$exit, $output] = $this->runCli($input);

        self::assertNotSame(0, $exit, $output);
        self::assertSame($before, $this->snapshot());
    }

    public static function doneChanges(): iterable
    {
        foreach (['plan', 'design'] as $command) {
            yield "$command content" => [$command, 'Revised content', []];
            yield "$command content and phase" => [$command, 'Revised content', ['--phase' => 'implement']];
            yield "$command combined" => [$command, 'Revised content', ['--phase' => 'implement', '--start' => true]];
            yield "$command reopen only" => [$command, 'Original '.ucfirst($command), ['--start' => true]];
        }
    }

    #[DataProvider('doneChanges')]
    public function testCompletedMaterialEditsAndReopeningUseSharedFollowUpRefusal(string $command, string $content, array $flags): void
    {
        $this->verify();
        $this->succeed(['command' => 'close', 'id' => 'T-001']);
        $before = $this->snapshot();

        [$exit, $output] = $this->runCli(['command' => $command, 'id' => 'T-001', '--text' => $content] + $flags);

        self::assertNotSame(0, $exit, $output);
        self::assertStringContainsString('create a new follow-up item', $output);
        self::assertStringContainsString('explicitly approve its scope', $output);
        self::assertSame($before, $this->snapshot());
        self::assertSame(['E-001', 'T-001'], $this->store->ids());
    }

    public function testDesignApproveRecordsHashOfTheJustWrittenDesignInOneMutation(): void
    {
        [$exit, $output] = $this->runCli([
            'command' => 'design', 'id' => 'T-001', '--text' => 'Approved content',
            '--approve' => true, '--by' => 'adam', '--confirmation' => 'Proceed with this design',
        ]);
        self::assertSame(0, $exit, $output);

        $item = $this->store->load('T-001');
        self::assertSame('Approved content', $item->section('Design'));
        self::assertSame($item->designHash(), $item->designApproval?->hash);
        self::assertSame('adam', $item->designApproval?->by);
        self::assertSame('Proceed with this design', $item->designApproval?->confirmation);
        self::assertTrue($item->isDesignApproved());
        self::assertSame(Status::Todo, $item->status);
        self::assertSame(Phase::Verify, $item->phase);
        // The design gate is separate from the scope approval record.
        self::assertTrue($item->isApproved());
        $raw = (string) file_get_contents($this->store->itemPath('T-001'));
        self::assertStringContainsString('design_hash', $raw);
        self::assertStringContainsString('design_approval', $raw);
        self::assertStringContainsString('Design updated for T-001', $output);
        self::assertStringContainsString('hash: ', $output);
        self::assertStringContainsString('Recorded design approval by adam at ', $output);
        self::assertStringContainsString('— this certifies an explicit user confirmation of design hash ', $output);
        self::assertStringContainsString('if confirmation was implicit, record it via workflow log T-001', $output);
    }

    public function testBareDesignApproveDefaultsToUnspecifiedAndSkipsConfirmation(): void
    {
        $this->succeed(['command' => 'design', 'id' => 'T-001', '--text' => 'Approved content', '--approve' => true]);

        $item = $this->store->load('T-001');
        self::assertSame('unspecified', $item->designApproval?->by);
        self::assertNull($item->designApproval?->confirmation);
        self::assertTrue($item->isDesignApproved());
        self::assertStringNotContainsString('confirmation:', (string) file_get_contents($this->store->itemPath('T-001')));
    }

    public function testDesignApproveComposesWithPhaseAndStartFlags(): void
    {
        $this->staleBoard();
        $this->succeed([
            'command' => 'design', 'id' => 'T-001', '--text' => 'Approved content',
            '--phase' => 'implement', '--start' => true, '--approve' => true,
        ]);

        $item = $this->store->load('T-001');
        self::assertSame('Approved content', $item->section('Design'));
        self::assertSame(Phase::Implement, $item->phase);
        self::assertSame(Status::InProgress, $item->status);
        self::assertTrue($item->isDesignApproved());
        self::assertSame($item->designHash(), $item->designApproval?->hash);
        self::assertTrue($item->isApproved());
        $this->assertBoardRefreshed(Phase::Implement);
    }

    public function testContentOnlyDesignWriteStalesTheDesignApprovalButNotScopeApproval(): void
    {
        $this->succeed(['command' => 'design', 'id' => 'T-001', '--text' => 'Approved content', '--approve' => true]);
        $this->succeed(['command' => 'design', 'id' => 'T-001', '--text' => 'Revised content']);

        $item = $this->store->load('T-001');
        self::assertSame('Revised content', $item->section('Design'));
        self::assertTrue($item->hasStaleDesignApproval());
        self::assertTrue($item->isApproved());
        self::assertSame(Status::Todo, $item->status);
        self::assertSame(Phase::Verify, $item->phase);

        [$exit, $output] = $this->runCli(['command' => 'brief', 'id' => 'T-001', '--for' => 'worker']);
        self::assertSame(2, $exit, $output);
        self::assertStringContainsString('stale design approval', $output);
    }

    public function testRoutineWritesKeepTheDesignApprovalCurrent(): void
    {
        $this->succeed(['command' => 'design', 'id' => 'T-001', '--text' => 'Approved content', '--approve' => true]);
        $this->succeed(['command' => 'set', 'id' => 'T-001', 'assignments' => ['priority=7']]);
        $this->succeed([
            'command' => 'result', 'id' => 'T-001', '--role' => 'worker', '--status' => 'done',
            '--finding' => 'Produced against the approved design.',
        ]);
        $this->succeed(['command' => 'plan', 'id' => 'T-001', '--text' => 'Revised Plan']);

        $item = $this->store->load('T-001');
        self::assertTrue($item->isDesignApproved());
        self::assertTrue($item->isApproved());
    }

    public function testDesignApproveRefusesEpicsAndPreservesAllBytes(): void
    {
        $before = $this->snapshot();

        [$exit, $output] = $this->runCli([
            'command' => 'design', 'id' => 'E-001', '--text' => 'Epic scope map', '--approve' => true,
        ]);

        self::assertSame(2, $exit, $output);
        self::assertStringContainsString('tasks and subtasks', $output);
        self::assertSame($before, $this->snapshot());
        self::assertNull($this->store->load('E-001')->designApproval);
    }

    public function testDesignApproveOnDoneUsesTheSharedFollowUpRefusal(): void
    {
        $this->verify();
        $this->succeed(['command' => 'close', 'id' => 'T-001']);
        $before = $this->snapshot();

        [$exit, $output] = $this->runCli([
            'command' => 'design', 'id' => 'T-001', '--text' => 'Original Design', '--approve' => true,
        ]);

        self::assertSame(2, $exit, $output);
        self::assertStringContainsString('create a new follow-up item', $output);
        self::assertStringContainsString('explicitly approve its scope', $output);
        self::assertSame($before, $this->snapshot());
    }

    public function testDesignApproveDoesNotPromoteDraftStatus(): void
    {
        $this->succeed(['command' => 'new', 'title' => 'Draft task', '--parent' => 'E-001']);
        $this->succeed(['command' => 'design', 'id' => 'T-002', '--text' => 'Approved content', '--approve' => true]);

        $item = $this->store->load('T-002');
        self::assertSame(Status::Draft, $item->status);
        self::assertTrue($item->isDesignApproved());
        self::assertNull($item->approval);
    }

    #[DataProvider('commands')]
    public function testUnchangedCompletedContentWithPhaseOnlyIsNotANewHistoryGate(string $command): void
    {
        $this->verify();
        $this->succeed(['command' => 'close', 'id' => 'T-001']);
        $before = $this->store->load('T-001');
        $this->succeed([
            'command' => $command, 'id' => 'T-001', '--text' => 'Original '.ucfirst($command), '--phase' => 'plan',
        ]);

        $item = $this->store->load('T-001');
        self::assertSame(Status::Done, $item->status);
        self::assertSame(Phase::Plan, $item->phase);
        self::assertSame($before->verifiedAt, $item->verifiedAt);
        self::assertSame($before->approval?->toArray(), $item->approval?->toArray());
    }

    #[DataProvider('commands')]
    public function testSetRemainsAvailableForManualStateCorrections(string $command): void
    {
        $this->succeed([
            'command' => $command, 'id' => 'T-001', '--text' => 'Revised content', '--phase' => 'implement', '--start' => true,
        ]);
        $this->succeed(['command' => 'set', 'id' => 'T-001', 'assignments' => ['status=todo', 'phase=explore']]);

        $item = $this->store->load('T-001');
        self::assertSame('Revised content', $item->section(ucfirst($command)));
        self::assertSame(Status::Todo, $item->status);
        self::assertSame(Phase::Explore, $item->phase);
        self::assertTrue($item->isApproved());
    }
}
