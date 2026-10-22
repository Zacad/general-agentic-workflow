<?php

declare(strict_types=1);

namespace Workflow\Tests\Console;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workflow\Console\Application;
use Workflow\Domain\FrontMatter;
use Workflow\Domain\Item;
use Workflow\Store\ItemStore;
use Workflow\Tests\WorkflowTestCase;

final class BriefPacketTest extends WorkflowTestCase
{
    private const CLAIM = 'PRODUCER_CLAIM_SENTINEL';
    private const PRIVATE_METADATA = 'UNKNOWN_METADATA_SENTINEL';
    private const PROGRESS = 'PROGRESS_SENTINEL';
    private const RELATED_CLAIM = 'RELATED_ITEM_CLAIM_SENTINEL';
    private const DECISION = 'Which option does the user prefer?';

    private array $data;
    private string $body;

    protected function setUp(): void
    {
        parent::setUp();
        $this->put([
            'id' => 'E-001', 'kind' => 'epic', 'title' => 'Parent',
            'last_result' => ['finding' => self::RELATED_CLAIM],
            'unknown_metadata' => self::RELATED_CLAIM,
        ], "## Design\n\nParent architecture.\n\n## Progress\n\n".self::RELATED_CLAIM);
        $this->put([
            'id' => 'T-001', 'kind' => 'task', 'parent' => 'E-001',
            'title' => 'Upstream', 'status' => 'done',
            'artifacts' => ['evidence/upstream.md'],
            'last_result' => ['finding' => self::RELATED_CLAIM],
            'unknown_metadata' => self::RELATED_CLAIM,
        ], "## Progress\n\n".self::RELATED_CLAIM);

        $this->data = [
            'id' => 'T-002',
            'kind' => 'task',
            'parent' => 'E-001',
            'title' => 'Independent deliverable',
            'status' => 'in-progress',
            'phase' => 'implement',
            'priority' => 3,
            'depends_on' => ['T-001'],
            'owns' => ['src/deliverable.php'],
            'artifacts' => ['evidence/target.md'],
            'outcome' => 'Observable outcome.',
            'scope' => [
                'in' => ['Included work.'], 'out' => ['Excluded work.'],
                'unknown_metadata' => self::PRIVATE_METADATA,
            ],
            'acceptance' => ['First criterion.', 'Second criterion.'],
            'last_result' => [
                'at' => '2026-10-21T00:00:00+00:00',
                'role' => 'worker', 'status' => 'done', 'finding' => self::CLAIM,
                'decision' => self::DECISION,
                'unknown_metadata' => ['finding' => self::PRIVATE_METADATA],
            ],
            'progress' => self::PROGRESS,
            'body' => self::PROGRESS,
            'unknown_metadata' => ['last_result' => ['finding' => self::PRIVATE_METADATA]],
        ];
        $this->body = "## Plan\n\nBounded approach.\n\n## Design\n\nRequired design.\n\n"
            ."## Progress\n\n".self::PROGRESS."\n\n## Decisions\n\nRecorded decision.";
        $this->data['approval'] = [
            'at' => '2026-10-21T00:00:00+00:00', 'by' => 'reviewer',
            'scope_hash' => Item::fromArray($this->data, $this->body)->scopeHash(),
            'unknown_metadata' => self::PRIVATE_METADATA,
        ];
        $this->put($this->data, $this->body);
    }

    private function put(array $data, string $body): void
    {
        $directory = $this->root.'/.workflow/items/'.$data['id'];
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        file_put_contents($directory.'/item.md', FrontMatter::render($data, $body));
    }

    private function brief(string $role, bool $json): string
    {
        $application = new Application();
        $application->setAutoExit(false);
        $output = new BufferedOutput();
        $exit = $application->run(new ArrayInput([
            '--project' => $this->root, 'command' => 'brief', 'id' => 'T-002',
            '--for' => $role, '--json' => $json,
        ]), $output);
        $text = $output->fetch();
        self::assertSame(0, $exit, $text);

        return $text;
    }

    public static function rolesAndFormats(): iterable
    {
        foreach (['orchestrator', 'explorer', 'worker', 'verifier'] as $role) {
            yield $role.' text' => [$role, false];
            yield $role.' JSON' => [$role, true];
        }
    }

