<?php

declare(strict_types=1);

namespace Workflow\Tests\Console;

use PHPUnit\Framework\Attributes\DataProvider;
use Workflow\Tests\WorkflowTestCase;

final class InstallHandoffTest extends WorkflowTestCase
{
    private string $kit;
    private string $runner;

    protected function setUp(): void
    {
        $this->root = '/tmp/opencode/handoff-test-'.bin2hex(random_bytes(6));
        mkdir($this->root, 0777, true);
        $this->kit = $this->root.'/kit';
        foreach ([
            'AGENTS.md' => 'kit rules',
            'README.md' => 'kit readme',
            'LICENSE' => 'kit license',
            '.agents/skills/example/SKILL.md' => 'kit skill',
            'docs/install.md' => 'kit instructions',
            'docs/opencode.example.json' => '{"default_agent":"workflow-orchestrator","agents":{}}',
            'cli/bin/workflow' => '#!/usr/bin/env php',
            'cli/templates/PRD.md' => '# {{PROJECT}} PRD {{DATE}}',
            'cli/templates/Architecture.md' => '# {{PROJECT}} architecture',
            '.workflow/PRD.md' => 'SOURCE HISTORY MUST NOT COPY',
        ] as $path => $contents) {
            $this->put($this->kit.'/'.$path, $contents);
        }

        // Load only the command under a synthetic kit root in a fresh process.
        $this->put(
            $this->kit.'/cli/src/Console/Command/InstallCommand.php',
            (string) file_get_contents(dirname(__DIR__, 2).'/src/Console/Command/InstallCommand.php'),
        );
        $this->runner = $this->root.'/run.php';
        $this->put($this->runner, '<?php
require '.var_export(dirname(__DIR__, 2).'/vendor/autoload.php', true).';
require '.var_export($this->kit.'/cli/src/Console/Command/InstallCommand.php', true).';
$application = new Symfony\Component\Console\Application();
$application->setAutoExit(false);
$application->addCommand(new Workflow\Console\Command\InstallCommand());
$input = ["command" => "install", "target" => $argv[1], "--name" => "Fixture"];
if (($argv[2] ?? "") === "force") { $input["--force"] = true; }
exit($application->run(new Symfony\Component\Console\Input\ArrayInput($input)));
');
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $file) {
            if ($file->isLink() || !$file->isDir()) {
                unlink($file->getPathname());
            } else {
                rmdir($file->getPathname());
            }
        }
        rmdir($this->root);
    }

    private function put(string $path, string $contents): void
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, $contents);
    }

    /** @return array{int, string} */
    private function install(string $target, bool $force = false, ?string $pathDir = null): array
    {
        $process = proc_open(
            [PHP_BINARY, $this->runner, $target, $force ? 'force' : 'default'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $this->root,
            ['PATH' => $pathDir ?? ''],
        );
        self::assertIsResource($process);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $output.$error];
    }

    public function testFreshScaffoldCreatesOpencodeConfigurationWithVerifiableSteps(): void
    {
        $target = $this->root.'/host/project';
        [$exit, $output] = $this->install($target);
        self::assertSame(0, $exit, $output);

        self::assertStringContainsString('Scaffold copied into', $output);
        self::assertStringContainsString('created opencode.json', $output);
        self::assertSame(
            '{"default_agent":"workflow-orchestrator","agents":{}}',
            file_get_contents($target.'/opencode.json'),
        );
        self::assertFileExists($target.'/docs/opencode.example.json');
        self::assertStringContainsString('Skills under .agents/skills/ are auto-discovered', $output);
        self::assertStringContainsString('workflow-orchestrator primary/default', $output);
        self::assertStringContainsString(
            'workflow-explorer, workflow-worker and workflow-verifier subagents',
            $output,
        );
        self::assertStringContainsString('opencode CLI not found on PATH', $output);
        self::assertStringContainsString('opencode api agent.list', $output);
        self::assertStringContainsString('location[directory]='.$target, $output);
        self::assertStringContainsString('not a paid LLM proof', $output);
        self::assertStringContainsString('Manually merge the kit AGENTS.md', $output);
        self::assertStringContainsString('composer install --no-dev', $output);
        self::assertStringContainsString('PATH', $output);
        self::assertStringContainsString('board', $output);

        // No full configuration dump.
        self::assertStringNotContainsString('"$schema"', $output);
        self::assertStringNotContainsString('Installed into', $output);
        self::assertStringNotContainsString('opencode debug agents', $output);
    }

    public static function forceModes(): array
    {
        return ['default' => [false], 'force' => [true]];
    }

    #[DataProvider('forceModes')]
    public function testExistingHostConfigurationIsPreservedAndNeverPrinted(bool $force): void
    {
        $target = $this->root.'/host';
        $hostConfig = "{\n"
            ."  \"default_agent\": \"host-orchestrator\",\n"
            ."  \"agents\": {\"host-orchestrator\": {\"mode\": \"primary\"}},\n"
            ."  \"model\": \"host/HOST_SECRET_MODEL\"\n"
            ."}\n";
        $this->put($target.'/opencode.json', $hostConfig);

        [$exit, $output] = $this->install($target, $force);
        self::assertSame(0, $exit, $output);
        self::assertSame($hostConfig, file_get_contents($target.'/opencode.json'));
        self::assertStringContainsString('never overwrite host settings', $output);
        self::assertStringContainsString('manually merge only the missing agents', $output);
        self::assertStringNotContainsString('HOST_SECRET_MODEL', $output);
        self::assertStringNotContainsString('host-orchestrator', $output);
    }
}
