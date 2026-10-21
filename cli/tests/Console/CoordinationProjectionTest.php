<?php

declare(strict_types=1);

namespace Workflow\Tests\Console;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workflow\Console\Application;
use Workflow\Domain\Approval;
use Workflow\Domain\FrontMatter;
use Workflow\Domain\Item;
use Workflow\Tests\WorkflowTestCase;

final class CoordinationProjectionTest extends WorkflowTestCase
{
    private const AT = '2026-10-21T00:00:00+00:00';

    private function runCli(array $input): string
    {
        $application = new Application();
        $application->setAutoExit(false);
        $output = new BufferedOutput();
        $exit = $application->run(new ArrayInput(['--project' => $this->root] + $input), $output);
        $text = $output->fetch();
        self::assertSame(0, $exit, $text);

        return $text;
    }

    /** Raw fixtures isolate read projections from the independently tested write policy. */
    private function seed(): array
    {
        $expected = ['drafts' => [], 'active' => [], 'ready' => [], 'waiting' => [], 'decisions' => [], 'done' => [], 'cancelled' => []];
        foreach ([
            ['drafts', 'draft', 0, 8],
            ['active', 'in-progress', 100, 13],
            ['ready', 'todo', 200, 23],
            ['waiting', 'todo', 300, 9],
            ['done', 'done', 400, 12],
            ['cancelled', 'cancelled', 500, 11],
        ] as [$group, $status, $base, $count]) {
            for ($index = 1; $index <= $count; $index++) {
                $id = sprintf('T-%03d', $base + $index);
                $data = [
                    'id' => $id, 'kind' => 'task', 'title' => $group.' item '.$id, 'status' => $status,
                    'standalone_reason' => 'Independent projection fixture.', 'updated' => self::AT,
                    'depends_on' => $group === 'waiting' ? ['T-101'] : [],
                ];
                $decision = ($group === 'active' && $index <= 7) || ($group === 'ready' && $index <= 2)
                    || ($group === 'waiting' && $index <= 3);
                if ($decision) {
                    $data['last_result'] = [
                        'at' => self::AT, 'role' => 'worker', 'status' => 'needs-decision',
                        'finding' => 'Question for '.$id, 'decision' => 'Choose for '.$id,
                    ];
                    $expected['decisions'][] = $id;
                } elseif ($status === 'done') {
                    $data['last_result'] = ['at' => self::AT, 'role' => 'verifier', 'status' => 'done', 'finding' => 'Checked.'];
                    $data['verified_at'] = self::AT;
                }
                if ($status !== 'draft') {
                    $data['approval'] = (new Approval(self::AT, 'user', Item::fromArray($data, '')->scopeHash()))->toArray();
                }
                $directory = $this->root.'/.workflow/items/'.$id;
                mkdir($directory, 0777, true);
                file_put_contents($directory.'/item.md', FrontMatter::render($data, ''));
                $expected[$group][] = $id;
            }
        }

        return $expected;
    }

    private function section(string $text, string $name): string
    {
        $pattern = '/^'.preg_quote($name, '/').' \(\d+\)\n((?:  [^\n]*\n)*)/m';
        self::assertSame(1, preg_match($pattern, $text, $matches), 'Missing section '.$name.': '.$text);

        return $matches[1];
    }

    private function textIds(string $section): array
    {
        preg_match_all('/^  (T-\d+)\b/m', $section, $matches);

        return $matches[1];
    }

    private function jsonIds(array $data, string $group): array
    {
        return array_column($data[$group], in_array($group, ['ready', 'waiting'], true) ? 'item' : 'id');
    }

    private function tree(): array
    {
        $tree = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root.'/.workflow', \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $file) {
            $tree[substr($file->getPathname(), strlen($this->root))] = $file->isDir() ? null : file_get_contents($file->getPathname());
        }
        ksort($tree);

