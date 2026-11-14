<?php

declare(strict_types=1);

namespace Workflow\Tests\Console;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workflow\Console\Application;
use Workflow\Domain\Item;
use Workflow\Domain\Run;
use Workflow\Domain\Status;
use Workflow\Domain\FrontMatter;
use Workflow\Domain\InvalidItemException;
use Workflow\Store\ItemStore;
use Workflow\Store\RunStore;
use Workflow\Tests\WorkflowTestCase;

final class RunApprovalCommandTest extends WorkflowTestCase
{
    private const RUN = 'R-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private function command(array $args): array
    {
        $app = new Application(); $app->setAutoExit(false);
        $out = new BufferedOutput();
        $code = $app->run(new ArrayInput(['--project' => $this->root] + $args), $out);
        return [$code, $out->fetch()];
    }

    public function testRunApprovalsAndTruthfulProjections(): void
    {
        $store = new ItemStore($this->root);
        $store->create(Item::fromArray(['id' => 'E-001', 'kind' => 'epic', 'parent' => null, 'title' => 'Root'], ''));
        $store->create(Item::fromArray(['id' => 'T-001', 'kind' => 'task', 'parent' => 'E-001', 'title' => 'Task'], ''));
        (new RunStore($this->root))->create(Run::fromArray([
            'version' => 1, 'id' => self::RUN, 'autonomous' => true, 'goal' => 'Goal', 'success_criteria' => ['criterion'],
            'permissions' => ['actions' => ['scope-approve', 'design-approve'], 'roots' => ['E-001']],
            'limits' => ['expires_at' => '2099-01-01T00:00:00Z', 'max_roots' => 1], 'status' => 'active',
            'status_reason' => null, 'recovery' => null, 'created_at' => '2020-01-01T00:00:00Z', 'updated_at' => '2020-01-01T00:00:00Z',
        ]));
        $path = $store->itemPath('T-001'); $before = file_get_contents($path);
        [$code] = $this->command(['command' => 'approve', 'id' => 'T-001', '--run' => self::RUN, '--by' => 'human']);
        self::assertNotSame(0, $code); self::assertSame($before, file_get_contents($path));
        [$code, $output] = $this->command(['command' => 'approve', 'id' => 'T-001', '--run' => self::RUN, '--json' => true]);
        self::assertSame(0, $code, $output);
        self::assertSame(self::RUN, json_decode($output, true, flags: JSON_THROW_ON_ERROR)['provenance']['run_id']);
        $before = file_get_contents($path);
        foreach ([['--phase' => 'plan'], ['--start' => true], ['--by' => 'human'], ['--confirmation' => 'yes']] as $flag) {
            [$code] = $this->command(['command' => 'design', 'id' => 'T-001', '--run' => self::RUN, '--text' => 'Design', '--approve' => true] + $flag);
            self::assertNotSame(0, $code); self::assertSame($before, file_get_contents($path));
        }
        foreach (['plan', 'explore', 'design', 'implement', 'verify'] as $phase) {
            for ($mask = 1; $mask < 16; ++$mask) {
                $flags = [];
                if ($mask & 1) $flags['--phase'] = $phase;
                if ($mask & 2) $flags['--start'] = true;
                if ($mask & 4) $flags['--by'] = 'human';
                if ($mask & 8) $flags['--confirmation'] = 'yes';
                [$code] = $this->command(['command' => 'design', 'id' => 'T-001', '--run' => self::RUN, '--text' => 'Design', '--approve' => true] + $flags);
                self::assertNotSame(0, $code); self::assertSame($before, file_get_contents($path));
            }
        }
        $file = $this->root.'/design.txt'; file_put_contents($file, "File design\r\n### Detail\r\nSafe");
        foreach ([
            ['--text' => 'x', '--file' => $file, '--approve' => true],
            ['--approve' => true],
            ['--text' => 'x'],
            ['--text' => "x\r\n## Plan\r\nunsafe", '--approve' => true],
            ['--text' => "x\n## Design\nunsafe", '--approve' => true],
            ['--text' => "x\n## Decisions\nunsafe", '--approve' => true],
            ['--text' => "x\n## Progress\nunsafe", '--approve' => true],
        ] as $options) {
            [$code] = $this->command(['command' => 'design', 'id' => 'T-001', '--run' => self::RUN] + $options);
            self::assertNotSame(0, $code); self::assertSame($before, file_get_contents($path));
        }
        [$code, $fileOutput] = $this->command(['command' => 'design', 'id' => 'T-001', '--run' => self::RUN, '--file' => $file, '--approve' => true]);
        self::assertSame(0, $code, $fileOutput);
        self::assertSame("File design\n### Detail\nSafe", $store->load('T-001')->section('Design'));
        foreach ([Status::Review, Status::Cancelled] as $status) {
            $store->mutate('T-001', static function (Item $item) use ($status): void { $item->status = $status; });
            $state = file_get_contents($path);
            [$code] = $this->command(['command' => 'design', 'id' => 'T-001', '--run' => self::RUN, '--text' => 'No', '--approve' => true]);
            self::assertNotSame(0, $code); self::assertSame($state, file_get_contents($path));
            $store->mutate('T-001', static function (Item $item): void { $item->status = Status::Todo; });
        }
        $epicPath = $store->itemPath('E-001'); $epic = file_get_contents($epicPath);
        [$code] = $this->command(['command' => 'design', 'id' => 'E-001', '--run' => self::RUN, '--text' => 'No', '--approve' => true]);
        self::assertNotSame(0, $code); self::assertSame($epic, file_get_contents($epicPath));
        [$code, $output] = $this->command(['command' => 'design', 'id' => 'T-001', '--run' => self::RUN, '--text' => "Design\n### Detail", '--approve' => true]);
        self::assertSame(0, $code, $output); self::assertStringContainsString('Automated design approval by run', $output);
        self::assertStringNotContainsString('explicit user confirmation', $output);
        [$code, $output] = $this->command(['command' => 'brief', 'id' => 'T-001', '--for' => 'verifier', '--json' => true]);
        self::assertSame(0, $code, $output);
        $packet = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(self::RUN, $packet['approval_provenance']['run_id']);
        self::assertSame(self::RUN, $packet['design_approval_provenance']['run_id']);
        self::assertArrayNotHasKey('last_result', $packet['item']);
        [$code, $review] = $this->command(['command' => 'review', 'id' => 'E-001', '--json' => true]);
        self::assertSame(0, $code, $review);
        $record = json_decode($review, true, flags: JSON_THROW_ON_ERROR)['descendants'][0];
        self::assertSame(self::RUN, $record['approval']['provenance']['run_id']);
        self::assertSame(self::RUN, $record['design_approval']['provenance']['run_id']);
        $valid = (string) file_get_contents($path); $parsed = FrontMatter::parse($valid);
        foreach (['wrong-key', 'extra', 'quote', 'invalid-run'] as $case) {
            $data = $parsed['data'];
            switch ($case) {
                case 'wrong-key': $data['approval']['design_hash'] = $data['approval']['scope_hash']; unset($data['approval']['scope_hash']); break;
                case 'extra': $data['approval']['extra'] = 'unexpected'; break;
                case 'quote': $data['approval']['confirmation'] = 'yes'; break;
                case 'invalid-run': $data['approval']['provenance']['run_id'] = '../bad'; break;
            }
            file_put_contents($path, FrontMatter::render($data, $parsed['body']));
            try { $store->load('T-001'); self::fail('Malformed approval accepted: '.$case); }
            catch (InvalidItemException) { self::assertNotEmpty((new \Workflow\Validate\ItemValidator($store))->validate()->errors); }
        }
        file_put_contents($path, $valid);
        (new RunStore($this->root))->mutate(self::RUN, static function (Run $run): Run {
            $data = $run->toArray(); $data['status'] = 'revoked'; $data['status_reason'] = 'stop'; return Run::fromArray($data);
        });
        self::assertTrue($store->load('T-001')->isApproved());
        self::assertTrue($store->load('T-001')->isDesignApproved());
        $warnings = implode("\n", (new \Workflow\Validate\ItemValidator($store))->validate()->warnings);
        self::assertStringNotContainsString('T-001: approval has no recorded confirmation quote', $warnings);
        self::assertStringNotContainsString('T-001: design approval has no recorded confirmation quote', $warnings);
    }
}
