<?php

declare(strict_types=1);

namespace Workflow\Tests\Console;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workflow\Console\Application;
use Workflow\Domain\FrontMatter;
use Workflow\Domain\ItemLifecycle;
use Workflow\Domain\Status;
use Workflow\Store\ItemStore;
use Workflow\Tests\WorkflowTestCase;

final class LifecycleCommandTest extends WorkflowTestCase
{
    private ItemStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            ['command' => 'init', '--name' => 'lifecycle'],
            ['command' => 'new', 'title' => 'Epic', '--kind' => 'epic'],
            ['command' => 'new', 'title' => 'Target', '--parent' => 'E-001'],
            ['command' => 'new', 'title' => 'Dependency', '--parent' => 'E-001'],
            ['command' => 'set', 'id' => 'T-001', 'assignments' => ['acceptance=Criterion']],
            ['command' => 'plan', 'id' => 'T-001', '--text' => 'Original Plan'],
            ['command' => 'design', 'id' => 'T-001', '--text' => 'Original Design'],
            ['command' => 'approve', 'id' => 'T-001'],
        ] as $input) {
            [$exit, $output] = $this->runCli($input);
            self::assertSame(0, $exit, $output);
        }
        $this->store = new ItemStore($this->root);
    }

    private function runCli(array $input): array
    {
        $application = new Application();
        $application->setAutoExit(false);
        $output = new BufferedOutput();
        $exit = $application->run(new ArrayInput(['--project' => $this->root] + $input), $output);

        return [$exit, $output->fetch()];
    }

    private function verify(array $artifacts = []): void
    {
        [$exit, $output] = $this->runCli([
            'command' => 'result', 'id' => 'T-001', '--role' => 'verifier', '--status' => 'done',
            '--finding' => 'Independently checked.', '--artifacts' => $artifacts,
        ]);
        self::assertSame(0, $exit, $output);
        self::assertTrue(ItemLifecycle::hasCurrentVerification($this->store->load('T-001')));
    }

    private function close(): void
    {
        [$exit, $output] = $this->runCli(['command' => 'close', 'id' => 'T-001']);
        self::assertSame(0, $exit, $output);
        self::assertSame(Status::Done, $this->store->load('T-001')->status);
    }

    private function tree(): array
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

    public static function targetChanges(): iterable
    {
        foreach (['title=Changed', 'outcome=Changed', 'parent=none', 'scope_in=Changed', 'scope_out=Changed',
            'acceptance=Changed', 'depends_on=T-002', 'owns=changed.php'] as $assignment) {
            yield $assignment => [['command' => 'set', 'assignments' => [$assignment]]];
        }
        yield 'Plan' => [['command' => 'plan', '--text' => 'Changed Plan']];
        yield 'Design' => [['command' => 'design', '--text' => 'Changed Design']];
        yield 'Decisions' => [['command' => 'decide', 'decision' => 'Changed decision']];
    }

    #[DataProvider('targetChanges')]
    public function testOpenTargetChangesInvalidateVerificationButNotApprovalMetadata(array $input): void
    {
        $this->verify();
        $approval = $this->store->load('T-001')->approval;
        [$exit, $output] = $this->runCli($input + ['id' => 'T-001']);
        self::assertSame(0, $exit, $output);
        self::assertNull($this->store->load('T-001')->verifiedAt);
        self::assertEquals($approval, $this->store->load('T-001')->approval);
        if (in_array($input['command'], ['plan', 'design', 'decide'], true)
            || ($input['assignments'][0] ?? '') === 'owns=changed.php') {
            self::assertTrue($this->store->load('T-001')->isApproved());
        }
        $before = $this->tree();
        [$exit, $output] = $this->runCli(['command' => 'close', 'id' => 'T-001']);
        self::assertSame(1, $exit, $output);
        self::assertStringContainsString('no verifier receipt for the current target', $output);
        self::assertSame($before, $this->tree());
    }

    public static function invalidatingReceipts(): iterable
    {
        foreach (['worker', 'explorer', 'verifier'] as $role) {
            foreach (['done', 'failed', 'blocked', 'needs-decision'] as $status) {
                if ($role !== 'verifier' || $status !== 'done') {
                    yield $role.' '.$status => [$role, $status];
                }
            }
        }
    }

    #[DataProvider('invalidatingReceipts')]
    public function testReceiptClearsProofWithoutInferringStatus(string $role, string $status): void
    {
        $this->verify();
        $statusBefore = $this->store->load('T-001')->status;
        [$exit, $output] = $this->runCli([
            'command' => 'result', 'id' => 'T-001', '--role' => $role, '--status' => $status,
            '--finding' => 'New receipt.', '--artifacts' => ['new-report.md'],
        ]);
        self::assertSame(0, $exit, $output);
        $item = $this->store->load('T-001');
        self::assertNull($item->verifiedAt);
        self::assertSame($statusBefore, $item->status);
        self::assertContains('new-report.md', $item->artifacts);
    }

    public function testNormalVerifyPhaseResultCloseSequenceVerifiesResultingArtifactReferences(): void
    {
        [$exit, $output] = $this->runCli(['command' => 'set', 'id' => 'T-001', 'assignments' => ['phase=verify']]);
        self::assertSame(0, $exit, $output);
        self::assertSame(Status::Todo, $this->store->load('T-001')->status);
        $this->verify(['verification.md']);
        $proof = $this->store->load('T-001')->verifiedAt;
        [$exit, $output] = $this->runCli(['command' => 'set', 'id' => 'T-001', 'assignments' => ['phase=verify', 'priority=7']]);
        self::assertSame(0, $exit, $output);
        [$exit, $output] = $this->runCli(['command' => 'close', 'id' => 'T-001', '--reason' => 'Checked.', '--json' => true]);
        self::assertSame(0, $exit, $output);
        self::assertTrue(json_decode($output, true, flags: JSON_THROW_ON_ERROR)['verified']);
        $item = $this->store->load('T-001');
        self::assertSame(Status::Done, $item->status);
        self::assertSame($proof, $item->verifiedAt);
        self::assertContains('verification.md', $item->artifacts);
        self::assertStringNotContainsString('Verification exception', $item->body);
        self::assertTrue($item->isApproved());
    }

    public function testSetDoneIsAtomicAndCanCompleteOnlyAfterCurrentVerification(): void
    {
        $before = $this->tree();
        [$exit, $output] = $this->runCli(['command' => 'set', 'id' => 'T-001', 'assignments' => ['priority=9', 'status=done']]);
        self::assertSame(2, $exit, $output);
        self::assertSame($before, $this->tree());
        $this->verify();
        $before = $this->tree();
        [$exit, $output] = $this->runCli(['command' => 'set', 'id' => 'T-001', 'assignments' => ['status=done', 'title=Changed']]);
        self::assertSame(2, $exit, $output);
        self::assertSame($before, $this->tree());
        [$exit, $output] = $this->runCli(['command' => 'set', 'id' => 'T-001', 'assignments' => ['status=done']]);
        self::assertSame(0, $exit, $output);
        self::assertTrue(ItemLifecycle::hasCurrentVerification($this->store->load('T-001')));
    }

    public static function doneMutations(): iterable
    {
        yield from self::targetChanges();
        foreach (['draft', 'todo', 'in-progress', 'cancelled'] as $status) {
            yield 'reopen '.$status => [['command' => 'set', 'assignments' => ['status='.$status]]];
        }
        foreach (self::invalidatingReceipts() as $name => [$role, $status]) {
            yield 'receipt '.$name => [[
                'command' => 'result', '--role' => $role, '--status' => $status, '--finding' => 'New work.',
            ]];
        }
        yield 'forced cancellation' => [['command' => 'close', '--status' => 'cancelled', '--force' => true]];
    }

    #[DataProvider('doneMutations')]
    public function testDoneMaterialWorkRequiresExplicitFollowUpAndPreservesBytes(array $input): void
    {
        $this->verify();
        $this->close();
        $before = $this->tree();
        [$exit, $output] = $this->runCli($input + ['id' => 'T-001']);
        self::assertNotSame(0, $exit, $output);
        self::assertStringContainsString('create a new follow-up item', $output);
        self::assertSame($before, $this->tree());
        self::assertSame(['E-001', 'T-001', 'T-002'], $this->store->ids());
    }

    public function testDoneAllowsPhasePriorityAndNonmaterialProgressButNotNewVerifierTargets(): void
    {
        $this->verify(['verification.md']);
        $this->close();
        $proof = $this->store->load('T-001')->verifiedAt;
        foreach ([
            ['command' => 'set', 'assignments' => ['priority=1', 'phase=implement']],
            ['command' => 'log', 'message' => 'Historical handoff note.'],
        ] as $input) {
            [$exit, $output] = $this->runCli($input + ['id' => 'T-001']);
            self::assertSame(0, $exit, $output);
        }
        self::assertSame($proof, $this->store->load('T-001')->verifiedAt);
        self::assertSame(Status::Done, $this->store->load('T-001')->status);
        $this->verify(['verification.md']);
        $before = $this->tree();
        [$exit, $output] = $this->runCli([
            'command' => 'result', 'id' => 'T-001', '--role' => 'verifier', '--status' => 'done',
            '--finding' => 'New target.', '--artifacts' => ['different-target.md'],
        ]);
        self::assertSame(1, $exit, $output);
        self::assertStringContainsString('create a new follow-up item', $output);
        self::assertSame($before, $this->tree());
    }

    public function testForceRecordsUnverifiedExceptionAndNeverFabricatesVerifierSuccess(): void
    {
        [$exit, $output] = $this->runCli([
            'command' => 'result', 'id' => 'T-001', '--role' => 'worker', '--status' => 'done', '--finding' => 'Produced.',
        ]);
        self::assertSame(0, $exit, $output);
        [$exit, $output] = $this->runCli([
            'command' => 'close', 'id' => 'T-001', '--force' => true, '--reason' => 'Explicit exception.', '--json' => true,
        ]);
        self::assertSame(0, $exit, $output);
        self::assertFalse(json_decode($output, true, flags: JSON_THROW_ON_ERROR)['verified']);
        $item = $this->store->load('T-001');
        self::assertNull($item->verifiedAt);
        self::assertSame('worker', $item->lastResult['role']);
        self::assertStringContainsString('Verification exception: explicit --force', $item->section('Progress'));
        self::assertStringContainsString('Explicit exception.', $item->section('Progress'));
        [, $output] = $this->runCli(['command' => 'validate']);
        self::assertStringContainsString('done without a verifier receipt', $output);
    }

    public function testForceCannotBypassStructuralRefusal(): void
    {
        $item = $this->store->load('T-001');
        $item->parent = 'T-002';
        file_put_contents($this->store->itemPath('T-001'), FrontMatter::render($item->toArray(), $item->body));
        $before = $this->tree();
        [$exit, $output] = $this->runCli(['command' => 'close', 'id' => 'T-001', '--force' => true]);
        self::assertSame(1, $exit, $output);
        self::assertStringContainsString('task parent must be a epic', $output);
        self::assertSame($before, $this->tree());
    }

    public function testClosureCannotClaimCurrentProofWhenReasonChangesTrackedSections(): void
    {
        $this->verify();
        $reason = "Additional text\n\n## Design\n\nA second material design";
        $before = $this->tree();
        [$exit, $output] = $this->runCli(['command' => 'close', 'id' => 'T-001', '--reason' => $reason]);
        self::assertSame(1, $exit, $output);
        self::assertSame($before, $this->tree());
        [$exit, $output] = $this->runCli([
            'command' => 'close', 'id' => 'T-001', '--reason' => $reason, '--force' => true, '--json' => true,
        ]);
        self::assertSame(0, $exit, $output);
        self::assertFalse(json_decode($output, true, flags: JSON_THROW_ON_ERROR)['verified']);
        $item = $this->store->load('T-001');
        self::assertNull($item->verifiedAt);
        self::assertStringContainsString('Verification exception: explicit --force', $item->section('Progress'));
    }

    public function testLegacyDoneNoteDoesNotClaimANewForceException(): void
    {
        $item = $this->store->load('T-001');
        $item->status = Status::Done;
        file_put_contents($this->store->itemPath('T-001'), FrontMatter::render($item->toArray(), $item->body));
        [$exit, $output] = $this->runCli(['command' => 'close', 'id' => 'T-001', '--reason' => 'Historical note.']);
        self::assertSame(0, $exit, $output);
        self::assertStringContainsString('existing completed history; no new verification', $output);
        self::assertStringNotContainsString('forced', $output);
        $after = $this->store->load('T-001');
        self::assertNull($after->verifiedAt);
        self::assertStringNotContainsString('Verification exception', $after->body);
    }

    public function testUnrelatedMalformedRecordsDoNotTurnCommittedClosureIntoAFailure(): void
    {
        mkdir($this->store->itemsDir().'/T-090');
        $raw = FrontMatter::render([
            'id' => 'T-091', 'kind' => 'task', 'parent' => 'E-001', 'status' => 'not-a-status',
        ], '');
        file_put_contents($this->store->itemPath('T-090'), $raw);
        $this->verify();
        [$exit, $output] = $this->runCli(['command' => 'close', 'id' => 'T-001']);
        self::assertSame(0, $exit, $output);
        self::assertStringContainsString('child status was not fully checked', $output);
        self::assertTrue(ItemLifecycle::hasCurrentVerification($this->store->load('T-001')));
        self::assertSame(Status::Done, $this->store->load('T-001')->status);
        self::assertSame($raw, file_get_contents($this->store->itemPath('T-090')));
    }

    public function testFollowUpIsCreatedOnlyByAnExplicitNewCommand(): void
    {
        $this->verify();
        $this->close();
        $history = file_get_contents($this->store->itemPath('T-001'));
        [$exit, $output] = $this->runCli(['command' => 'set', 'id' => 'T-001', 'assignments' => ['status=todo']]);
        self::assertSame(2, $exit, $output);
        self::assertFalse($this->store->exists('T-003'));
        [$exit, $output] = $this->runCli([
            'command' => 'new', 'title' => 'Explicit follow-up', '--parent' => 'E-001', '--depends' => ['T-001'],
        ]);
        self::assertSame(0, $exit, $output);
        self::assertSame(Status::Draft, $this->store->load('T-003')->status);
        self::assertNull($this->store->load('T-003')->approval);
        self::assertSame($history, file_get_contents($this->store->itemPath('T-001')));
    }
}
