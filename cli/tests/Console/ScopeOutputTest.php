<?php

declare(strict_types=1);

namespace Workflow\Tests\Console;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Console\Application;
use Workflow\Domain\FrontMatter;
use Workflow\Domain\Item;
use Workflow\Domain\Status;
use Workflow\Store\ItemStore;
use Workflow\Support\ScopeOutput;
use Workflow\Tests\WorkflowTestCase;

final class ScopeOutputTest extends WorkflowTestCase
{
    private const LITERAL = '<info>pociągi 🚆</info> <error>źródła</error> '
        .'<fg=green;options=bold>Żółć</> <href=https://example.test/a/b>link</>';

    private function put(array $data, string $body = ''): void
    {
        $directory = $this->root.'/.workflow/items/'.$data['id'];
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        file_put_contents($directory.'/item.md', FrontMatter::render($data, $body));
    }

    private function runCli(array $input, bool $decorated = false): string
    {
        $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, $decorated);
        $application = new Application();
        $application->setAutoExit(false);
        $exit = $application->run(new ArrayInput(['--project' => $this->root] + $input), $output);
        $text = $output->fetch();
        self::assertSame(0, $exit, $text);
        self::assertStringNotContainsString("\033[", $text);

        return $text;
    }

    public static function literals(): iterable
    {
        yield 'null' => [null, 'none (null)'];
        yield 'null marker as string' => ['none (null)', '"none (null)"'];
        yield 'null as string' => ['null', '"null"'];
        yield 'none as string' => ['none', 'none'];
        yield 'simple string' => ['Task', 'Task'];
        yield 'empty string' => ['', '""'];
        yield 'empty list' => [[], '[]'];
        yield 'empty string in list' => [[''], '[""]'];
        yield 'separate values' => [['a', 'b'], '["a","b"]'];
        yield 'comma value' => [['a, b'], '["a, b"]'];
        yield 'multiline' => ["first\nsecond\n", '"first\\nsecond\\n"'];
        yield 'whitespace' => [" \tTask ", '" \\tTask "'];
        yield 'quotes and slashes' => ['"quote" \\path', '"\\"quote\\" \\\\path"'];
        yield 'literal tags and Unicode' => [self::LITERAL, '"'.self::LITERAL.'"'];
    }

    #[DataProvider('literals')]
    public function testLiteralValuesStayDistinct(string|array|null $value, string $expected): void
    {
        self::assertSame($expected, ScopeOutput::literal($value));
    }

    public function testProjectionDoesNotNormalizeListsOrAddOwnedPathsToScope(): void
    {
        $item = Item::fromArray(['id' => 'T-001', 'kind' => 'task', 'title' => ''], '');
        // These direct values also check the output seam independently of the
        // existing persistence rule that trims/drops empty typed-list entries.
        $item->scopeIn = ['z', 'a, b', 'a', 'b', '', 'z', self::LITERAL];
        $item->scopeOut = [];
        $item->acceptance = ['', "first\nsecond", self::LITERAL];
        $item->dependsOn = ['T-020', 'T-010', 'T-020'];
        $item->owns = ['src/', 'elsewhere/'];
        $expected = [
            'kind' => 'task', 'parent' => null, 'title' => '', 'outcome' => '',
            'scope' => ['in' => $item->scopeIn, 'out' => []],
            'acceptance' => $item->acceptance, 'depends_on' => $item->dependsOn,
        ];
        self::assertSame($expected, ScopeOutput::scope($item));
        $hash = $item->scopeHash();
        $item->owns = ['changed/'];
        self::assertSame($expected, ScopeOutput::scope($item));
        self::assertSame($hash, $item->scopeHash());

        $item->scopeIn = array_reverse(array_unique($item->scopeIn));
        $item->dependsOn = ['T-010', 'T-020'];
        self::assertNotSame($expected, ScopeOutput::scope($item));
        self::assertSame($hash, $item->scopeHash());
        $item->acceptance = array_reverse($item->acceptance);
        self::assertNotSame($hash, $item->scopeHash());
        $item->parent = '';
        self::assertSame('', ScopeOutput::scope($item)['parent']);
    }

    public static function decorationModes(): iterable
    {
        yield 'undecorated' => [false];
        yield 'decorated' => [true];
    }

    private function seedTree(): void
    {
        $epic = [
            'id' => 'E-001', 'kind' => 'epic', 'title' => self::LITERAL."\nEpic",
            'outcome' => '', 'scope' => ['in' => ['a, b'], 'out' => []],
            'acceptance' => [self::LITERAL],
        ];
        $epic['approval'] = [
            'at' => '2026-10-21T00:00:00+00:00', 'by' => self::LITERAL,
            'scope_hash' => Item::fromArray($epic, '')->scopeHash(),
        ];
        $this->put($epic, "## Plan\n\n".self::LITERAL."\n\n## Design\n\nEpic design.");
        $this->put(['id' => 'E-002', 'kind' => 'epic', 'title' => 'Outside']);
        foreach (['T-010', 'T-020'] as $id) {
            $this->put(['id' => $id, 'kind' => 'task', 'parent' => 'E-002', 'status' => 'done']);
        }
        $this->put([
            'id' => 'T-002', 'kind' => 'task', 'parent' => 'E-001',
            'title' => self::LITERAL.' '.str_repeat('ź', 300).' END',
            'outcome' => "first\n".self::LITERAL."\nlast\n",
            'scope' => ['in' => ['z', 'a, b', 'a', 'b', 'z', self::LITERAL], 'out' => ['none', '[]']],
            'acceptance' => ["first\nsecond", self::LITERAL],
            'depends_on' => ['T-020', 'T-010'], 'owns' => ['src/a, b.php', self::LITERAL],
        ], "## Plan\n\n".self::LITERAL."\n  indented\n\nlast\n\n## Design\n");
        $this->put([
            'id' => 'S-001', 'kind' => 'subtask', 'parent' => 'T-002',
            'title' => 'Subtask', 'status' => 'in-progress', 'phase' => 'implement',
            'owns' => ['src/subtask.php'],
        ], "## Design\n\n".self::LITERAL);
        $this->put([
            'id' => 'T-003', 'kind' => 'task', 'parent' => 'E-001', 'title' => '',
            'status' => 'cancelled', 'phase' => 'verify',
            'approval' => ['at' => '', 'by' => self::LITERAL, 'scope_hash' => 'stale'],
        ]);
    }

    private function expectedScope(Item $item): array
    {
        return [
            'kind' => $item->kind->value, 'parent' => $item->parent,
            'title' => $item->title, 'outcome' => $item->outcome,
            'scope' => ['in' => $item->scopeIn, 'out' => $item->scopeOut],
            'acceptance' => $item->acceptance, 'depends_on' => $item->dependsOn,
        ];
    }

    #[DataProvider('decorationModes')]
    public function testReviewJsonIncludesExactTreeApprovalAndPlanDesignWithoutWrites(bool $decorated): void
    {
        $this->seedTree();
        $approval = json_decode($this->runCli([
            'command' => 'approve', 'id' => 'T-002', '--by' => self::LITERAL, '--json' => true,
        ], $decorated), true, flags: JSON_THROW_ON_ERROR);
        $store = new ItemStore($this->root);
        $before = [];
        foreach ($store->ids() as $id) {
            $before[$id] = file_get_contents($store->itemPath($id));
        }
        $packet = json_decode($this->runCli([
            'command' => 'review', 'id' => 'E-001', '--json' => true,
        ], $decorated), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(['epic', 'descendants', 'counts', 'prd'], array_keys($packet));
        self::assertSame(['T-002', 'S-001', 'T-003'], array_column($packet['descendants'], 'id'));
        self::assertSame(['tasks' => 2, 'subtasks' => 1, 'open' => 2], $packet['counts']);
        foreach ([$packet['epic'], ...$packet['descendants']] as $record) {
            $item = $store->load($record['id']);
            foreach ($this->expectedScope($item) as $key => $value) {
                self::assertSame($value, $record[$key], $record['id'].' '.$key);
            }
            self::assertSame($item->owns, $record['owns']);
            self::assertSame($item->status->value, $record['status']);
            self::assertSame($item->phase->value, $record['phase']);
            self::assertSame($item->scopeHash(), $record['scope_hash']);
            self::assertSame($item->approval?->toArray(), $record['approval']);
        }
        self::assertSame('current ('.self::LITERAL.', 2026-10-21T00:00:00+00:00)', $packet['epic']['approval_state']);
        self::assertStringStartsWith('current ('.self::LITERAL.', ', $packet['descendants'][0]['approval_state']);
        self::assertSame('none', $packet['descendants'][1]['approval_state']);
        self::assertSame('STALE — re-approve before work', $packet['descendants'][2]['approval_state']);
        self::assertSame(self::LITERAL, $packet['epic']['plan']);
        self::assertSame('Epic design.', $packet['epic']['design']);
        self::assertSame(self::LITERAL."\n  indented\n\nlast", $packet['descendants'][0]['plan']);
        self::assertSame('', $packet['descendants'][0]['design']);
        self::assertSame('', $packet['descendants'][1]['plan']);
        self::assertSame(self::LITERAL, $packet['descendants'][1]['design']);
        self::assertSame('', $packet['descendants'][2]['plan']);
        self::assertSame('', $packet['descendants'][2]['design']);
        foreach ($this->expectedScope($store->load('T-002')) + ['owns' => null, 'scope_hash' => null, 'status' => null] as $key => $_) {
            self::assertSame($packet['descendants'][0][$key], $approval[$key]);
        }
        self::assertSame('T-002', $approval['id']);
        self::assertSame(self::LITERAL, $approval['by']);
        foreach ($before as $id => $bytes) {
            self::assertSame($bytes, file_get_contents($store->itemPath($id)));
        }
    }

    #[DataProvider('decorationModes')]
    public function testTextCommandsShareLiteralScopeWithoutTruncationOrTagFormatting(bool $decorated): void
    {
        $this->seedTree();
        $approval = $this->runCli(['command' => 'approve', 'id' => 'T-002', '--by' => self::LITERAL], $decorated);
        $review = $this->runCli(['command' => 'review', 'id' => 'E-001'], $decorated);
        $lines = [
            'kind: task', 'parent: E-001',
            'title: "'.self::LITERAL.' '.str_repeat('ź', 300).' END"',
            'outcome: "first\\n'.self::LITERAL.'\\nlast\\n"',
            'scope in: ["z","a, b","a","b","z","'.self::LITERAL.'"]',
            'scope out: ["none","[]"]',
            'acceptance: ["first\\nsecond","'.self::LITERAL.'"]',
            'depends: ["T-020","T-010"]',
            'owns: ["src/a, b.php","'.self::LITERAL.'"]',
        ];
        foreach ($lines as $line) {
            self::assertStringContainsString("\n".$line."\n", $approval);
            self::assertStringContainsString("\n  ".$line."\n", $review);
        }
        self::assertStringContainsString('Approved T-002 by "'.self::LITERAL.'"', $approval);
        foreach (['parent: none (null)', 'scope in: ["a, b"]', 'scope out: []', 'outcome: ""', 'title: ""', 'acceptance: []', 'owns: []'] as $line) {
            self::assertStringContainsString($line, $review);
        }
        self::assertStringContainsString("plan:\n  ".self::LITERAL, $review);
        self::assertStringContainsString("  plan:\n    ".self::LITERAL."\n      indented\n    \n    last", $review);
        self::assertStringContainsString('plan: missing · design: written', $review);
        self::assertStringContainsString('plan: written · design: missing', $review);
        self::assertStringContainsString("\n  - S-001 · Subtask\n", $review);
        self::assertStringContainsString('2 task(s), 1 subtask(s), 2 open item(s)', $review);
    }

    public static function formats(): iterable
    {
        yield 'text' => [false];
        yield 'JSON' => [true];
    }

    #[DataProvider('formats')]
    public function testApprovalReportsItsLockedSnapshotEvenIfRecordChangesBeforeOutput(bool $json): void
    {
        $this->put([
            'id' => 'T-001', 'kind' => 'task', 'title' => 'Approved title',
            'outcome' => 'Approved outcome', 'scope' => ['in' => ['a, b'], 'out' => []],
            'acceptance' => ['Approved criterion'], 'owns' => ['approved/'],
        ]);
        // An unrelated invalid record makes board refresh issue its normal
        // warning, providing a deterministic hook after the lock is released.
        $this->put(['id' => 'T-999', 'kind' => 'invalid']);
        $store = new ItemStore($this->root);
        $before = $store->load('T-001');
        $errors = new class($store) extends BufferedOutput {
            public function __construct(private readonly ItemStore $store)
            {
                parent::__construct();
            }

            protected function doWrite(string $message, bool $newline): void
            {
                if (str_contains($message, 'warning: board not refreshed:')) {
                    $this->store->mutate('T-001', static function (Item $item): void {
                        $item->title = 'LATER_SCOPE';
                        $item->outcome = 'LATER_OUTCOME';
                        $item->scopeIn = ['LATER_SCOPE_IN'];
                        $item->acceptance = ['LATER_ACCEPTANCE'];
                        $item->owns = ['later/'];
                        $item->status = Status::InProgress;
                    });
                }
                parent::doWrite($message, $newline);
            }
        };
        $output = new class extends ConsoleOutput {
            private string $buffer = '';

            protected function doWrite(string $message, bool $newline): void
            {
                $this->buffer .= $message.($newline ? "\n" : '');
            }

            public function fetch(): string
            {
                return $this->buffer;
            }
        };
        $output->setErrorOutput($errors);
        $application = new Application();
        $application->setAutoExit(false);
        $exit = $application->run(new ArrayInput([
            '--project' => $this->root, 'command' => 'approve', 'id' => 'T-001',
            '--by' => self::LITERAL, '--json' => $json,
        ]), $output);
        $text = $output->fetch();
        self::assertSame(0, $exit, $text);
        self::assertStringContainsString('warning: board not refreshed:', $errors->fetch());
        self::assertSame('LATER_SCOPE', $store->load('T-001')->title);
        self::assertTrue($store->load('T-001')->hasStaleApproval());
        self::assertStringNotContainsString('LATER_', $text);
        self::assertStringNotContainsString('later/', $text);
        if ($json) {
            self::assertSame([
                'id' => 'T-001', 'by' => self::LITERAL,
                'scope_hash' => $before->scopeHash(), 'status' => 'todo',
                ...$this->expectedScope($before), 'owns' => ['approved/'],
            ], json_decode($text, true, flags: JSON_THROW_ON_ERROR));
        } else {
            self::assertStringContainsString('title: Approved title', $text);
            self::assertStringContainsString('outcome: Approved outcome', $text);
            self::assertStringContainsString('scope in: ["a, b"]', $text);
            self::assertStringContainsString('acceptance: ["Approved criterion"]', $text);
            self::assertStringContainsString('owns: ["approved/"]', $text);
            self::assertStringContainsString('hash: '.$before->scopeHash(), $text);
            self::assertStringContainsString('status: todo', $text);
        }
    }

    public function testEmptyEpicReviewJsonHasAnEmptyDecomposition(): void
    {
        $this->put(['id' => 'E-001', 'kind' => 'epic', 'title' => 'Empty']);
        $packet = json_decode($this->runCli([
            'command' => 'review', 'id' => 'E-001', '--json' => true,
        ]), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([], $packet['descendants']);
        self::assertSame(['tasks' => 0, 'subtasks' => 0, 'open' => 0], $packet['counts']);
        self::assertNull($packet['epic']['parent']);
        self::assertSame('', $packet['epic']['outcome']);
        self::assertSame(['in' => [], 'out' => []], $packet['epic']['scope']);
    }
}
