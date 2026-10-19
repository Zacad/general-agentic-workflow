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
        $root = rtrim($root, '/');
        $name ??= basename($root);
        $workflow = $root.'/.workflow';
        $created = [];

        foreach ([$workflow, $workflow.'/items'] as $directory) {
            if (!is_dir($directory)) {
                if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
                    throw new WorkflowException(sprintf('Cannot create directory: %s', $directory));
                }
                $created[] = substr($directory, strlen($root) + 1).'/';
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
            file_put_contents($target, $contents);
            $created[] = '.workflow/'.$file;
        }

        $gitignore = $workflow.'/.gitignore';
        if (!is_file($gitignore)) {
            file_put_contents($gitignore, ".locks/\nboard.html\n*.tmp\n");
            $created[] = '.workflow/.gitignore';
        }

        return $created;
    }
}
