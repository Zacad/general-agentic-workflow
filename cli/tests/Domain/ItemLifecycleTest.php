<?php

declare(strict_types=1);

namespace Workflow\Tests\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Workflow\Domain\Approval;
use Workflow\Domain\Item;
use Workflow\Domain\ItemLifecycle;
use Workflow\Domain\Kind;
use Workflow\Domain\Phase;
use Workflow\Domain\Status;
use Workflow\Domain\WorkflowException;

final class ItemLifecycleTest extends TestCase
{
    private const AT = '2026-10-21T00:00:00+00:00';

    private function make(bool $verified = true): Item
    {
        $item = Item::fromArray([
            'id' => 'T-001', 'kind' => 'task', 'title' => 'Original', 'status' => 'todo',
            'owns' => ['target.php'], 'artifacts' => ['target.php'], 'acceptance' => ['Original criterion'],
        ], "## Plan\n\nOriginal Plan\n\n## Design\n\nOriginal Design\n\n## Decisions\n\nOriginal Decisions\n\n## Progress\n");
        $item->approval = new Approval(self::AT, 'user', $item->scopeHash());
        if ($verified) {
            $item->lastResult = $this->receipt('verifier', 'done');
            $item->verifiedAt = self::AT;
        }

        return $item;
    }

    private function receipt(string $role, string $status): array
    {
        return ['at' => self::AT, 'role' => $role, 'status' => $status, 'finding' => 'Checked.'];
    }

    public static function materialChanges(): iterable
    {
        yield 'id' => ['id', 'T-002'];
        yield 'kind' => ['kind', Kind::Epic];
        yield 'parent' => ['parent', 'E-001'];
        yield 'title' => ['title', 'Changed'];
        yield 'outcome' => ['outcome', 'Changed outcome'];
        yield 'scope in' => ['scopeIn', ['Changed scope']];
        yield 'scope out' => ['scopeOut', ['Changed exclusions']];
        yield 'acceptance' => ['acceptance', ['Changed criterion']];
        yield 'dependencies' => ['dependsOn', ['T-002']];
        yield 'ownership' => ['owns', ['changed.php']];
        yield 'artifact references' => ['artifacts', ['changed.php']];
        foreach (['Plan', 'Design', 'Decisions'] as $section) {
            yield $section => [$section, null];
        }
        yield 'duplicate Design after Progress' => ['duplicate', null];
    }

    private function change(Item $item, string $field, mixed $value): void
    {
        if (in_array($field, ['Plan', 'Design', 'Decisions'], true)) {
            $item->body = str_replace('Original '.$field, 'Changed '.$field, $item->body);
        } elseif ($field === 'duplicate') {
            $item->body .= "\n## Design\n\nNew material work\n";
        } else {
            $item->{$field} = $value;
        }
    }

    #[DataProvider('materialChanges')]
    public function testOpenTargetChangesClearProofWithoutEditingApproval(string $field, mixed $value): void
    {
        $before = $this->make();
        $after = clone $before;
        $this->change($after, $field, $value);
        ItemLifecycle::apply($before, $after);

        self::assertNull($after->verifiedAt);
        self::assertSame(self::AT, $before->verifiedAt);
        self::assertSame($before->approval, $after->approval);
        if (in_array($field, ['owns', 'artifacts', 'Plan', 'Design', 'Decisions', 'duplicate'], true)) {
            self::assertTrue($after->isApproved());
        }
    }

    #[DataProvider('materialChanges')]
    public function testDoneTargetChangesRequireFollowUpEvenWithForce(string $field, mixed $value): void
    {
        $before = $this->make();
        $before->status = Status::Done;
        $after = clone $before;
        $this->change($after, $field, $value);

        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage('create a new follow-up item');
        ItemLifecycle::apply($before, $after, forceClose: true);
    }

    public function testRoutineFieldsAndProgressRetainProof(): void
    {
        foreach ([Status::Todo, Status::Done] as $status) {
            $before = $this->make();
            $before->status = $status;
            $after = clone $before;
            $after->phase = Phase::Verify;
            $after->priority = 9;
            $after->body .= "\n- Non-material handoff note.\n";
            $after->extra['note'] = 'No new workflow gate';
            $after->approval = new Approval(self::AT, 'corrected reviewer', $after->scopeHash());

            self::assertFalse(ItemLifecycle::apply($before, $after));
            self::assertSame(self::AT, $after->verifiedAt);
            self::assertSame($status, $after->status, 'Phase never infers status.');
        }
    }

    public function testNormalStatusTransitionsAndClosureProgressRetainCurrentProof(): void
    {
        foreach ([Status::Draft, Status::Todo, Status::InProgress, Status::Cancelled, Status::Done] as $status) {
            $before = $this->make();
            $after = clone $before;
            $after->status = $status;
            $after->body .= "\n- Closed as ".$status->value."\n";
            self::assertFalse(ItemLifecycle::apply($before, $after));
            self::assertTrue(ItemLifecycle::hasCurrentVerification($after));
        }
    }

