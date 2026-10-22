<?php

declare(strict_types=1);

namespace Workflow\Tests\Validate;

use Workflow\Domain\Approval;
use Workflow\Domain\FrontMatter;
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

    /** Deliberately bypass supported writes to exercise legacy diagnostics. */
    private function writeRaw(Item $item): void
    {
        $directory = dirname($this->store->itemPath($item->id));
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        file_put_contents($directory.'/item.md', FrontMatter::render($item->toArray(), $item->body));
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
        $this->writeRaw($first);
        $this->writeRaw($second);

        $result = (new ItemValidator($this->store))->validate();

        self::assertNotEmpty($result->errors);
        self::assertStringContainsString('Dependency cycle', implode("\n", $result->errors));
    }

    public function testBadParentKindIsDetected(): void
    {
        $this->store->create($this->make('E-001', Kind::Epic));
        $this->writeRaw($this->make('S-001', Kind::Subtask, 'E-001'));

        $result = (new ItemValidator($this->store))->validate();

        self::assertStringContainsString('subtask parent must be a task', implode("\n", $result->errors));
    }

    public function testFullDiagnosticsStillReportRawLegacyStructure(): void
    {
        $this->writeRaw($this->make('T-001', Kind::Epic, 'E-999'));
        $this->writeRaw($this->make('S-001', Kind::Subtask));
        $missing = $this->make('T-002', Kind::Task, 'E-999');
        $missing->dependsOn = ['T-999', 'T-002'];
        $this->writeRaw($missing);
        $self = $this->make('T-003', Kind::Task, 'T-003');
        $this->writeRaw($self);

        $result = (new ItemValidator($this->store))->validate();
        $errors = implode("\n", $result->errors);
        foreach (['id does not match kind epic', 'an epic cannot have a parent', 'a subtask requires a task parent',
            'parent E-999 does not exist', 'dependency T-999 does not exist', 'depends on itself', 'own parent'] as $message) {
            self::assertStringContainsString($message, $errors);
        }
    }

    public function testStandaloneTaskReasonIsStillOnlyGuidance(): void
    {
        $this->store->create($this->make('T-001', Kind::Task));
        $result = (new ItemValidator($this->store))->validate();

        self::assertSame([], $result->errors);
        self::assertStringContainsString('standalone task without standalone_reason', implode("\n", $result->warnings));
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

    public function testImplementationWithoutDesignIsReported(): void
    {
        $item = $this->make('E-001', Kind::Epic);
        $item->phase = Phase::Implement;
        $item->body = "## Plan\n\n1. Do the work.\n\n## Progress\n";
        $this->store->create($item);

        $result = (new ItemValidator($this->store))->validate();
        self::assertStringContainsString('implement without a design', implode("\n", $result->warnings));

        $item->body = "## Plan\n\n1. Do the work.\n\n## Design\n\n1. Design it.\n\n## Progress\n";
        $this->store->save($item);

        $result = (new ItemValidator($this->store))->validate();
        self::assertStringNotContainsString('without a design', implode("\n", $result->warnings));
    }

    public function testOwnershipOverlapIsIgnoredAcrossDependencyChain(): void
    {
        $first = $this->make('T-001', Kind::Task);
        $first->owns = ['src/'];
        $second = $this->make('T-002', Kind::Task);
        $second->owns = ['src/Store/'];
        $second->dependsOn = ['T-001'];
        $this->store->create($first);
        $this->store->create($second);

        $result = (new ItemValidator($this->store))->validate();

        self::assertStringNotContainsString('parallel overlap', implode("\n", $result->warnings));
    }

    public function testItemLocalArtifactPathUnderWorkflowIsAccepted(): void
    {
        $item = $this->make('T-001', Kind::Task);
        $item->artifacts = ['items/T-001/outputs/x.md'];
        $this->store->create($item);

        $outputDirectory = $this->store->itemsDir().'/T-001/outputs';
        mkdir($outputDirectory, 0777, true);
        file_put_contents($outputDirectory.'/x.md', 'x');

        $result = (new ItemValidator($this->store))->validate();

        self::assertStringNotContainsString('artifact not found', implode("\n", $result->warnings));
    }

    public function testTemplateDocumentsWarnOnlyWhenAnEpicExists(): void
    {
        $workflow = $this->store->workflowDir();
        // Marker-only copy (new scaffold) and a legacy copy without the marker.
        file_put_contents($workflow.'/PRD.md', "<!-- workflow:unfilled-template -->\n# PRD\n\n## Goals\n\n1. ...\n");
        file_put_contents($workflow.'/Architecture.md', "# Architecture\n\nReplace the placeholders.\n");

        $this->store->create($this->make('T-001', Kind::Task));
        $warnings = implode("\n", (new ItemValidator($this->store))->validate()->warnings);
        self::assertStringNotContainsString('template placeholders', $warnings);

        $this->store->create($this->make('E-001', Kind::Epic));
        $warnings = implode("\n", (new ItemValidator($this->store))->validate()->warnings);
        self::assertStringContainsString(
            '.workflow/PRD.md still contains template placeholders; record product truth there'
            ." (fill moment: before the first epic's scope approval; epics: E-001).",
            $warnings,
        );
        self::assertStringContainsString(
            '.workflow/Architecture.md still contains template placeholders; record product truth there'
            .' (fill moment: at epic design; epics: E-001).',
            $warnings,
        );

        // Filled documents are silent, including with epics present.
        file_put_contents($workflow.'/PRD.md', "# PRD\n\nFilled truth.\n\n## Goals\n\n1. Ship.\n");
        file_put_contents($workflow.'/Architecture.md', "# Architecture\n\nFilled shape.\n");
        $warnings = implode("\n", (new ItemValidator($this->store))->validate()->warnings);
        self::assertStringNotContainsString('template placeholders', $warnings);
    }

    public function testCancelledEpicsDoNotTriggerTemplateDocumentWarnings(): void
    {
        $cancelled = $this->make('E-001', Kind::Epic);
        $cancelled->status = Status::Cancelled;
        $this->store->create($cancelled);

        $warnings = implode("\n", (new ItemValidator($this->store))->validate()->warnings);

        self::assertStringNotContainsString('template placeholders', $warnings);
    }

    public function testExplorerReceiptPredatingApprovalIsWarned(): void
    {
        $item = $this->make('E-001', Kind::Epic);
        $item->approval = new Approval('2026-01-01T00:00:00+00:00', 'test', $item->scopeHash());
        $item->body = "## Plan\n\n## Progress\n\n"
            ."- 2025-12-31T23:59:59+00:00 · explorer · done\n  Finding: Early evidence.\n\n"
            ."- not-an-iso-date · explorer · done\n  Finding: Unparsed timestamps never compare.\n";
        $this->store->create($item);

        $warnings = implode("\n", (new ItemValidator($this->store))->validate()->warnings);

        self::assertStringContainsString(
            'E-001: explorer receipt predates approval (2025-12-31T23:59:59+00:00); discovery ran before the epic was approved.',
            $warnings,
        );
        self::assertSame(1, substr_count($warnings, 'explorer receipt predates approval'));
    }

    public function testExplorerReceiptAfterApprovalIsSilent(): void
    {
        $item = $this->make('E-001', Kind::Epic);
        $item->body = "## Plan\n\n## Progress\n\n- 2026-01-01T00:00:01+00:00 · explorer · done\n  Finding: Later evidence.\n";
        $this->store->create($item);

        $warnings = implode("\n", (new ItemValidator($this->store))->validate()->warnings);

        self::assertStringNotContainsString('explorer receipt predates approval', $warnings);
    }

    public function testOpenChildWithoutParentDesignIsWarned(): void
    {
        $this->store->create($this->make('E-001', Kind::Epic));
        $this->store->create($this->make('T-001', Kind::Task, 'E-001'));

        $warnings = implode("\n", (new ItemValidator($this->store))->validate()->warnings);
        self::assertStringContainsString('T-001: parent E-001 has no recorded Design (canonical scope map).', $warnings);

        $epic = $this->store->load('E-001');
        $epic->body = "## Plan\n\n## Design\n\nScope map: T-001.\n\n## Progress\n";
        $this->store->save($epic);

        $warnings = implode("\n", (new ItemValidator($this->store))->validate()->warnings);
        self::assertStringNotContainsString('no recorded Design', $warnings);
    }

    public function testWorkerArtifactsOutsideOwnsAreWarnedAndOtherRolesAreNot(): void
    {
        mkdir($this->root.'/src', 0777, true);
        file_put_contents($this->root.'/src/deliverable.md', 'owned work');
        file_put_contents($this->root.'/stray.md', 'unowned work');
        $outputDirectory = $this->store->itemsDir().'/T-001/outputs';
        mkdir($outputDirectory, 0777, true);
        file_put_contents($outputDirectory.'/evidence.md', 'evidence');

        $item = $this->make('T-001', Kind::Task);
        $item->status = Status::InProgress;
        $item->owns = ['src/'];
        $item->artifacts = [
            'src/deliverable.md',
            'stray.md',
            '.workflow/items/T-001/outputs/evidence.md',
            'https://example.test/report',
        ];
        $item->lastResult = [
            'at' => '2026-01-01T00:00:00+00:00', 'role' => 'worker', 'status' => 'done', 'finding' => 'Worked.',
        ];
        $this->store->create($item);

        $warnings = implode("\n", (new ItemValidator($this->store))->validate()->warnings);
        self::assertStringContainsString(
            'T-001: artifact stray.md is outside owns (was it written? add to owns via set, or correct the artifact).',
            $warnings,
        );
        self::assertStringNotContainsString('src/deliverable.md is outside owns', $warnings);
        self::assertStringNotContainsString('evidence.md is outside owns', $warnings);

        // A non-producer artifact reference is upstream evidence, not a write.
        $item->lastResult['role'] = 'explorer';
        $this->store->save($item);

        $warnings = implode("\n", (new ItemValidator($this->store))->validate()->warnings);
        self::assertStringNotContainsString('outside owns', $warnings);
    }

    public function testOpenApprovalWithoutConfirmationQuoteIsWarned(): void
    {
        $item = $this->make('T-001', Kind::Task);
        $this->store->create($item);

        $warnings = implode("\n", (new ItemValidator($this->store))->validate()->warnings);
        self::assertStringContainsString(
            'T-001: approval has no recorded confirmation quote — approval must be explicit user confirmation.',
            $warnings,
        );

        $item->approval = new Approval($item->approval->at, 'test', $item->scopeHash(), 'Yes, approve this scope.');
        $this->store->save($item);

        $warnings = implode("\n", (new ItemValidator($this->store))->validate()->warnings);
        self::assertStringNotContainsString('no recorded confirmation quote', $warnings);
    }
}
