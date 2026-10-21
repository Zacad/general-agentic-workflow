<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Board\BoardWriter;
use Workflow\Store\ItemStore;
use Workflow\Store\ProjectInitializer;

final class InstallCommand extends BaseCommand
{
    private const SKIP = [
        '.git',
        '.workflow',
        'vendor',
        'tools',
        'var',
        '.phpunit.cache',
    ];

    public function __construct()
    {
        parent::__construct('install');
    }

    protected function configure(): void
    {
        $this->setDescription('Copy the workflow kit into a target project and initialize it.')
            ->addArgument('target', InputArgument::REQUIRED, 'Target project directory.')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Project name for the templates.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Refresh ordinary kit files; preserve root documents and workflow records.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $kitRoot = realpath(dirname(__DIR__, 4));
        if ($kitRoot === false) {
            $output->writeln('<error>Cannot locate the kit root.</error>');

            return self::FAILURE;
        }

        $target = (string) $input->getArgument('target');
        if (!str_starts_with($target, '/')) {
            $target = (getcwd() ?: '.').'/'.$target;
        }

        try {
            $targetPath = $this->resolveTarget($target);
        } catch (\RuntimeException $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');

            return self::INVALID;
        }

        if ($targetPath === $kitRoot) {
            $output->writeln('<error>Target is the kit root; choose another directory.</error>');

            return self::INVALID;
        }

        if ($this->contains($kitRoot, $targetPath)) {
            $output->writeln('<error>Target must be outside the kit root.</error>');

            return self::INVALID;
        }

        if ($this->contains($targetPath, $kitRoot)) {
            $output->writeln('<error>Target cannot be a parent of the kit root.</error>');

            return self::INVALID;
        }

        if (!is_dir($targetPath) && !mkdir($targetPath, 0777, true) && !is_dir($targetPath)) {
            $output->writeln(sprintf('<error>Cannot create target: %s</error>', $targetPath));

            return self::FAILURE;
        }

        $force = (bool) $input->getOption('force');
        $copied = [];
        $skipped = [];

        foreach (['AGENTS.md', 'README.md', 'LICENSE'] as $file) {
            $source = $kitRoot.'/'.$file;
            $this->copyFile($source, $targetPath.'/'.$file, false, $copied, $skipped);
        }

        foreach (['.agents', 'docs', 'cli'] as $directory) {
            $source = $kitRoot.'/'.$directory;
            if (is_link($source) || is_dir($source)) {
                $this->copyTree($source, $targetPath.'/'.$directory, $force, $copied, $skipped);
            }
        }

        $initializer = new ProjectInitializer($kitRoot.'/cli/templates');
        $created = $initializer->initialize($targetPath, $input->getOption('name'));

        $output->writeln(sprintf('Installed into %s', $targetPath));
        $output->writeln(sprintf('  copied %d files, skipped %d existing files or unsafe paths', count($copied), count($skipped)));
        foreach ($created as $path) {
            $output->writeln('  created '.$path);
        }

        try {
            $board = (new BoardWriter())->write(new ItemStore($targetPath));
            $output->writeln('  created '.$board);
        } catch (\Throwable $exception) {
            $output->writeln('<comment>warning: board not created: '.$exception->getMessage().'</comment>');
        }

        $workflowBin = $targetPath.'/cli/bin/workflow';
        if (in_array($workflowBin, $copied, true)) {
            @chmod($workflowBin, 0755);
        }

        $output->writeln('');
        $output->writeln('Next:');
        $output->writeln('  Scaffold only: review skipped paths; no automatic merges or deletions.');
        $output->writeln('  Manually merge the kit AGENTS.md rules into preserved host instructions.');
        $output->writeln('  Existing root documents, host configuration and workflow records are never merged or replaced.');
        $output->writeln('  cd '.$targetPath.'/cli && composer install --no-dev');
        $output->writeln('  '.$targetPath.'/cli/bin/workflow --project '.$targetPath.' validate');

        return self::SUCCESS;
    }

    private function copyTree(string $source, string $target, bool $force, array &$copied, array &$skipped): void
    {
        if (is_link($source) || is_link($target) || (file_exists($target) && !is_dir($target))) {
            $skipped[] = $target;

            return;
        }

        if (!is_dir($target) && !mkdir($target, 0777, true) && !is_dir($target)) {
            throw new \RuntimeException(sprintf('Cannot create directory: %s', $target));
        }

        foreach (new \FilesystemIterator($source, \FilesystemIterator::SKIP_DOTS) as $file) {
            /** @var \SplFileInfo $file */
            if (in_array($file->getFilename(), self::SKIP, true)) {
                continue;
            }

            $destination = $target.'/'.$file->getFilename();
            if (!$file->isLink() && $file->isDir()) {
                $this->copyTree($file->getPathname(), $destination, $force, $copied, $skipped);
            } else {
                $this->copyFile($file->getPathname(), $destination, $force, $copied, $skipped);
            }
        }
    }

    private function copyFile(string $source, string $destination, bool $force, array &$copied, array &$skipped): void
    {
        $force = $force && basename($destination) !== '.gitignore';
        if (is_link($source) || !is_file($source) || is_link($destination)
            || (file_exists($destination) && (!is_file($destination) || !$force))) {
            $skipped[] = $destination;
            return;
        }

        $directory = dirname($destination);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Cannot create directory: %s', $directory));
        }

        if (!copy($source, $destination)) {
            throw new \RuntimeException(sprintf('Cannot copy %s to %s', $source, $destination));
        }

        $copied[] = $destination;
    }

    private function contains(string $parent, string $path): bool
    {
        return $path === $parent || str_starts_with($path, rtrim($parent, '/').'/');
    }

    private function resolveTarget(string $path): string
    {
        $resolved = '/';
        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                $resolved = dirname($resolved);
                continue;
            }

            $candidate = rtrim($resolved, '/').'/'.$part;
            if (is_link($candidate) || file_exists($candidate)) {
                // Resolve this component before a following '..': a symlink's
                // physical parent is not necessarily its lexical parent.
                $existing = realpath($candidate);
                if ($existing === false || !is_dir($existing)) {
                    throw new \RuntimeException(sprintf('Target component is dangling or not a directory: %s', $candidate));
                }
                $resolved = $existing;
            } else {
                $resolved = $candidate;
            }
        }

        return $resolved;
    }
}
