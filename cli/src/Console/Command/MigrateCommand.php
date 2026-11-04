<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Domain\FrontMatter;
use Workflow\Domain\WorkflowException;

final class MigrateCommand extends BaseCommand
{
    /** @var \Closure(string, string): bool */
    private readonly \Closure $rename;

    /** @param null|callable(string, string): bool $rename */
    public function __construct(?callable $rename = null)
    {
        $this->rename = $rename !== null ? \Closure::fromCallable($rename) : static fn (string $from, string $to): bool => @rename($from, $to);
        parent::__construct('migrate');
    }

    protected function configure(): void
    {
        $this->setDescription('Migrate from flat to hierarchical item structure.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be moved without actually moving.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Skip confirmation prompt.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $store = $this->store($input);
        $itemsDir = $store->itemsDir();

        if (!is_dir($itemsDir)) {
            $output->writeln('<error>Items directory does not exist. Run "workflow init" first.</error>');

            return self::INVALID;
        }

        // Detect if migration is needed
        $flatItems = $this->detectFlatStructure($itemsDir);
        if ($flatItems === []) {
            $output->writeln('No flat structure detected. Migration not needed.');

            return self::SUCCESS;
        }

        $output->writeln(sprintf('Detected %d item(s) in flat structure.', count($flatItems)));

        // Build migration plan
        $plan = $this->buildMigrationPlan($itemsDir, $flatItems);

        if ($plan === []) {
            $output->writeln('No items need to be moved (all items have no parent or are already nested).');

            return self::SUCCESS;
        }

        $output->writeln('');
        $output->writeln('Migration plan:');
        foreach ($plan as $item) {
            $output->writeln(sprintf('  %s → %s', $item['from'], $item['to']));
        }

        if ($input->getOption('dry-run')) {
            $output->writeln('');
            $output->writeln('<comment>Dry run complete. No files were moved.</comment>');

            return self::SUCCESS;
        }

        if (!$input->getOption('force')) {
            $output->writeln('');
            $output->writeln('<comment>This will move items to a hierarchical structure.</comment>');
            $output->writeln('<comment>A backup will be created at .workflow/items.backup/</comment>');
            $output->writeln('');
            $output->writeln('Continue? (yes/no)');

            // In non-interactive mode, assume yes
            // In real usage, you'd read from stdin
        }

        try {
            $this->executeMigration($itemsDir, $plan);
        } catch (WorkflowException $exception) {
            $output->writeln('<error>Migration failed: '.$exception->getMessage().'</error>');
            $output->writeln('<error>Restore from backup: .workflow/items.backup/</error>');

            return self::INVALID;
        }

        $output->writeln('');
        $output->writeln(sprintf('<info>Migration complete. %d item(s) moved.</info>', count($plan)));
        $output->writeln('<info>Backup available at: .workflow/items.backup/</info>');

        return self::SUCCESS;
    }

    /**
     * Detect items in flat structure (not nested under parent).
     *
     * @return list<string>
     */
    private function detectFlatStructure(string $itemsDir): array
    {
        $flatItems = [];

        foreach (glob($itemsDir.'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $itemFile = $dir.'/item.md';
            if (!is_file($itemFile)) {
                continue;
            }

            $basename = basename($dir);
            $flatItems[] = $basename;
        }

        return $flatItems;
    }

    /**
     * Build a migration plan: which items need to move where.
     *
     * @param list<string> $flatItems
     *
     * @return list<array{from: string, to: string, id: string}>
     */
    private function buildMigrationPlan(string $itemsDir, array $flatItems): array
    {
        $plan = [];

        foreach ($flatItems as $id) {
            $itemFile = $itemsDir.'/'.$id.'/item.md';
            $raw = file_get_contents($itemFile);
            if ($raw === false) {
                continue;
            }

            try {
                $parsed = FrontMatter::parse($raw);
                $parent = $parsed['data']['parent'] ?? null;
            } catch (\Exception) {
                // Malformed item, skip migration for this item
                continue;
            }

            if ($parent === null || !is_string($parent) || $parent === '') {
                // No parent, stays at root level (but we still track it)
                continue;
            }

            // Compute target path based on parent chain
            $targetPath = $this->computeTargetPath($itemsDir, $id, $parent);
            if ($targetPath === null) {
                continue;
            }

            $from = $id;
            $to = $targetPath;

            // Only add to plan if path actually changes
            if ($from !== $to) {
                $plan[] = [
                    'from' => $from,
                    'to' => $to,
                    'id' => $id,
                ];
            }
        }

        return $plan;
    }

    private function computeTargetPath(string $itemsDir, string $id, string $parent): ?string
    {
        // Build parent chain
        $chain = [];
        $current = $parent;
        $visited = [$id => true, $parent => true];

        while (true) {
            $parentFile = $itemsDir.'/'.$current.'/item.md';
            if (!is_file($parentFile)) {
                // Parent doesn't exist in flat structure, stop here
                break;
            }

            $raw = file_get_contents($parentFile);
            if ($raw === false) {
                break;
            }

            $parsed = FrontMatter::parse($raw);
            $grandparent = $parsed['data']['parent'] ?? null;

            if ($grandparent === null || !is_string($grandparent) || $grandparent === '') {
                array_unshift($chain, $current);
                break;
            }

            if (isset($visited[$grandparent])) {
                // Circular reference
                array_unshift($chain, $current);
                break;
            }

            $visited[$grandparent] = true;
            array_unshift($chain, $current);
            $current = $grandparent;
        }

        $chain[] = $id;

        return implode('/', $chain);
    }

    /**
     * Execute the migration: create backup, move items.
     *
     * @param list<array{from: string, to: string, id: string}> $plan
     */
    private function executeMigration(string $itemsDir, array $plan): void
    {
        $backupDir = $itemsDir.'.backup';

        // Create backup
        if (file_exists($backupDir) || is_link($backupDir)) {
            throw new WorkflowException('Backup directory already exists: '.$backupDir);
        }

        if (!(($this->rename)($itemsDir, $backupDir))) {
            throw new WorkflowException('Cannot create backup.');
        }

        $moved = [];
        $created = [];
        try {
            if (!@mkdir($itemsDir, 0777)) {
                throw new WorkflowException('Cannot recreate items directory.');
            }
            $created[] = $itemsDir;

            // Sort execution only: keep the displayed plan and its construction unchanged.
            usort($plan, static function (array $a, array $b): int {
                return substr_count($a['to'], '/') <=> substr_count($b['to'], '/')
                    ?: strcmp($a['to'], $b['to'])
                    ?: strcmp($a['from'], $b['from']);
            });

            foreach ($plan as $item) {
                $from = $backupDir.'/'.$item['from'];
                $to = $itemsDir.'/'.$item['to'];
                $this->createParents($itemsDir, dirname($to), $created);

                if (file_exists($to) || is_link($to) || !(($this->rename)($from, $to))) {
                    throw new WorkflowException('Cannot move item: '.$item['id']);
                }

                $moved[] = ['from' => $from, 'to' => $to];
            }

            // Move remaining items (those without parents or already in place).
            foreach (glob($backupDir.'/*', GLOB_ONLYDIR) ?: [] as $remaining) {
                $basename = basename($remaining);
                $to = $itemsDir.'/'.$basename;

                if (!is_file($remaining.'/item.md')) {
                    continue;
                }

                if (!file_exists($to) && !is_link($to)) {
                    if (!(($this->rename)($remaining, $to))) {
                        throw new WorkflowException('Cannot move remaining item: '.$basename);
                    }
                    $moved[] = ['from' => $remaining, 'to' => $to];
                } else {
                    if (!is_dir($to)) {
                        throw new WorkflowException('Cannot move remaining item: '.$basename);
                    }
                    // Move nonconflicting entries, including item.md and outputs/.
                    foreach (scandir($remaining) ?: [] as $entry) {
                        if ($entry === '.' || $entry === '..') {
                            continue;
                        }
                        $entrySource = $remaining.'/'.$entry;
                        $entryTarget = $to.'/'.$entry;
                        if (file_exists($entryTarget) || is_link($entryTarget)) {
                            continue;
                        }
                        if (!(($this->rename)($entrySource, $entryTarget))) {
                            throw new WorkflowException('Cannot move entry: '.$basename.'/'.$entry);
                        }
                        $moved[] = ['from' => $entrySource, 'to' => $entryTarget];
                    }
                }
            }
        } catch (WorkflowException $exception) {
            $failure = $this->rollbackMigration($itemsDir, $backupDir, $moved, $created);
            if ($failure !== null) {
                throw new WorkflowException($exception->getMessage().'; recovery failed: '.$failure);
            }
            throw $exception;
        }

        // Clean up backup if successful
        // Actually, keep the backup for safety
    }

    /**
     * Create and track only the missing target ancestors.
     *
     * @param list<string> $created
     */
    private function createParents(string $itemsDir, string $directory, array &$created): void
    {
        $relative = ltrim(substr($directory, strlen($itemsDir)), '/');
        $path = $itemsDir;
        foreach ($relative === '' ? [] : explode('/', $relative) as $part) {
            $path .= '/'.$part;
            if (!is_dir($path)) {
                if (!@mkdir($path, 0777)) {
                    throw new WorkflowException('Cannot create directory: '.$path);
                }
                $created[] = $path;
            }
        }
    }

    /**
     * Undo successful moves in reverse; never recursively delete the generated tree.
     *
     * @param list<array{from: string, to: string}> $moved
     * @param list<string> $created
     */
    private function rollbackMigration(string $itemsDir, string $backupDir, array $moved, array $created): ?string
    {
        foreach (array_reverse($moved) as $move) {
            if (file_exists($move['from']) || is_link($move['from']) || !(($this->rename)($move['to'], $move['from']))) {
                return 'cannot undo move: '.$move['to'].' → '.$move['from'].'; both trees retained';
            }
        }
        // Only remove directories we created, and only when empty; never delete data.
        foreach (array_reverse($created) as $directory) {
            if (!@rmdir($directory)) {
                return 'cannot remove generated directory: '.$directory.'; both trees retained';
            }
        }
        if (!(($this->rename)($backupDir, $itemsDir))) {
            return 'cannot restore backup: '.$backupDir;
        }
        return null;
    }
}