        return $tree;
    }

    public function testDefaultTextRetainsEveryExistingSectionLimit(): void
    {
        $expected = $this->seed();
        $text = $this->runCli(['command' => 'status']);
        foreach (['drafts' => 5, 'active' => 10, 'ready' => 5, 'waiting' => 5, 'decisions' => 5] as $group => $limit) {
            $section = $this->section($text, $group);
            self::assertSame(array_slice($expected[$group], 0, $limit), $this->textIds($section), $group);
            self::assertStringContainsString('… and '.(count($expected[$group]) - $limit).' more', $section);
            self::assertStringContainsString($group.' ('.count($expected[$group]).')', $text);
        }
        self::assertDoesNotMatchRegularExpression('/^(done|cancelled) \(/m', $text);
        self::assertStringContainsString('53 open · 12 done · 11 cancelled', $text);
    }

    public function testAllTextHasNoHiddenCapsInAnyCategory(): void
    {
        $expected = $this->seed();
        $text = $this->runCli(['command' => 'status', '--all' => true]);
        foreach ($expected as $group => $ids) {
            self::assertSame($ids, $this->textIds($this->section($text, $group)), $group);
        }
        self::assertStringNotContainsString('… and ', $text);
        self::assertStringContainsString('53 open · 12 done · 11 cancelled', $text);
    }

    public function testDefaultJsonRetainsItsSchemaAndUncappedWorkingSet(): void
    {
        $expected = $this->seed();
        $data = json_decode($this->runCli(['command' => 'status', '--json' => true]), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(['project', 'active', 'drafts', 'ready', 'waiting', 'decisions', 'counts'], array_keys($data));
        self::assertSame(basename($this->root), $data['project']);
        foreach (['active', 'drafts', 'ready', 'waiting', 'decisions'] as $group) {
            self::assertSame($expected[$group], $this->jsonIds($data, $group), $group);
        }
        self::assertSame(['draft' => 8, 'todo' => 32, 'in-progress' => 13, 'done' => 12, 'cancelled' => 11], $data['counts']);
        self::assertArrayNotHasKey('done', $data);
        self::assertArrayNotHasKey('cancelled', $data);
    }

    public function testAllJsonAddsCompleteTerminalGroupsWithoutChangingExistingGroups(): void
    {
        $expected = $this->seed();
        $default = json_decode($this->runCli(['command' => 'status', '--json' => true]), true, flags: JSON_THROW_ON_ERROR);
        $all = json_decode($this->runCli(['command' => 'status', '--all' => true, '--json' => true]), true, flags: JSON_THROW_ON_ERROR);
        foreach ($default as $key => $value) {
            self::assertSame($value, $all[$key], $key);
        }
        foreach ($expected as $group => $ids) {
            self::assertSame($ids, $this->jsonIds($all, $group), $group);
        }
        foreach (['done', 'cancelled'] as $group) {
            foreach ($all[$group] as $item) {
                self::assertSame($group, $item['status']);
                self::assertSame('task', $item['kind']);
            }
        }
    }

    public function testEmptyProjectStillIncludesEmptyTerminalGroupsOnlyWhenRequested(): void
    {
        mkdir($this->root.'/.workflow');
        $default = json_decode($this->runCli(['command' => 'status', '--json' => true]), true, flags: JSON_THROW_ON_ERROR);
        $all = json_decode($this->runCli(['command' => 'status', '--all' => true, '--json' => true]), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([], $all['done']);
        self::assertSame([], $all['cancelled']);
        unset($all['done'], $all['cancelled']);
        self::assertSame($default, $all);
        self::assertStringContainsString('0 open · 0 done · 0 cancelled', $this->runCli(['command' => 'status', '--all' => true]));
    }

    public function testCoordinationReadsDoNotRewriteRecordsOrGenerateFiles(): void
    {
        $this->seed();
        $before = $this->tree();
        foreach ([
            ['command' => 'status'],
            ['command' => 'status', '--all' => true],
            ['command' => 'status', '--json' => true],
            ['command' => 'status', '--all' => true, '--json' => true],
            ['command' => 'ready', '--all' => true, '--json' => true],
            ['command' => 'validate', '--json' => true],
        ] as $input) {
            $this->runCli($input);
        }
        self::assertSame($before, $this->tree());
    }
}
