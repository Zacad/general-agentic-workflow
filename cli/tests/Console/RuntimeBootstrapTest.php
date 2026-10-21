<?php

declare(strict_types=1);

namespace Workflow\Tests\Console;

use Workflow\Tests\WorkflowTestCase;

final class RuntimeBootstrapTest extends WorkflowTestCase
{
    public function testPhpFloorMatchesLockedPlatform(): void
    {
        $directory = dirname(__DIR__, 2);
        $composer = json_decode(file_get_contents($directory.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $lock = json_decode(file_get_contents($directory.'/composer.lock'), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame('>=8.4.1', $composer['require']['php']);
        self::assertSame($composer['require']['php'], $lock['platform']['php']);
    }

    public function testLockedRuntimeProvidesMbstringWithIconv(): void
    {
        $directory = dirname(__DIR__, 2);
        $composer = json_decode(file_get_contents($directory.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $lock = json_decode(file_get_contents($directory.'/composer.lock'), true, flags: JSON_THROW_ON_ERROR);
        $packages = array_column($lock['packages'], null, 'name');

        self::assertArrayNotHasKey('ext-mbstring', $composer['require']);
        self::assertSame('*', $packages['symfony/polyfill-mbstring']['require']['ext-iconv']);
        self::assertSame('*', $packages['symfony/polyfill-mbstring']['provide']['ext-mbstring']);
    }

    public function testMissingAutoloadExplainsRuntimeAndDevelopmentSetup(): void
    {
        $directory = $this->copyBootstrap();
        [$exit, $stdout, $stderr] = $this->runBootstrap($directory);

        self::assertSame(1, $exit);
        self::assertSame('', $stdout);
        self::assertStringContainsString('Workflow dependencies are not installed.', $stderr);
        self::assertStringContainsString('Runtime prerequisites: PHP >=8.4.1, Composer 2 and iconv (mbstring is polyfilled).', $stderr);
        self::assertStringContainsString('Runtime setup: composer install --no-dev --working-dir='.escapeshellarg($directory), $stderr);
        self::assertStringContainsString('Development also requires native mbstring, DOM, filter, JSON, libxml, tokenizer, XMLWriter and Phar.', $stderr);
        self::assertStringContainsString('Development setup: composer install --working-dir='.escapeshellarg($directory), $stderr);
    }

    public function testExistingAutoloaderControlsApplicationBootstrap(): void
    {
        $directory = $this->copyBootstrap();
        mkdir($directory.'/vendor');
        file_put_contents($directory.'/vendor/autoload.php', <<<'PHP'
<?php

namespace Workflow\Console;

final class Application
{
    public function run(): int
    {
        fwrite(STDOUT, "Isolated autoloader used.\n");

        return 23;
    }
}
PHP);

        [$exit, $stdout, $stderr] = $this->runBootstrap($directory);

        self::assertSame(23, $exit);
        self::assertSame("Isolated autoloader used.\n", $stdout);
        self::assertSame('', $stderr);
    }

    private function copyBootstrap(): string
    {
        $directory = $this->root."/runtime kit's cli";
        mkdir($directory.'/bin', 0777, true);
        copy(dirname(__DIR__, 2).'/bin/workflow', $directory.'/bin/workflow');

        return $directory;
    }

    /**
     * @return array{0: int, 1: string, 2: string}
     */
    private function runBootstrap(string $directory): array
    {
        $process = proc_open([PHP_BINARY, $directory.'/bin/workflow'], [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, $this->root);
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $stdout, $stderr];
    }
}
