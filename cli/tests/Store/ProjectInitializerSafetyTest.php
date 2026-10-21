<?php

declare(strict_types=1);

namespace Workflow\Tests\Store;

use PHPUnit\Framework\Attributes\DataProvider;
use Workflow\Domain\WorkflowException;
use Workflow\Store\ProjectInitializer;
use Workflow\Tests\WorkflowTestCase;

final class ProjectInitializerSafetyTest extends WorkflowTestCase
{
    private string $templates;

    protected function setUp(): void
    {
        $this->root = '/tmp/opencode/initializer-test-'.bin2hex(random_bytes(6));
        mkdir($this->root.'/templates', 0777, true);
        $this->templates = $this->root.'/templates';
        file_put_contents($this->templates.'/PRD.md', '# {{PROJECT}} {{DATE}}');
        file_put_contents($this->templates.'/Architecture.md', '# {{PROJECT}} architecture');
        mkdir($this->root.'/host');
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

    public function testFreshInitializationAndRepeatPreserveExistingRecordsAndBoard(): void
    {
        $initializer = new ProjectInitializer($this->templates);
        $host = $this->root.'/host';
        self::assertSame(
            ['.workflow/', '.workflow/items/', '.workflow/PRD.md', '.workflow/Architecture.md', '.workflow/.gitignore'],
            $initializer->initialize($host, 'Host'),
        );
        self::assertSame('# Host '.gmdate('Y-m-d'), file_get_contents($host.'/.workflow/PRD.md'));
        self::assertSame('# Host architecture', file_get_contents($host.'/.workflow/Architecture.md'));
        self::assertSame(".locks/\nboard.html\n*.tmp\n", file_get_contents($host.'/.workflow/.gitignore'));
        mkdir($host.'/.workflow/items/E-001/outputs', 0777, true);
        $paths = [
            'PRD.md', 'Architecture.md', '.gitignore', 'board.html',
            'items/E-001/item.md', 'items/E-001/outputs/report.md',
        ];
        foreach ($paths as $path) {
            file_put_contents($host.'/.workflow/'.$path, "host $path\0\n");
        }
        self::assertSame([], $initializer->initialize($host, 'Different name'));
        foreach ($paths as $path) {
            self::assertSame("host $path\0\n", file_get_contents($host.'/.workflow/'.$path));
        }
    }

    public static function collisions(): array
    {
        $cases = [];
        foreach (['.workflow', '.workflow/items', '.workflow/PRD.md', '.workflow/Architecture.md', '.workflow/.gitignore', '.workflow/board.html'] as $path) {
            foreach (['link', 'dangling', 'wrong-type'] as $type) {
                $cases[$path.' '.$type] = [$path, $type];
            }
        }

        return $cases;
    }

    #[DataProvider('collisions')]
    public function testUnsafePathsAreRejectedBeforeInitializationWrites(string $relative, string $type): void
    {
        $host = $this->root.'/host';
        $path = $host.'/'.$relative;
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        mkdir($this->root.'/external');
        file_put_contents($this->root.'/external/sentinel', 'external bytes');
        $directory = in_array($relative, ['.workflow', '.workflow/items'], true);
        if ($type === 'link' || $type === 'dangling') {
            symlink($this->root.($type === 'link' ? '/external' : '/missing'), $path);
        } elseif ($directory) {
            file_put_contents($path, 'wrong type');
        } else {
            mkdir($path);
        }
        $before = $this->entries($host);
        try {
            (new ProjectInitializer($this->templates))->initialize($host);
            self::fail('Unsafe initializer path was accepted: '.$relative);
        } catch (WorkflowException $exception) {
            self::assertStringContainsString('Unsafe initializer path', $exception->getMessage());
        }
        self::assertSame($before, $this->entries($host));
        self::assertSame('external bytes', file_get_contents($this->root.'/external/sentinel'));
        self::assertSame(['sentinel'], array_values(array_diff(scandir($this->root.'/external'), ['.', '..'])));
        if ($type !== 'wrong-type') {
            self::assertTrue(is_link($path));
        }
    }

    public static function linkedSources(): array
    {
        return [
            'template file' => ['PRD.md'],
            'template directory' => ['templates'],
            'template ancestor' => ['ancestor'],
        ];
    }

    #[DataProvider('linkedSources')]
    public function testLinkedTemplateSourcesAreRejectedBeforeWrites(string $component): void
    {
        if ($component === 'PRD.md') {
            rename($this->templates.'/PRD.md', $this->templates.'/real.md');
            symlink($this->templates.'/real.md', $this->templates.'/PRD.md');
        } elseif ($component === 'templates') {
            rename($this->templates, $this->root.'/real-templates');
            symlink($this->root.'/real-templates', $this->templates);
        } else {
            symlink($this->root, $this->root.'/alias');
            $this->templates = $this->root.'/alias/templates';
        }
        try {
            (new ProjectInitializer($this->templates))->initialize($this->root.'/host');
            self::fail('Linked source was accepted.');
        } catch (WorkflowException $exception) {
            self::assertStringContainsString('Unsafe initializer path', $exception->getMessage());
        }
        self::assertDirectoryDoesNotExist($this->root.'/host/.workflow');
    }

    public function testMissingTemplateFailsBeforeCreatingRecords(): void
    {
        unlink($this->templates.'/Architecture.md');
        try {
            (new ProjectInitializer($this->templates))->initialize($this->root.'/host');
            self::fail('Missing template was accepted.');
        } catch (WorkflowException $exception) {
            self::assertStringContainsString('Missing template', $exception->getMessage());
        }
        self::assertDirectoryDoesNotExist($this->root.'/host/.workflow');
    }

    public function testLinkedProjectAncestorIsRejected(): void
    {
        symlink($this->root.'/host', $this->root.'/alias');
        try {
            (new ProjectInitializer($this->templates))->initialize($this->root.'/alias/child');
            self::fail('Linked ancestor was accepted.');
        } catch (WorkflowException $exception) {
            self::assertStringContainsString('Unsafe initializer path', $exception->getMessage());
        }
        self::assertDirectoryDoesNotExist($this->root.'/host/child');
    }

    /** @return array<string, string> */
    private function entries(string $root): array
    {
        $entries = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $file) {
            $entries[$file->getPathname()] = $file->isLink() ? 'link:'.readlink($file->getPathname())
                : ($file->isDir() ? 'directory' : (string) file_get_contents($file->getPathname()));
        }
        ksort($entries);

        return $entries;
    }
}
