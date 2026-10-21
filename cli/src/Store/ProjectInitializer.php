<?php

declare(strict_types=1);

namespace Workflow\Store;

use Workflow\Domain\WorkflowException;

final class ProjectInitializer
{
    public function __construct(private readonly string $templatesDir)
    {
    }

    /**
     * @return list<string> created paths, relative to the project root
     */
    public function initialize(string $root, ?string $name = null): array
    {
        $root = rtrim($root, '/') ?: '/';
        $name ??= basename($root);
        $workflow = rtrim($root, '/').'/.workflow';
        $created = [];

        // Check all managed destinations before creating anything. In
        // particular, init's subsequent board refresh must not replace a link.
        $directories = [$root, $workflow, $workflow.'/items'];
        foreach ($directories as $directory) {
            $this->assertPath($directory, true);
        }
        foreach (['PRD.md', 'Architecture.md', '.gitignore', 'board.html'] as $file) {
            $this->assertPath($workflow.'/'.$file, false);
        }
        foreach (['PRD.md', 'Architecture.md'] as $file) {
            if (!is_file($workflow.'/'.$file)) {
                $template = $this->templatesDir.'/'.$file;
                $this->assertPath($template, false);
                if (!is_file($template)) {
                    throw new WorkflowException(sprintf('Missing template: %s', $template));
                }
            }
        }

        foreach ([$workflow, $workflow.'/items'] as $directory) {
            if (!is_dir($directory)) {
                if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
                    throw new WorkflowException(sprintf('Cannot create directory: %s', $directory));
                }
                $created[] = substr($directory, strlen(rtrim($root, '/')) + 1).'/';
            }
        }

        foreach (['PRD.md', 'Architecture.md'] as $file) {
            $target = $workflow.'/'.$file;
            if (is_file($target)) {
                continue;
            }

            $templatePath = $this->templatesDir.'/'.$file;
            if (!is_file($templatePath)) {
                throw new WorkflowException(sprintf('Missing template: %s', $templatePath));
            }

            $contents = str_replace(
                ['{{PROJECT}}', '{{DATE}}'],
                [$name, gmdate('Y-m-d')],
                (string) file_get_contents($templatePath),
            );
            if (file_put_contents($target, $contents) === false) {
                throw new WorkflowException(sprintf('Cannot write template: %s', $target));
            }
            $created[] = '.workflow/'.$file;
        }

        $gitignore = $workflow.'/.gitignore';
        if (!is_file($gitignore)) {
            if (file_put_contents($gitignore, ".locks/\nboard.html\n*.tmp\n") === false) {
                throw new WorkflowException(sprintf('Cannot write gitignore: %s', $gitignore));
            }
            $created[] = '.workflow/.gitignore';
        }

        return $created;
    }

    private function assertPath(string $path, bool $directory): void
    {
        $parent = dirname($path);
        if ($parent !== $path) {
            $this->assertPath($parent, true);
        }

        if (is_link($path) || (file_exists($path) && !($directory ? is_dir($path) : is_file($path)))) {
            throw new WorkflowException(sprintf('Unsafe initializer path (symlink or wrong type): %s', $path));
        }
    }
}