    public static function unsuccessfulReceipts(): iterable
    {
        foreach (['worker', 'explorer', 'orchestrator', 'verifier'] as $role) {
            foreach (['done', 'blocked', 'failed', 'needs-decision'] as $status) {
                if ($role !== 'verifier' || $status !== 'done') {
                    yield $role.' '.$status => [$role, $status];
                }
            }
        }
    }

    #[DataProvider('unsuccessfulReceipts')]
    public function testNewReceiptsClearProofEvenAtTheSameTimestamp(string $role, string $status): void
    {
        $before = $this->make();
        $after = clone $before;
        $receipt = $this->receipt($role, $status);
        $after->lastResult = $receipt;
        ItemLifecycle::apply($before, $after, $receipt);
        self::assertNull($after->verifiedAt);
        self::assertSame(self::AT, $before->verifiedAt);
    }

    #[DataProvider('unsuccessfulReceipts')]
    public function testDoneRejectsProducerAndUnsuccessfulVerifierReceipts(string $role, string $status): void
    {
        $before = $this->make();
        $before->status = Status::Done;
        $after = clone $before;
        $receipt = $this->receipt($role, $status);
        $after->lastResult = $receipt;
        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage('create a new follow-up item');
        ItemLifecycle::apply($before, $after, $receipt);
    }

    public function testSuccessfulReceiptEstablishesProofForResultingArtifactReferences(): void
    {
        $before = $this->make();
        $after = clone $before;
        $after->artifacts[] = 'verification.md';
        $receipt = $this->receipt('verifier', 'done');
        $after->lastResult = $receipt; // Exactly the same values and second as before.
        ItemLifecycle::apply($before, $after, $receipt);

        self::assertTrue(ItemLifecycle::hasCurrentVerification($after));
        self::assertSame(self::AT, $after->verifiedAt);
        self::assertTrue($after->isApproved());
    }

    public function testChangedReceiptMetadataCannotAuthenticateItself(): void
    {
        $before = $this->make(false);
        $after = clone $before;
        $after->lastResult = $this->receipt('verifier', 'done');
        $after->verifiedAt = self::AT;
        ItemLifecycle::apply($before, $after);
        self::assertNull($after->verifiedAt);

        $new = $this->make();
        ItemLifecycle::apply(null, $new);
        self::assertNull($new->verifiedAt, 'A supplied creation timestamp is not receipt intent.');
    }

    public function testSuccessWithNoPriorTimestampNeedsExplicitIntent(): void
    {
        $before = $this->make(false);
        $after = clone $before;
        $after->lastResult = $this->receipt('verifier', 'done');
        ItemLifecycle::apply($before, $after, $after->lastResult);
        self::assertSame(self::AT, $after->verifiedAt);
    }

    public function testForceIsAnUnverifiedExceptionNotVerification(): void
    {
        $before = $this->make(false);
        $after = clone $before;
        $after->status = Status::Done;
        self::assertTrue(ItemLifecycle::apply($before, $after, forceClose: true));
        self::assertNull($after->verifiedAt);
        self::assertNull($after->lastResult);
    }

    public function testScopeEditAndCompletionInOneMutationCannotUseOldProof(): void
    {
        $before = $this->make();
        $after = clone $before;
        $after->title = 'Changed';
        $after->status = Status::Done;
        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage('no verifier receipt for the current target');
        ItemLifecycle::apply($before, $after);
    }

    public function testTimestampWithoutMatchingSuccessIsNotCurrentProof(): void
    {
        $item = $this->make();
        foreach ([null, $this->receipt('worker', 'done'), $this->receipt('verifier', 'failed'),
            ['at' => 'different', 'role' => 'verifier', 'status' => 'done']] as $receipt) {
            $item->lastResult = $receipt;
            self::assertFalse(ItemLifecycle::hasCurrentVerification($item));
        }
    }

    public function testLegacyDoneAllowsNonmaterialNotesWithoutManufacturingProof(): void
    {
        $before = $this->make(false);
        $before->status = Status::Done;
        $after = clone $before;
        $after->priority = 1;
        $after->body .= "\n- Historical correction.\n";
        self::assertFalse(ItemLifecycle::apply($before, $after));
        self::assertNull($after->verifiedAt);
        self::assertSame(Status::Done, $after->status);
    }

    public function testDoneCannotBeReopenedOrHaveItsProofRewritten(): void
    {
        $before = $this->make();
        $before->status = Status::Done;
        foreach (['status' => Status::InProgress, 'verifiedAt' => null] as $field => $value) {
            $after = clone $before;
            $after->{$field} = $value;
            try {
                ItemLifecycle::apply($before, $after, forceClose: true);
                self::fail('Historical '.$field.' was rewritten.');
            } catch (WorkflowException $exception) {
                self::assertStringContainsString('create a new follow-up item', $exception->getMessage());
            }
        }
    }
}
