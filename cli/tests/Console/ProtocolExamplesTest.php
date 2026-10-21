<?php

declare(strict_types=1);

namespace Workflow\Tests\Console;

use PHPUnit\Framework\Attributes\DataProvider;
use Workflow\Domain\Kind;
use Workflow\Domain\Status;
use Workflow\Store\ItemStore;
use Workflow\Tests\WorkflowTestCase;

final class ProtocolExamplesTest extends WorkflowTestCase
{
    private string $kit;

    protected function setUp(): void
    {
        $this->root = '/tmp/opencode/protocol-test-'.bin2hex(random_bytes(6));
        mkdir($this->root, 0777, true);
        $this->kit = dirname(__DIR__, 3);
    }

    /** @return array{int, string} */
    private function runProcess(array $command, string $directory): array
    {
        $environment = getenv();
        $environment['TMPDIR'] = $this->root;
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $directory, $environment);
        self::assertIsResource($process);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $output.$error];
    }

    /** @return array{int, string} */
    private function cli(string $project, array $arguments): array
    {
        return $this->runProcess([PHP_BINARY, $this->kit.'/cli/bin/workflow', '--project', $project, ...$arguments], $this->root);
    }

    private function succeed(string $project, array $arguments): string
    {
        [$exit, $output] = $this->cli($project, $arguments);
        self::assertSame(0, $exit, $output);

        return $output;
    }

    private function example(string $document, string $name): string
    {
        $contents = (string) file_get_contents($this->kit.'/'.$document);
        self::assertSame(1, preg_match(
            '/<!-- protocol-example: '.preg_quote($name, '/').' -->\R```sh\R(.*?)\R```/s',
            $contents,
            $match,
        ), 'Missing executable example '.$name);

        return $match[1];
    }

    private function runExample(string $document, string $name, string $label): array
    {
        $script = $this->example($document, $name);
        [$exit, $output] = $this->runProcess(['bash', '-euo', 'pipefail', '-c', $script], $this->kit);
        self::assertSame(0, $exit, $output);
        self::assertSame(1, preg_match('/^'.preg_quote($label, '/').' (.+)$/m', $output, $match), $output);
        self::assertStringStartsWith($this->root.'/', $match[1]);

        return [$match[1], $output, $script];
    }

    public function testReadmeQuickstartExecutesStagedApprovalAndVerifierThenClose(): void
    {
        [$project, $output, $script] = $this->runExample('README.md', 'quickstart', 'Demo retained at');
        $store = new ItemStore($project);
        self::assertSame(['E-001', 'T-001'], $store->ids());
        $epic = $store->load('E-001');
        $task = $store->load('T-001');
        self::assertSame(Kind::Epic, $epic->kind);
        self::assertSame([], $epic->owns);
        self::assertSame(Kind::Task, $task->kind);
        self::assertSame('E-001', $task->parent);
        self::assertStringContainsString('Scope map: T-001', $epic->section('Design'));
        self::assertStringContainsString('Follow parent Design', $task->section('Design'));
        self::assertSame("hello\n", file_get_contents($project.'/.workflow/items/T-001/outputs/greeting.txt'));
        foreach ([$epic, $task] as $item) {
            self::assertSame(Status::Done, $item->status);
            self::assertTrue($item->isApproved());
            self::assertNotNull($item->verifiedAt);
            self::assertSame('verifier', $item->lastResult['role']);
            self::assertSame('done', $item->lastResult['status']);
            self::assertSame($item->verifiedAt, $item->lastResult['at']);
            self::assertStringNotContainsString('Verification exception', $item->body);
            self::assertStringContainsString('no exploration needed', $item->section('Plan'));
            self::assertNotEmpty($item->artifacts);
            foreach ($item->artifacts as $path) {
                self::assertStringStartsWith('.workflow/items/', $path);
                self::assertFileExists($project.'/'.$path);
            }
        }
        self::assertSame(2, substr_count($script, 'workflow review E-001'));
        self::assertSame(1, substr_count($script, 'workflow approve E-001'));
        self::assertStringContainsString('(no child items)', $output);
        self::assertStringContainsString('1 task(s), 0 subtask(s)', $output);
        self::assertLessThan(
            strpos($task->body, '· verifier · done'),
            strpos($task->body, '· worker · done'),
        );
        self::assertLessThan(strpos($task->body, 'closed as done'), strpos($task->body, '· verifier · done'));
        self::assertStringContainsString('OK (0 warnings)', $this->succeed($project, ['validate']));
        self::assertStringContainsString('does not authenticate role independence', (string) file_get_contents($this->kit.'/README.md'));
    }

    public function testInstallSmokeRetainsCancelledHistoryAndCanonicalRoleLoading(): void
    {
        [$project] = $this->runExample('docs/install.md', 'install-smoke', 'Smoke retained at');
        $store = new ItemStore($project);
        self::assertSame(['T-001'], $store->ids(), 'Source history must not be imported.');
        $item = $store->load('T-001');
        self::assertSame(Status::Cancelled, $item->status);
        self::assertNull($item->parent);
        self::assertNotEmpty($item->extra['standalone_reason']);
        self::assertTrue($item->isApproved());
        self::assertNull($item->verifiedAt);
        self::assertStringContainsString('Isolated smoke completed', $item->body);
        self::assertFileExists($project.'/.workflow/board.html');
        self::assertFileDoesNotExist($project.'/cli/vendor');
        self::assertFileEquals($this->kit.'/AGENTS.md', $project.'/AGENTS.md');
        foreach (['orchestrator', 'explorer', 'worker', 'verifier'] as $role) {
            // Static prompt checks cannot establish actual LLM loading or independence.
            $prompt = (string) file_get_contents($project.'/.agents/roles/'.$role.'.md');
            self::assertStringContainsString("First read the project's root `AGENTS.md` in full", $prompt, $role);
            self::assertStringContainsString('even when this role is loaded', $prompt, $role);
            self::assertStringContainsString('absent, incomplete', $prompt, $role);
            self::assertStringContainsString('or conflicts with these instructions', $prompt, $role);
            self::assertStringContainsString('reviewed manual merge', $prompt, $role);
            self::assertStringContainsString('do not replace or assume away host authority', $prompt, $role);
            self::assertStringContainsString('AGENTS receipt', $prompt, $role);
        }
        self::assertStringContainsString('OK (0 warnings)', $this->succeed($project, ['validate']));
    }

    public static function forceModes(): iterable
    {
        yield 'default' => [false];
        yield 'force' => [true];
    }

    #[DataProvider('forceModes')]
    public function testRealKitScaffoldPreservesHostAuthorityAndHistory(bool $force): void
    {
        $project = $this->root.'/host';
        $preserved = [
            'AGENTS.md', 'README.md', 'LICENSE', '.gitignore', 'opencode.json',
            '.workflow/PRD.md', '.workflow/Architecture.md', '.workflow/.gitignore',
            '.workflow/items/E-777/outputs/host.txt', 'docs/target-only.md', 'docs/.gitignore',
        ];
        foreach ($preserved as $path) {
            $this->put($project.'/'.$path, 'Host authority/history: '.$path."\n");
        }
        // A preserved host AGENTS without workflow rules still needs a reviewed manual merge.
        $history = "---\nid: E-777\nkind: epic\ntitle: Completed host work\nstatus: done\nphase: verify\n---\n\nHistorical host narrative.\n";
        $this->put($project.'/.workflow/items/E-777/item.md', $history);
        $this->put($project.'/docs/install.md', 'Host-customized instructions');
        $arguments = ['install', $project];
        if ($force) {
            $arguments[] = '--force';
        }

        $output = $this->succeed($project, $arguments);

        foreach ($preserved as $path) {
            self::assertSame('Host authority/history: '.$path."\n", file_get_contents($project.'/'.$path), $path);
        }
        self::assertSame($history, file_get_contents($project.'/.workflow/items/E-777/item.md'));
        self::assertSame(['E-777'], (new ItemStore($project))->ids());
        self::assertSame(
            $force ? file_get_contents($this->kit.'/docs/install.md') : 'Host-customized instructions',
            file_get_contents($project.'/docs/install.md'),
        );
        self::assertStringContainsString('Manually merge the kit AGENTS.md', $output);
        self::assertStringContainsString('no automatic merges or deletions', $output);
        self::assertStringContainsString('composer install --no-dev', $output);
    }

    public function testActiveDocumentationLinksAndArchitectureSourceReferencesResolve(): void
    {
        $documents = [
            'AGENTS.md', 'README.md', 'docs/install.md', '.workflow/PRD.md', '.workflow/Architecture.md',
            '.agents/roles/orchestrator.md', '.agents/roles/explorer.md', '.agents/roles/worker.md', '.agents/roles/verifier.md',
            '.agents/skills/workflow-cli/SKILL.md', '.agents/skills/decompose-work/SKILL.md',
            '.agents/skills/install-workflow/SKILL.md', '.agents/skills/fresh-session-handoff/SKILL.md',
            '.agents/skills/verify-outcome/SKILL.md',
        ];
        foreach ($documents as $document) {
            $text = (string) file_get_contents($this->kit.'/'.$document);
            preg_match_all('/\]\(([^)]+)\)/', $text, $links);
            foreach ($links[1] as $link) {
                self::assertFileExists(dirname($this->kit.'/'.$document).'/'.$link, $document.': '.$link);
            }
        }
        $architecture = (string) file_get_contents($this->kit.'/.workflow/Architecture.md');
        preg_match_all('~`(cli/(?:src/[A-Za-z/]+(?:\.php)?|templates/))`~', $architecture, $references);
        self::assertGreaterThan(10, count($references[1]));
        foreach ($references[1] as $path) {
            self::assertTrue(file_exists($this->kit.'/'.$path), $path);
        }
        self::assertStringNotContainsString('cli/src/Store/FrontMatter.php', $architecture);
    }

    public function testDocumentedSmallWorkFlagsAndForceExceptionPreserveCompletedHistory(): void
    {
        $project = $this->root.'/small';
        mkdir($project);
        $this->succeed($project, ['init']);
        $this->succeed($project, [
            'new', 'Small example', '--standalone-reason', 'One independently acceptable example',
            '--outcome', 'The example records an explicit exception.',
        ]);
        $this->succeed($project, ['set', 'T-001', 'scope_in=Example only', 'scope_out=Real work', 'acceptance=Exception is not verifier success']);
        $this->succeed($project, ['plan', 'T-001', '--text', 'Use the fixed example; no exploration needed; check exception semantics.']);
        $this->succeed($project, ['design', 'T-001', '--text', 'One record; inspect exception without claiming verification; no open risks.']);
        $brief = json_decode($this->succeed($project, ['brief', 'T-001', '--for', 'orchestrator', '--json']), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(['Exception is not verifier success'], $brief['item']['acceptance']);
        $this->succeed($project, ['approve', 'T-001', '--by', 'example-user']);
        $store = new ItemStore($project);
        $approval = $store->load('T-001')->approval?->toArray();
        $this->put($this->root.'/design.md', 'One record; inspect exception without claiming verification; no open risks.');
        $this->succeed($project, ['design', 'T-001', '--file', $this->root.'/design.md', '--phase', 'implement', '--start']);
        self::assertSame(Status::InProgress, $store->load('T-001')->status);
        self::assertSame($approval, $store->load('T-001')->approval?->toArray());

        [$exit] = $this->cli($project, ['close', 'T-001']);
        self::assertNotSame(0, $exit);
        $this->succeed($project, ['close', 'T-001', '--force', '--reason', 'Deliberate isolated example, not independent verification']);
        $done = $store->load('T-001');
        self::assertSame(Status::Done, $done->status);
        self::assertNull($done->verifiedAt);
        self::assertNull($done->lastResult);
        self::assertStringContainsString('Verification exception: explicit --force', $done->body);
        $before = file_get_contents($store->itemPath('T-001'));

        [$exit, $output] = $this->cli($project, ['design', 'T-001', '--text', 'New material work', '--start']);
        self::assertNotSame(0, $exit);
        self::assertStringContainsString('create a new follow-up item', $output);
        self::assertSame($before, file_get_contents($store->itemPath('T-001')));
        self::assertSame(['T-001'], $store->ids(), 'No automatic successor.');
        $this->succeed($project, ['log', 'T-001', 'Non-material historical note']);
        $this->succeed($project, ['set', 'T-001', 'priority=3']);
        self::assertSame(Status::Done, $store->load('T-001')->status);
        self::assertSame($approval, $store->load('T-001')->approval?->toArray());
    }

    private function put(string $path, string $contents): void
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, $contents);
    }
}
