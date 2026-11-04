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
        $child = rtrim($base, '/').'/workflow-test-'.bin2hex(random_bytes(6));
        if (!@mkdir($child, 0777, true)) {
            throw new \RuntimeException('Failed to create test root: '.$child);
        }
        $resolved = realpath($child);
        if ($resolved === false) {
            @rmdir($child);
            throw new \RuntimeException('Failed to resolve test root: '.$child);
        }
        $this->root = $resolved;
    }

    protected function tearDown(): void
    {
        if (!isset($this->root) || !is_dir($this->root)) {
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