    #[DataProvider('rolesAndFormats')]
    public function testRolePacketsRetainInputsWithoutLeakingMetadataOrChangingRecords(string $role, bool $json): void
    {
        $store = new ItemStore($this->root);
        $before = [];
        foreach ($store->ids() as $id) {
            $before[$id] = file_get_contents($store->itemPath($id));
        }

        $output = $this->brief($role, $json);
        foreach ([self::PRIVATE_METADATA, self::PROGRESS, self::RELATED_CLAIM] as $sentinel) {
            self::assertStringNotContainsString($sentinel, $output);
        }

        if ($json) {
            $packet = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
            $expected = [
                'item' => [
                    'id' => 'T-002', 'kind' => 'task', 'parent' => 'E-001',
                    'title' => 'Independent deliverable', 'status' => 'in-progress',
                    'phase' => 'implement', 'priority' => 3,
                    'depends_on' => ['T-001'], 'owns' => ['src/deliverable.php'],
                    'artifacts' => ['evidence/target.md'], 'outcome' => 'Observable outcome.',
                    'scope' => ['in' => ['Included work.'], 'out' => ['Excluded work.']],
                    'acceptance' => ['First criterion.', 'Second criterion.'],
                ],
                'role' => $role,
                'approval' => 'current (reviewer, 2026-10-21T00:00:00+00:00)',
                'dependencies' => ['T-001 (done)'],
                'upstream_artifacts' => ['T-001' => ['evidence/upstream.md']],
                'plan' => 'Bounded approach.', 'design' => 'Required design.',
                'parent_design' => 'Parent architecture.',
                'decisions' => 'Recorded decision.',
                'deliverable_paths' => ['src/deliverable.php'],
                'parent_design_present' => true,
            ];
            if ($role === 'explorer') {
                $expected['output_target'] = '.workflow/items/T-002/outputs/';
            }
            if ($role !== 'verifier') {
                $expected['item']['last_result'] = [
                    'role' => 'worker', 'status' => 'done', 'finding' => self::CLAIM,
                    'decision' => self::DECISION,
                ];
            }
            self::assertSame($expected, $packet);
        } else {
            foreach ([
                'T-002 · Independent deliverable', 'kind: task', 'status: in-progress',
                'phase: implement', 'priority: 3', 'role: '.$role, 'parent: E-001',
                'approval: current (reviewer, 2026-10-21T00:00:00+00:00)',
                'depends: T-001 (done)', 'owns: src/deliverable.php',
                'outcome: Observable outcome.', 'scope in: Included work.',
                'scope out: Excluded work.', '1. First criterion.', '2. Second criterion.',
                "plan:\n  Bounded approach.", "design:\n  Required design.",
                "parent design:\n  Parent architecture.", "decisions:\n  Recorded decision.",
                "artifacts:\n  - evidence/target.md", 'T-001: evidence/upstream.md',
            ] as $retained) {
                self::assertStringContainsString($retained, $output);
            }
            if ($role === 'explorer') {
                self::assertStringContainsString('write: .workflow/items/T-002/outputs/', $output);
            } else {
                self::assertStringNotContainsString('write:', $output);
            }
        }

        if ($role === 'verifier') {
            self::assertStringNotContainsString(self::CLAIM, $output);
            self::assertStringNotContainsString('last_result', $output);
            self::assertStringNotContainsString('last result:', $output);
            if (!$json) {
                self::assertStringContainsString('producer claim: omitted', $output);
            }
        } else {
            self::assertStringContainsString(self::CLAIM, $output);
        }

        foreach ($before as $id => $bytes) {
            self::assertSame($bytes, file_get_contents($store->itemPath($id)));
        }
        self::assertSame($this->data['unknown_metadata'], $store->load('T-002')->extra['unknown_metadata']);
    }

    public static function nonVerifierRoles(): iterable
    {
        foreach (['orchestrator', 'explorer', 'worker'] as $role) {
            yield $role => [$role];
        }
    }

    #[DataProvider('nonVerifierRoles')]
    public function testReceiptSummaryIsBoundedAndSharedByBothFormats(string $role): void
    {
        $this->data['last_result']['finding'] = "  pociągi\n".str_repeat('ź', 140).'UNBOUNDED_TAIL';
        $this->put($this->data, $this->body);
        $packet = json_decode($this->brief($role, true), true, flags: JSON_THROW_ON_ERROR);
        $finding = 'pociągi '.str_repeat('ź', 91).'…';
        self::assertSame([
            'role' => 'worker', 'status' => 'done', 'finding' => $finding,
            'decision' => self::DECISION,
        ], $packet['item']['last_result']);
        self::assertSame(100, mb_strlen($packet['item']['last_result']['finding']));
        $text = $this->brief($role, false);
        self::assertStringContainsString('last result: worker · done — '.$finding.' · decision: '.self::DECISION, $text);
        self::assertStringNotContainsString('UNBOUNDED_TAIL', $text);
        self::assertStringNotContainsString(self::PRIVATE_METADATA, $text);
    }

    #[DataProvider('nonVerifierRoles')]
    public function testReceiptSubpacketCannotSerializeArbitraryNestedValues(string $role): void
    {
        $this->data['last_result'] = [
            'role' => ['unknown_metadata' => self::PRIVATE_METADATA],
            'status' => ['unknown_metadata' => self::PRIVATE_METADATA],
            'finding' => ['unknown_metadata' => self::PRIVATE_METADATA],
            'decision' => ['unknown_metadata' => self::PRIVATE_METADATA],
            'item' => $this->data,
        ];
        $this->put($this->data, $this->body);
        $json = $this->brief($role, true);
        $packet = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(['role' => '?', 'status' => '?', 'finding' => ''], $packet['item']['last_result']);
        self::assertStringNotContainsString(self::PRIVATE_METADATA, $json);
        $text = $this->brief($role, false);
        self::assertStringContainsString('last result: ? · ? — ', $text);
        self::assertStringNotContainsString(self::PRIVATE_METADATA, $text);
    }

    #[DataProvider('rolesAndFormats')]
    public function testAbsentReceiptIsNotInvented(string $role, bool $json): void
    {
        unset($this->data['last_result']);
        $this->put($this->data, $this->body);
        $output = $this->brief($role, $json);
        self::assertStringNotContainsString('last_result', $output);
        self::assertStringNotContainsString('last result:', $output);
    }

    public function testVerifierDoesNotCensorApprovedNarrative(): void
    {
        $this->put($this->data, "## Plan\n\n".self::CLAIM."\n\n## Design\n\n".self::CLAIM
            ."\n\n## Progress\n\n".self::PROGRESS."\n\n## Decisions\n\n".self::CLAIM);
        $packet = json_decode($this->brief('verifier', true), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(self::CLAIM, $packet['plan']);
        self::assertSame(self::CLAIM, $packet['design']);
        self::assertSame(self::CLAIM, $packet['decisions']);
        self::assertArrayNotHasKey('last_result', $packet['item']);
        self::assertStringContainsString(self::CLAIM, $this->brief('verifier', false));
    }
}
