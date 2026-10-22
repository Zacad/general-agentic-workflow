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

        $output->writeln(sprintf('Scaffold copied into %s', $targetPath));
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

        $this->registerOpenCodeAgents($kitRoot, $targetPath, $output);

        $output->writeln('');
        $output->writeln('Next steps (manual):');
        $output->writeln('  This is a scaffold copy only; no automatic merges or deletions.');
        $output->writeln('  OpenCode registration is automated for clean projects; existing configs are never overwritten.');
        $output->writeln(sprintf('  Review %s/docs/install.md first.', $targetPath));
        $output->writeln('  Existing config: manually merge only the missing agents, default and model entries; never overwrite host settings.');
        $output->writeln('  Skills under .agents/skills/ are auto-discovered; no skills entry is needed.');
        $output->writeln('  Expected: workflow-orchestrator primary/default; workflow-explorer, workflow-worker and workflow-verifier subagents.');
        $output->writeln('  Reload OpenCode in the target, then the catalog check above turns green (catalog proof only, not a paid LLM proof).');
        $output->writeln('  Manually merge the kit AGENTS.md rules into preserved host instructions.');
        $output->writeln(sprintf('  cd %s/cli && composer install --no-dev', $targetPath));
        $output->writeln(sprintf('  Use ./cli/bin/workflow from the target or %s/cli/bin/workflow (optionally add %s/cli/bin to PATH):', $targetPath, $targetPath));
        $output->writeln(sprintf('    %s/cli/bin/workflow --project %s validate', $targetPath, $targetPath));
        $output->writeln(sprintf('  Refresh the derived board after manual edits: %s/cli/bin/workflow --project %s board', $targetPath, $targetPath));

        return self::SUCCESS;
    }

    private function registerOpenCodeAgents(string $kitRoot, string $targetPath, OutputInterface $output): void
    {
        $agents = ['workflow-orchestrator', 'workflow-explorer', 'workflow-worker', 'workflow-verifier'];
        $configPath = $targetPath.'/opencode.json';
        if (!is_file($configPath)) {
            $example = $kitRoot.'/docs/opencode.example.json';
            if (!is_link($configPath) && is_file($example) && @copy($example, $configPath)) {
                $output->writeln('  created opencode.json (four workflow agents registered for OpenCode)');
            } else {
                $output->writeln('<comment>warning: could not create opencode.json from docs/opencode.example.json; create it manually.</comment>');
            }
        } else {
            $decoded = json_decode((string) file_get_contents($configPath), true);
            $defined = is_array($decoded) && isset($decoded['agents']) && is_array($decoded['agents'])
                ? array_keys($decoded['agents'])
                : [];
            $missing = array_values(array_diff($agents, $defined));
            if (!is_array($decoded) || $decoded === []) {
                $output->writeln('<comment>warning: existing opencode.json is not readable JSON; merge the workflow agents manually from docs/opencode.example.json.</comment>');
            } elseif ($missing === []) {
                $output->writeln('  existing opencode.json already defines all four workflow agents; nothing changed.');
            } else {
                $output->writeln('<comment>warning: existing opencode.json is missing workflow agents: '.implode(', ', $missing).'; merge them manually from docs/opencode.example.json without touching host settings.</comment>');
            }
        }

        $this->verifyOpenCodeCatalog($targetPath, $agents, $output);
    }

    private function verifyOpenCodeCatalog(string $targetPath, array $agents, OutputInterface $output): void
    {
        $binary = trim((string) (shell_exec('command -v opencode 2>/dev/null') ?: ''));
        if ($binary === '') {
            $output->writeln('  opencode CLI not found on PATH; verify agent registration after reloading OpenCode:');
            $output->writeln(sprintf('    opencode api agent.list --param "location[directory]=%s"', $targetPath));

            return;
        }

        $raw = shell_exec(sprintf(
            '%s api agent.list --param %s 2>/dev/null',
            escapeshellarg($binary),
            escapeshellarg('location[directory]='.$targetPath),
        ));
        $ids = [];
        $decoded = json_decode((string) $raw, true);
        if (is_array($decoded) && isset($decoded['data']) && is_array($decoded['data'])) {
            foreach ($decoded['data'] as $agent) {
                if (is_array($agent) && is_string($agent['id'] ?? null)) {
                    $ids[] = $agent['id'];
                }
            }
        }

        $missing = array_values(array_diff($agents, $ids));
        if ($missing === []) {
            $output->writeln('  catalog: all four workflow agents are registered (catalog proof only, not a paid LLM proof).');
        } else {
            $output->writeln('<comment>warning: catalog reports missing workflow agents: '.implode(', ', $missing).' — reload OpenCode in the target, then re-check: opencode api agent.list --param "location[directory]='.$targetPath.'"</comment>');
        }
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
