<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
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
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite existing files.');
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

        $existing = realpath($target);
        $targetPath = $existing !== false ? $existing : $this->normalize($target);

        if ($targetPath === $kitRoot) {
            $output->writeln('<error>Target is the kit root; choose another directory.</error>');

            return self::INVALID;
        }

        if (str_starts_with($targetPath.'/', $kitRoot.'/')) {
            $output->writeln('<error>Target must be outside the kit root.</error>');

            return self::INVALID;
        }

        if (str_starts_with($kitRoot.'/', $targetPath.'/')) {
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
            if (!is_file($source)) {
                continue;
            }
            $this->copyFile($source, $targetPath.'/'.$file, $force, $copied, $skipped);
        }

        foreach (['.agents', 'docs', 'cli'] as $directory) {
            $source = $kitRoot.'/'.$directory;
            if (is_dir($source)) {
                $this->copyTree($source, $targetPath.'/'.$directory, $force, $copied, $skipped);
            }
        }

        $initializer = new ProjectInitializer($kitRoot.'/cli/templates');
        $created = $initializer->initialize($targetPath, $input->getOption('name'));

        $output->writeln(sprintf('Installed into %s', $targetPath));
        $output->writeln(sprintf('  copied %d files, skipped %d existing files', count($copied), count($skipped)));
        foreach ($created as $path) {
            $output->writeln('  created '.$path);
        }

        $workflowBin = $targetPath.'/cli/bin/workflow';
        if (is_file($workflowBin)) {
            @chmod($workflowBin, 0755);
        }

        $output->writeln('');
        $output->writeln('Next:');
        $output->writeln('  cd '.$targetPath.'/cli && composer install');
        $output->writeln('  '.$targetPath.'/cli/bin/workflow --project '.$targetPath.' validate');

        return self::SUCCESS;
    }

    private function copyTree(string $source, string $target, bool $force, array &$copied, array &$skipped): void
    {
        $source = rtrim($source, '/');
        $target = rtrim($target, '/');

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
                function (\SplFileInfo $file) use ($source): bool {
                    $relative = ltrim(substr($file->getPathname(), strlen($source)), '/');
                    if (rtrim($relative, '/') === '.workflow' || str_starts_with($relative, '.workflow/')) {
                        return false;
                    }
                    foreach (self::SKIP as $skip) {
                        if ($relative === $skip || str_starts_with($relative, $skip.'/')) {
                            return false;
                        }
                    }

                    return true;
                },
            ),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            $relative = ltrim(substr($file->getPathname(), strlen($source)), '/');
            $destination = $target.'/'.$relative;

            if ($file->isDir()) {
                if (!is_dir($destination) && !mkdir($destination, 0777, true) && !is_dir($destination)) {
                    throw new \RuntimeException(sprintf('Cannot create directory: %s', $destination));
                }
                continue;
            }

            $this->copyFile($file->getPathname(), $destination, $force, $copied, $skipped);
        }
    }

    private function copyFile(string $source, string $destination, bool $force, array &$copied, array &$skipped): void
    {
        if (is_file($destination) && !$force) {
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

    private function normalize(string $path): string
    {
        $parts = [];
        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $part;
        }

        return '/'.implode('/', $parts);
    }
}
