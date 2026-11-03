<?php

declare(strict_types=1);

namespace Workflow\Tests\Console;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workflow\Console\Application;
use Workflow\Store\ItemStore;
use Workflow\Tests\WorkflowTestCase;
use Workflow\Validate\ItemValidator;

final class MigrateCommandTest extends WorkflowTestCase
{
    private ItemStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        self::assertSame(0, $this->runCli(['command' => 'init', '--name' => 'migrate'])[0]);
        $this->store = new ItemStore($this->root);
    }

    /** @return array{int, string} */
    private function runCli(array $input): array
    {
        $application = new Application();
        $application->setAutoExit(false);
        $output = new BufferedOutput();
        $exit = $application->run(new ArrayInput(['--project' => $this->root] + $input), $output);

        return [$exit, $output->fetch()];
    }

    /**
     * Regression: the migration previously moved only an epic's item.md when
     * the epic's nested directory had already been created by child moves,
     * orphaning `outputs/` in the backup. Now every remaining entry in a
     * pre-existing parent directory is relocated.
     */
    public function testEpicOutputsTravelWithTheEpic(): void
    {
        // Build a flat layout by hand (workflow new creates items at the
        // top level; `migrate` is what puts them nested).
        $this->runCli(['command' => 'new', 'title' => 'Epic', '--kind' => 'epic']);
        $this->runCli(['command' => 'new', 'title' => 'Task', '--parent' => 'E-001']);
        $outputs = $this->store->itemsDir().'/E-001/outputs';
        mkdir($outputs, 0777, true);
        file_put_contents($outputs.'/evidence.md', 'epic evidence');

        [$exit] = $this->runCli(['command' => 'migrate', '--force' => true]);
        self::assertSame(0, $exit);

        self::assertFileExists($outputs.'/evidence.md');
        self::assertDirectoryDoesNotExist($this->store->itemsDir().'.backup/E-001/outputs');
    }

    public function testValidatePassesOnMigratedLayout(): void
    {
        $this->runCli(['command' => 'new', 'title' => 'Epic', '--kind' => 'epic']);
        $this->runCli(['command' => 'new', 'title' => 'Task', '--parent' => 'E-001']);

        [$exit] = $this->runCli(['command' => 'migrate', '--force' => true]);
        self::assertSame(0, $exit);

        $result = (new ItemValidator($this->store))->validate();
        $errors = implode("\n", $result->errors);

        self::assertStringNotContainsString('unreadable', $errors);
        self::assertStringNotContainsString('unexpected entry', $errors);
        self::assertStringNotContainsString('item.md is missing', $errors);
    }
}
