<?php

declare(strict_types=1);

namespace Workflow\Tests;

use PHPUnit\Framework\TestCase;

abstract class WorkflowTestCase extends TestCase
{
    protected string $root;

    protected function setUp(): void
    {
        $base = getenv('WORKFLOW_TEST_ROOT') ?: sys_get_temp_dir();
        $this->root = rtrim($base, '/').'/workflow-test-'.bin2hex(random_bytes(6));
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->root)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }

        @rmdir($this->root);
    }
}
