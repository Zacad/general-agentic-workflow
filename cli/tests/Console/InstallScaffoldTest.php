<?php

declare(strict_types=1);

namespace Workflow\Tests\Console;

use PHPUnit\Framework\Attributes\DataProvider;
use Workflow\Tests\WorkflowTestCase;

final class InstallScaffoldTest extends WorkflowTestCase
{
    private string $kit;
    private string $runner;

    protected function setUp(): void
    {
        $this->root = '/tmp/opencode/scaffold-test-'.bin2hex(random_bytes(6));
        mkdir($this->root, 0777, true);
        $this->kit = $this->root.'/kit';
        foreach ([
            'AGENTS.md' => 'kit rules',
            'README.md' => 'kit readme',
            'LICENSE' => 'kit license',
            '.agents/skills/example/SKILL.md' => 'kit skill',
            'docs/install.md' => 'kit instructions',
            'docs/.gitignore' => 'kit ignore',
            'cli/bin/workflow' => '#!/usr/bin/env php',
            'cli/templates/PRD.md' => '# {{PROJECT}} PRD {{DATE}}',
            'cli/templates/Architecture.md' => '# {{PROJECT}} architecture',
            '.workflow/PRD.md' => 'SOURCE HISTORY MUST NOT COPY',
            '.workflow/items/E-999/outputs/private.md' => 'SOURCE OUTPUT MUST NOT COPY',
            'cli/vendor/private.php' => 'do not copy dependencies',
            'cli/tools/private.php' => 'do not copy tools',
        ] as $path => $contents) {
            $this->put($this->kit.'/'.$path, $contents);
        }

        // Load only the command under a synthetic kit root in a fresh process.
        // A containment regression can then copy at most this tiny fixture,
        // never recursively copy the actual repository.
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
    private function install(string $target, bool $force = false): array
    {
        $process = proc_open(
            [PHP_BINARY, $this->runner, $target, $force ? 'force' : 'default'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $this->root,
        );
        self::assertIsResource($process);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $output.$error];
    }

    public function testFreshScaffoldExcludesHistoryAndDependenciesAndGivesManualGuidance(): void
    {
        $target = $this->root.'/host/missing/project';
        [$exit, $output] = $this->install($target);
        self::assertSame(0, $exit, $output);
        self::assertSame('kit rules', file_get_contents($target.'/AGENTS.md'));
        self::assertSame('kit readme', file_get_contents($target.'/README.md'));
        self::assertSame('kit license', file_get_contents($target.'/LICENSE'));
        self::assertSame('kit skill', file_get_contents($target.'/.agents/skills/example/SKILL.md'));
        self::assertStringContainsString('Scaffold copied into', $output);
        self::assertStringNotContainsString('opencode debug agents', $output);
        self::assertStringContainsString('# Fixture PRD', file_get_contents($target.'/.workflow/PRD.md'));
        self::assertFileExists($target.'/.workflow/board.html');
        self::assertFileDoesNotExist($target.'/.workflow/items/E-999');
        self::assertFileDoesNotExist($target.'/cli/vendor');
        self::assertFileDoesNotExist($target.'/cli/tools');
        self::assertSame(0755, fileperms($target.'/cli/bin/workflow') & 0777);
        self::assertStringContainsString('Manually merge the kit AGENTS.md', $output);
        self::assertStringContainsString('composer install --no-dev', $output);
    }

    public static function forceModes(): array
    {
        return ['default' => [false], 'force' => [true]];
    }

    #[DataProvider('forceModes')]
    public function testHostAuthorityAndHistoryArePreservedWhileOrdinaryFilesFollowForce(bool $force): void
    {
        $target = $this->root.'/host';
        $preserved = [
            'AGENTS.md', 'README.md', 'LICENSE', '.gitignore', 'opencode.json',
            '.claude/settings.json', 'docs/.gitignore',
            '.workflow/PRD.md', '.workflow/Architecture.md', '.workflow/.gitignore',
            '.workflow/items/E-123/item.md', '.workflow/items/E-123/outputs/host.md',
            'docs/target-only.md',
        ];
        foreach ($preserved as $path) {
            $contents = $path === '.workflow/items/E-123/item.md'
                ? "---\nid: E-123\nkind: epic\ntitle: Host history\nstatus: done\nphase: verify\n---\n\nHost body\n"
                : "host bytes: $path\n\0";
            $this->put($target.'/'.$path, $contents);
            $before[$path] = $contents;
        }
        $this->put($target.'/docs/install.md', 'old instructions');
        $this->put($target.'/.agents/skills/example/SKILL.md', 'old skill');
        $this->put($target.'/.workflow/board.html', 'old derived board');

        [$exit, $output] = $this->install($target, $force);
        self::assertSame(0, $exit, $output);
        foreach ($before as $path => $contents) {
            self::assertSame($contents, file_get_contents($target.'/'.$path), $path);
        }
        self::assertSame($force ? 'kit instructions' : 'old instructions', file_get_contents($target.'/docs/install.md'));
        self::assertSame($force ? 'kit skill' : 'old skill', file_get_contents($target.'/.agents/skills/example/SKILL.md'));
        self::assertNotSame('old derived board', file_get_contents($target.'/.workflow/board.html'));
        self::assertFileDoesNotExist($target.'/.workflow/items/E-999');
    }

    public static function containedTargets(): array
    {
        return [
            'equal' => ['kit'],
            'descendant' => ['kit/new/target'],
            'ancestor' => ['.'],
            'equal alias' => ['alias'],
            'symlink missing suffix' => ['alias/new/target'],
            'symlink then parent' => ['child-alias/../new/target'],
            'symlink then parent ancestor' => ['alias/..'],
            'missing then parent' => ['kit/missing/../new/target'],
        ];
    }

    #[DataProvider('containedTargets')]
    public function testContainmentRefusesBeforeWriting(string $relative): void
    {
        mkdir($this->kit.'/child');
        symlink($this->kit, $this->root.'/alias');
        symlink($this->kit.'/child', $this->root.'/child-alias');
        $before = $this->snapshot();
        [$exit, $output] = $this->install($this->root.'/'.$relative, true);
        self::assertSame(2, $exit, $output);
        self::assertSame($before, $this->snapshot());
    }

    public function testFilesystemRootIsAnAncestor(): void
    {
        // Exercise the root boundary as a pure guard, not a copy command: even
        // a broken guard must never write the filesystem root during a test.
        $command = new \Workflow\Console\Command\InstallCommand();
        $contains = new \ReflectionMethod($command, 'contains');
        self::assertTrue($contains->invoke($command, '/', $this->kit));
        self::assertFalse($contains->invoke($command, $this->kit, $this->kit.'-sibling'));
    }

    public static function invalidComponents(): array
    {
        return [
            'dangling final' => ['dangling'],
            'dangling before missing' => ['dangling/child'],
            'dangling before parent' => ['dangling/../host'],
            'file final' => ['file'],
            'file before missing' => ['file/child'],
            'file before parent' => ['file/../host'],
            'file symlink before parent' => ['file-link/../host'],
        ];
    }

    #[DataProvider('invalidComponents')]
    public function testInvalidComponentsCannotBeLexicallyBypassed(string $relative): void
    {
        symlink($this->root.'/absent', $this->root.'/dangling');
        $this->put($this->root.'/file', 'not a directory');
        symlink($this->root.'/file', $this->root.'/file-link');
        $before = $this->snapshot();
        [$exit, $output] = $this->install($this->root.'/'.$relative);
        self::assertSame(2, $exit, $output);
        self::assertStringContainsString('dangling or not a directory', $output);
        self::assertSame($before, $this->snapshot());
    }

    public function testOutsideSymlinkThenParentUsesPhysicalParentAndRetainsMissingSuffix(): void
    {
        mkdir($this->root.'/outside/deep', 0777, true);
        symlink($this->root.'/outside/deep', $this->root.'/alias');
        [$exit, $output] = $this->install($this->root.'/alias/../missing/project');
        self::assertSame(0, $exit, $output);
        self::assertFileExists($this->root.'/outside/missing/project/AGENTS.md');
        self::assertFileDoesNotExist($this->root.'/missing');
    }

    public function testRelativeSiblingWithSharedPrefixIsNotContained(): void
    {
        [$exit, $output] = $this->install('kit-sibling');
        self::assertSame(0, $exit, $output);
        self::assertFileExists($this->root.'/kit-sibling/AGENTS.md');
    }

    public function testExistingOutsideSymlinkAliasResolvesToItsDestination(): void
    {
        mkdir($this->root.'/outside');
        symlink($this->root.'/outside', $this->root.'/alias');
        [$exit, $output] = $this->install($this->root.'/alias');
        self::assertSame(0, $exit, $output);
        self::assertFileExists($this->root.'/outside/AGENTS.md');
        self::assertTrue(is_link($this->root.'/alias'));
    }

    #[DataProvider('forceModes')]
    public function testSourceAndDestinationLinksAndTypeCollisionsAreHeld(bool $force): void
    {
        $target = $this->root.'/host';
        $external = $this->root.'/external';
        $this->put($external.'/sentinel', 'external bytes');
        $this->put($external.'/bin/workflow', 'external executable');
        chmod($external.'/bin/workflow', 0600);
        mkdir($target);
        symlink($external.'/sentinel', $target.'/AGENTS.md');
        mkdir($target.'/README.md');
        symlink($external.'/missing', $target.'/LICENSE');
        symlink($external, $target.'/.agents');
        symlink($external, $target.'/cli');
        $this->put($target.'/docs/install.md/host.txt', 'directory collision');
        $this->put($target.'/docs/as-directory', 'file collision');
        $this->put($this->kit.'/docs/as-directory/kit.txt', 'do not descend');
        $this->put($this->kit.'/docs/linked-directory/kit.txt', 'do not follow');
        symlink($external, $target.'/docs/linked-directory');
        symlink($external.'/sentinel', $this->kit.'/docs/source-file-link');
        symlink($external, $this->kit.'/docs/source-directory-link');
        symlink($external.'/missing', $this->kit.'/docs/source-dangling-link');
        symlink($external.'/sentinel', $target.'/docs/.gitignore');
        $this->put($this->kit.'/docs/destination-link', 'kit bytes');
        symlink($external.'/sentinel', $target.'/docs/destination-link');
        $this->put($this->kit.'/docs/dangling-link', 'kit bytes');
        symlink($external.'/absent', $target.'/docs/dangling-link');

        [$exit, $output] = $this->install($target, $force);
        self::assertSame(0, $exit, $output);
        foreach (['AGENTS.md', 'LICENSE', '.agents', 'cli', 'docs/.gitignore', 'docs/destination-link', 'docs/dangling-link', 'docs/linked-directory'] as $link) {
            self::assertTrue(is_link($target.'/'.$link), $link);
        }
        self::assertDirectoryExists($target.'/README.md');
        self::assertSame('directory collision', file_get_contents($target.'/docs/install.md/host.txt'));
        self::assertSame('file collision', file_get_contents($target.'/docs/as-directory'));
        foreach (['source-file-link', 'source-directory-link', 'source-dangling-link'] as $path) {
            self::assertFalse(is_link($target.'/docs/'.$path));
            self::assertFileDoesNotExist($target.'/docs/'.$path);
        }
        self::assertSame('external bytes', file_get_contents($external.'/sentinel'));
        self::assertSame(['bin', 'sentinel'], array_values(array_diff(scandir($external), ['.', '..'])));
        clearstatcache();
        self::assertSame(0600, fileperms($external.'/bin/workflow') & 0777);
    }

    public function testSourceRootLinksAreNotImported(): void
    {
        unlink($this->kit.'/AGENTS.md');
        symlink($this->kit.'/README.md', $this->kit.'/AGENTS.md');
        rename($this->kit.'/.agents', $this->kit.'/source-agents');
        symlink($this->kit.'/source-agents', $this->kit.'/.agents');
        [$exit, $output] = $this->install($this->root.'/host', true);
        self::assertSame(0, $exit, $output);
        self::assertFileDoesNotExist($this->root.'/host/AGENTS.md');
        self::assertDirectoryDoesNotExist($this->root.'/host/.agents');
    }

    #[DataProvider('forceModes')]
    public function testLinkedBinDirectoryIsNotFollowedByCopyOrChmod(bool $force): void
    {
        $external = $this->root.'/external';
        $this->put($external.'/workflow', 'host executable');
        chmod($external.'/workflow', 0600);
        mkdir($this->root.'/host/cli', 0777, true);
        symlink($external, $this->root.'/host/cli/bin');
        [$exit, $output] = $this->install($this->root.'/host', $force);
        self::assertSame(0, $exit, $output);
        self::assertTrue(is_link($this->root.'/host/cli/bin'));
        self::assertSame('host executable', file_get_contents($external.'/workflow'));
        clearstatcache();
        self::assertSame(0600, fileperms($external.'/workflow') & 0777);
        self::assertSame(['workflow'], array_values(array_diff(scandir($external), ['.', '..'])));
    }

    public static function unsafeWorkflowPaths(): array
    {
        return [
            'linked workflow' => ['.workflow', 'link'],
            'workflow file' => ['.workflow', 'file'],
            'linked items' => ['.workflow/items', 'link'],
            'linked PRD' => ['.workflow/PRD.md', 'link'],
            'linked board' => ['.workflow/board.html', 'link'],
            'directory board' => ['.workflow/board.html', 'directory'],
        ];
    }

    #[DataProvider('unsafeWorkflowPaths')]
    public function testInstallerRejectsUnsafeInitializerAndBoardDestinations(string $relative, string $type): void
    {
        $target = $this->root.'/host';
        $path = $target.'/'.$relative;
        mkdir(dirname($path), 0777, true);
        $this->put($this->root.'/external/sentinel', 'untouched');
        if ($type === 'link') {
            symlink($this->root.'/external', $path);
        } elseif ($type === 'directory') {
            mkdir($path);
        } else {
            file_put_contents($path, 'wrong type');
        }
        [$exit, $output] = $this->install($target, true);
        self::assertNotSame(0, $exit, $output);
        self::assertStringContainsString('Unsafe initializer path', $output);
        self::assertSame('untouched', file_get_contents($this->root.'/external/sentinel'));
        self::assertSame(['sentinel'], array_values(array_diff(scandir($this->root.'/external'), ['.', '..'])));
        self::assertSame($type === 'link', is_link($path));
        if ($type === 'directory') {
            self::assertDirectoryExists($path);
        } elseif ($type === 'file') {
            self::assertSame('wrong type', file_get_contents($path));
        }
    }

    /** @return array<string, string> */
    private function snapshot(): array
    {
        $paths = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $file) {
            $paths[$file->getPathname()] = $file->isLink() ? 'link:'.readlink($file->getPathname())
                : ($file->isDir() ? 'directory' : (string) file_get_contents($file->getPathname()));
        }
        ksort($paths);

        return $paths;
    }
}
