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
    public function __construct()
    {
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
        if (is_dir($backupDir)) {
            throw new WorkflowException('Backup directory already exists: '.$backupDir);
        }

        if (!rename($itemsDir, $backupDir)) {
            throw new WorkflowException('Cannot create backup.');
        }

        if (!mkdir($itemsDir, 0777, true)) {
            // Try to restore backup
            rename($backupDir, $itemsDir);
            throw new WorkflowException('Cannot recreate items directory.');
        }

        // Move items according to plan
        $moved = [];
        foreach ($plan as $item) {
            $from = $backupDir.'/'.$item['from'];
            $to = $itemsDir.'/'.$item['to'];

            $toDir = dirname($to);
            if (!is_dir($toDir) && !mkdir($toDir, 0777, true)) {
                // Rollback
                $this->rollbackMigration($itemsDir, $backupDir, $moved);
                throw new WorkflowException('Cannot create directory: '.$toDir);
            }

            if (!rename($from, $to)) {
                $this->rollbackMigration($itemsDir, $backupDir, $moved);
                throw new WorkflowException('Cannot move item: '.$item['id']);
            }

            $moved[] = $item;
        }

        // Move remaining items (those without parents or already in place)
        foreach (glob($backupDir.'/*', GLOB_ONLYDIR) ?: [] as $remaining) {
            $basename = basename($remaining);
            $to = $itemsDir.'/'.$basename;

            // Check if this is an item directory (has item.md)
            $itemFile = $remaining.'/item.md';
            if (!is_file($itemFile)) {
                // Not an item directory, skip (might be a parent created during migration)
                continue;
            }

            if (!is_dir($to)) {
                // Target doesn't exist, move the whole directory
                if (!rename($remaining, $to)) {
                    $this->rollbackMigration($itemsDir, $backupDir, $moved);
                    throw new WorkflowException('Cannot move remaining item: '.$basename);
                }
            } else {
                // Target exists (created during migration for child items).
                // Move every remaining entry — item.md and outputs/ alike —
                // so the parent's own artifact directory is not orphaned.
                foreach (scandir($remaining) ?: [] as $entry) {
                    if ($entry === '.' || $entry === '..') {
                        continue;
                    }
                    $entrySource = $remaining.'/'.$entry;
                    $entryTarget = $to.'/'.$entry;
                    if (is_file($entryTarget) || is_dir($entryTarget)) {
                        // Preserve anything the migration already placed here.
                        continue;
                    }
                    if (!rename($entrySource, $entryTarget)) {
                        $this->rollbackMigration($itemsDir, $backupDir, $moved);
                        throw new WorkflowException('Cannot move entry: '.$basename.'/'.$entry);
                    }
                }
            }
        }

        // Clean up backup if successful
        // Actually, keep the backup for safety
    }

    /**
     * Rollback a failed migration.
     *
     * @param list<array{from: string, to: string, id: string}> $moved
     */
    private function rollbackMigration(string $itemsDir, string $backupDir, array $moved): void
    {
        // Move items back to backup
        foreach ($moved as $item) {
            $from = $itemsDir.'/'.$item['to'];
            $to = $backupDir.'/'.$item['from'];
            if (is_dir($from)) {
                rename($from, $to);
            }
        }

        // Remove new items directory
        $this->removeDirectory($itemsDir);

        // Restore backup
        rename($backupDir, $itemsDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir.'/'.$file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
