<?php

declare(strict_types=1);

namespace Workflow\Tests\Store;

use PHPUnit\Framework\Attributes\DataProvider;
use Workflow\Domain\Approval;
use Workflow\Domain\FrontMatter;
use Workflow\Domain\Item;
use Workflow\Domain\ItemLifecycle;
use Workflow\Domain\Kind;
use Workflow\Domain\Phase;
use Workflow\Domain\Status;
use Workflow\Domain\WorkflowException;
use Workflow\Store\ItemStore;
use Workflow\Tests\WorkflowTestCase;
use Workflow\Validate\ItemValidator;

final class LifecycleWriteTest extends WorkflowTestCase
{
    private const AT = '2026-10-21T00:00:00+00:00';
    private ItemStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = new ItemStore($this->root);
        foreach (['E-001', 'E-002'] as $id) {
            $this->store->create(Item::fromArray(['id' => $id, 'kind' => 'epic'], ''));
        }
        $this->store->create($this->make('T-001'));
        $this->store->create($this->make('T-002'));
        file_put_contents($this->root.'/target.php', 'Frozen target');
    }

    private function make(string $id): Item
    {
        $item = Item::fromArray([
            'id' => $id, 'kind' => 'task', 'parent' => 'E-001', 'status' => 'todo', 'title' => 'Original',
            'owns' => ['target.php'], 'artifacts' => ['target.php'], 'acceptance' => ['criterion'],
        ], "## Plan\n\nOriginal Plan\n\n## Design\n\nOriginal Design\n\n## Decisions\n\nOriginal Decisions\n\n## Progress\n");
        $item->approval = new Approval(self::AT, 'user', $item->scopeHash());

        return $item;
    }

    private function receipt(string $role = 'verifier', string $status = 'done'): array
    {
        return ['at' => self::AT, 'role' => $role, 'status' => $status, 'finding' => 'Checked.'];
    }

    private function record(string $role = 'verifier', string $status = 'done', array $artifacts = []): void
    {
        $this->store->recordReceipt('T-001', $this->receipt($role, $status), $artifacts, '- '.self::AT.' · '.$role.' · '.$status);
    }

    /** Intentional raw legacy fixture, not a supported write route. */
    private function raw(Item $item, ?string $directoryId = null): void
    {
        $path = $this->store->itemPath($directoryId ?? $item->id);
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, FrontMatter::render($item->toArray(), $item->body));
    }

    private function tree(): array
    {
        $tree = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->store->itemsDir(), \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $file) {
            $path = $file->getPathname();
            $tree[substr($path, strlen($this->store->itemsDir()))] = $file->isDir() ? null : file_get_contents($path);
        }
        ksort($tree);

        return $tree;
    }

    public static function completionAttempts(): iterable
    {
        foreach (['create', 'save-new', 'save', 'mutate'] as $route) {
            foreach (['absent', 'timestamp', 'verifier-shaped metadata'] as $proof) {
                yield $route.' '.$proof => [$route, $proof];
            }
        }
    }

    #[DataProvider('completionAttempts')]
    public function testOrdinaryWritesCannotMintProofOrCompleteWithoutIt(string $route, string $proof): void
    {
        $id = in_array($route, ['create', 'save-new'], true) ? 'T-099' : 'T-001';
        $change = function (Item $item) use ($proof): void {
            $item->status = Status::Done;
            $item->priority = 9;
            if ($proof !== 'absent') {
                $item->verifiedAt = self::AT;
            }
            if ($proof === 'verifier-shaped metadata') {
                $item->lastResult = $this->receipt();
            }
        };
        $before = $this->tree();
        try {
            if ($route === 'mutate') {
                $this->store->mutate($id, $change);
            } else {
                $candidate = $this->make($id);
                $change($candidate);
                $this->store->{$route === 'save-new' ? 'save' : $route}($candidate);
            }
            self::fail('Completion bypassed current verification.');
        } catch (WorkflowException $exception) {
            self::assertStringContainsString('no verifier receipt for the current target', $exception->getMessage());
        }
        self::assertSame($before, $this->tree());
    }

    public static function targetWrites(): iterable
    {
        foreach (['save', 'mutate'] as $route) {
            foreach (['parent', 'title', 'outcome', 'scopeIn', 'scopeOut', 'acceptance', 'dependsOn', 'owns', 'artifacts',
                'Plan', 'Design', 'Decisions', 'duplicate'] as $field) {
                yield $route.' '.$field => [$route, $field];
            }
        }
    }

    private function change(Item $item, string $field): void
    {
        if (in_array($field, ['Plan', 'Design', 'Decisions'], true)) {
            $item->body = str_replace('Original '.$field, 'Changed '.$field, $item->body);
        } elseif ($field === 'duplicate') {
            $item->body .= "\n## Design\n\nChanged duplicate\n";
        } else {
            $item->{$field} = match ($field) {
                'parent' => 'E-002',
                'title', 'outcome' => 'Changed',
                'dependsOn' => ['T-002'],
                default => ['changed'],
            };
        }
    }

    #[DataProvider('targetWrites')]
    public function testTargetEditsClearProofButNotApprovalRecords(string $route, string $field): void
    {
        $this->record();
        $before = $this->store->load('T-001');
        if ($route === 'save') {
            $candidate = clone $before;
            $this->change($candidate, $field);
            $this->store->save($candidate);
        } else {
            $this->store->mutate('T-001', fn (Item $item) => $this->change($item, $field));
        }
        $after = $this->store->load('T-001');
        self::assertNull($after->verifiedAt);
        self::assertEquals($before->approval, $after->approval);
        if (in_array($field, ['owns', 'artifacts', 'Plan', 'Design', 'Decisions', 'duplicate'], true)) {
            self::assertTrue($after->isApproved());
        }
        $bytes = $this->tree();
        try {
            $this->store->mutate('T-001', static function (Item $item): void {
                $item->status = Status::Done;
                $item->verifiedAt = self::AT; // Re-supplying the old timestamp cannot restore proof.
            });
            self::fail('Stale proof allowed completion.');
        } catch (WorkflowException) {
            self::assertSame($bytes, $this->tree());
        }
    }

    #[DataProvider('targetWrites')]
    public function testDoneTargetEditsPreserveEveryByteAndCreateNoSuccessor(string $route, string $field): void
    {
        $this->record();
        self::assertTrue($this->store->close('T-001', Status::Done, '', self::AT));
        $before = $this->tree();
        try {
            if ($route === 'save') {
                $candidate = $this->store->load('T-001');
                $this->change($candidate, $field);
                $this->store->save($candidate);
            } else {
                $this->store->mutate('T-001', fn (Item $item) => $this->change($item, $field));
            }
            self::fail('Done history was changed.');
        } catch (WorkflowException $exception) {
            self::assertStringContainsString('create a new follow-up item', $exception->getMessage());
        }
        self::assertSame($before, $this->tree());
        self::assertSame(['E-001', 'E-002', 'T-001', 'T-002'], $this->store->ids());
    }

    public function testExplicitReceiptsInvalidateOrEstablishResultingTargetAtSameSecond(): void
    {
        foreach (['worker', 'explorer', 'orchestrator', 'verifier'] as $role) {
            foreach (['done', 'blocked', 'failed', 'needs-decision'] as $status) {
                if ($role === 'verifier' && $status === 'done') {
                    continue;
                }
                $this->record();
                self::assertSame(self::AT, $this->store->load('T-001')->verifiedAt);
                $this->record($role, $status);
                self::assertNull($this->store->load('T-001')->verifiedAt, $role.' '.$status);
            }
        }
        $this->record(artifacts: ['verification.md']);
        $verified = $this->store->load('T-001');
        self::assertSame(self::AT, $verified->verifiedAt);
        self::assertContains('verification.md', $verified->artifacts);
        self::assertTrue($verified->isApproved());
        $this->store->mutate('T-001', static function (Item $item): void {
            $item->phase = Phase::Verify;
            $item->status = Status::InProgress;
        });
        self::assertTrue($this->store->close('T-001', Status::Done, 'Checked', self::AT));
        self::assertTrue(ItemLifecycle::hasCurrentVerification($this->store->load('T-001')));
    }

    public function testDoneRejectsNewReceiptsButAllowsSuccessfulReverificationOfSameTarget(): void
    {
        $this->record();
        $this->store->close('T-001', Status::Done, '', self::AT);
        foreach ([['worker', 'done'], ['explorer', 'done'], ['verifier', 'failed'], ['verifier', 'blocked'], ['verifier', 'needs-decision']] as [$role, $status]) {
            $before = $this->tree();
            try {
                $this->record($role, $status);
                self::fail('Receipt rewrote completed work.');
            } catch (WorkflowException $exception) {
                self::assertStringContainsString('create a new follow-up item', $exception->getMessage());
            }
            self::assertSame($before, $this->tree());
        }
        $before = $this->tree();
        try {
            $this->record(artifacts: ['new-target.php']);
            self::fail('Successful verifier changed the historical target.');
        } catch (WorkflowException) {
            self::assertSame($before, $this->tree());
        }
        $this->record();
        self::assertSame(Status::Done, $this->store->load('T-001')->status);
        self::assertSame(self::AT, $this->store->load('T-001')->verifiedAt);
    }

    public function testInvalidMaterialDoneEditsStillExplainFollowUpInsteadOfReopening(): void
    {
        $this->record();
        $this->store->close('T-001', Status::Done, '', self::AT);
        foreach (['kind' => Kind::Epic, 'parent' => 'E-999'] as $field => $value) {
            $candidate = $this->store->load('T-001');
            $candidate->{$field} = $value;
            $before = $this->tree();
            try {
                $this->store->save($candidate);
                self::fail('Material done edit was accepted.');
            } catch (WorkflowException $exception) {
                self::assertStringContainsString('create a new follow-up item', $exception->getMessage());
            }
            self::assertSame($before, $this->tree());
        }
    }

    public function testDesignApprovalWritesKeepProofAndNeverStaleScopeApproval(): void
    {
        $this->record();
        $this->store->mutate('T-001', static function (Item $item): void {
            $item->designApproval = Approval::forDesign(self::AT, 'user', $item->designHash());
        });

        $after = $this->store->load('T-001');
        self::assertSame(self::AT, $after->verifiedAt, 'Approval-metadata writes keep current proof.');
        self::assertTrue($after->isDesignApproved());
        self::assertTrue($after->isApproved());

        // A Design change clears proof, stales the design approval and leaves
        // the scope approval alone: three independent freshness contracts.
        $this->store->mutate('T-001', static fn (Item $item) => $item->body = str_replace(
            'Original Design',
            'Changed Design',
            $item->body,
        ));
        $changed = $this->store->load('T-001');
        self::assertNull($changed->verifiedAt);
        self::assertTrue($changed->hasStaleDesignApproval());
        self::assertTrue($changed->isApproved());
    }

    public function testExplicitVerificationDoesNotAddApprovalPlanDesignOrPhaseGates(): void
    {
        $candidate = $this->make('T-003');
        $candidate->approval = null;
        $candidate->body = '';
        $candidate->phase = Phase::Plan;
        $candidate->status = Status::Draft;
        $this->store->create($candidate);
        $this->store->recordReceipt('T-003', $this->receipt(), [], '- Checked without adding process gates.');
        self::assertTrue($this->store->close('T-003', Status::Done, '', self::AT));
        $item = $this->store->load('T-003');
        self::assertNull($item->approval);
        self::assertSame(Phase::Plan, $item->phase);
        self::assertSame('', $item->section('Plan'));
        self::assertSame('', $item->section('Design'));
    }

    public function testForcedClosureRecordsExceptionAndDoesNotFabricateVerification(): void
    {
        $this->record('worker', 'done');
        self::assertFalse($this->store->close('T-001', Status::Done, 'Deliberate exception', self::AT, true));
        $item = $this->store->load('T-001');
        self::assertNull($item->verifiedAt);
        self::assertSame('worker', $item->lastResult['role']);
        self::assertStringContainsString('Verification exception: explicit --force', $item->section('Progress'));
        self::assertStringContainsString('Deliberate exception', $item->section('Progress'));
        $before = $this->tree();
        try {
            $this->store->close('T-001', Status::Cancelled, '', self::AT, true);
            self::fail('Force changed done history.');
        } catch (WorkflowException $exception) {
            self::assertStringContainsString('create a new follow-up item', $exception->getMessage());
        }
        self::assertSame($before, $this->tree());
    }

    public function testForceCannotBypassStructure(): void
    {
        $invalid = $this->store->load('T-001');
        $invalid->parent = 'T-002';
        $this->raw($invalid);
        $before = $this->tree();
        try {
            $this->store->close('T-001', Status::Done, '', self::AT, true);
            self::fail('Force bypassed structural safety.');
        } catch (WorkflowException $exception) {
            self::assertStringContainsString('task parent must be a epic', $exception->getMessage());
        }
        self::assertSame($before, $this->tree());
    }

    public function testSuppliedOpenVerificationTimestampsAreNotAccepted(): void
    {
        $new = $this->make('T-003');
        $new->verifiedAt = self::AT;
        $new->lastResult = $this->receipt();
        $this->store->create($new);
        self::assertNull($this->store->load('T-003')->verifiedAt);
        $new->verifiedAt = self::AT;
        $this->store->save($new);
        self::assertNull($this->store->load('T-003')->verifiedAt);
    }

    public function testLegacyDiagnosticsAndNonmaterialDoneCorrectionsStayHonest(): void
    {
        $legacy = $this->store->load('T-001');
        $legacy->verifiedAt = self::AT;
        $legacy->lastResult = $this->receipt('verifier', 'failed');
        $this->raw($legacy);
        $warnings = implode("\n", (new ItemValidator($this->store))->validate()->warnings);
        self::assertStringContainsString('verification is stale or lacks a matching successful verifier receipt', $warnings);
        $before = $this->tree();
        try {
            $this->store->close('T-001', Status::Done, '', self::AT);
            self::fail('Stale legacy timestamp allowed completion.');
        } catch (WorkflowException) {
            self::assertSame($before, $this->tree());
        }
        $legacy->status = Status::Done;
        $legacy->verifiedAt = null;
        $this->raw($legacy);
        $this->store->mutate('T-001', function (Item $item): void {
            $item->priority = 1;
            $this->store->appendProgress($item, '- Historical note only.');
        });
        self::assertNull($this->store->load('T-001')->verifiedAt);
        self::assertStringContainsString('done without a verifier receipt', implode("\n", (new ItemValidator($this->store))->validate()->warnings));
    }

    public function testSamePathExternalChangesRemainConventionBasedAndUnrelatedLegacyRecordsRemainUntouched(): void
    {
        $legacy = $this->make('T-091');
        $legacy->kind = Kind::Epic;
        $this->raw($legacy, 'T-090');
        $legacyBytes = file_get_contents($this->store->itemPath('T-090'));
        $this->record();
        file_put_contents($this->root.'/target.php', 'External replacement at the same path');
        $this->store->mutate('T-001', function (Item $item): void {
            $item->priority = 7;
            $this->store->appendProgress($item, '- No target-field change.');
        });
        self::assertSame(self::AT, $this->store->load('T-001')->verifiedAt, 'No artifact-content scanner is promised.');
        self::assertSame($legacyBytes, file_get_contents($this->store->itemPath('T-090')));
    }
}
