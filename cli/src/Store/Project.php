<?php

declare(strict_types=1);

namespace Workflow\Store;

use Workflow\Domain\WorkflowException;

final class Project
{
    public static function find(?string $explicit = null): string
    {
        if ($explicit !== null && $explicit !== '') {
            $root = realpath($explicit);
            if ($root === false || !is_dir($root)) {
                throw new WorkflowException(sprintf('Project path does not exist: %s', $explicit));
            }

            return $root;
        }

        $directory = getcwd();
        if ($directory === false) {
            throw new WorkflowException('Cannot determine the current directory.');
        }

        while (true) {
            if (is_dir($directory.'/.workflow')) {
                return $directory;
            }

            $parent = dirname($directory);
            if ($parent === $directory) {
                break;
            }
            $directory = $parent;
        }

        return getcwd() ?: '.';
    }

    public static function require(?string $explicit = null): string
    {
        $root = self::find($explicit);
        if (!is_dir($root.'/.workflow')) {
            throw new WorkflowException('No .workflow directory found here. Run "workflow init" first or pass --project.');
        }

        return $root;
    }
}
