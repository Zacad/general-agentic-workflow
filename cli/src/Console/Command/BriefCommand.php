<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Domain\Item;
use Workflow\Support\Text;

final class BriefCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('brief');
    }

    protected function configure(): void
    {
        $this->setDescription('Print a bounded delegation packet for one item.')
            ->addArgument('id', InputArgument::REQUIRED, 'Item ID.')
            ->addOption('for', null, InputOption::VALUE_REQUIRED, 'orchestrator, explorer, worker or verifier.', 'worker')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $store = $this->store($input);
        $view = $this->view($input);
        $items = $view->items();
        $item = $store->load((string) $input->getArgument('id'));

        $role = strtolower((string) $input->getOption('for'));
        if (!in_array($role, ['orchestrator', 'explorer', 'worker', 'verifier'], true)) {
            $output->writeln('<error>Invalid --for: use orchestrator, explorer, worker or verifier.</error>');

            return self::INVALID;
        }

        $approval = $this->approvalLabel($item);
        $dependencies = $this->dependencyLines($item, $items);
        $upstream = $this->upstreamArtifacts($item, $items);
        $plan = $item->section('Plan');
        $decisions = $item->section('Decisions');

        if ($input->getOption('json')) {
            $this->writeJson($output, [
                'item' => $item->toArray(),
                'role' => $role,
                'approval' => $approval,
                'dependencies' => $dependencies,
                'upstream_artifacts' => $upstream,
                'plan' => $plan,
                'decisions' => $decisions,
                'deliverable_paths' => $item->owns,
            ]);

            return self::SUCCESS;
        }

        $output->writeln(sprintf('%s · %s', $item->id, $item->title));
        $output->writeln(sprintf(
            'kind: %s · status: %s · phase: %s · priority: %d · role: %s',
            $item->kind->value,
            $item->status->value,
            $item->phase->value,
            $item->priority,
            $role,
        ));
        $output->writeln(sprintf(
            'parent: %s · approval: %s',
            $item->parent ?? 'none',
            $approval,
        ));
        $output->writeln(sprintf(
            'depends: %s · owns: %s',
            Text::inlineList($dependencies),
            Text::inlineList($item->owns),
        ));
        $output->writeln('outcome: '.($item->outcome !== '' ? $item->outcome : '(not set)'));
        $output->writeln('scope in: '.Text::inlineList($item->scopeIn));
        $output->writeln('scope out: '.Text::inlineList($item->scopeOut));

        if ($item->acceptance !== []) {
            $output->writeln('acceptance:');
            foreach ($item->acceptance as $index => $criterion) {
                $output->writeln(sprintf('  %d. %s', $index + 1, $criterion));
            }
        } else {
            $output->writeln('acceptance: (not set)');
        }

        $output->writeln('plan:');
        if ($plan === '') {
            $output->writeln('  (not written yet)');
        } else {
            foreach (preg_split('/\R/', $plan) ?: [] as $line) {
                $output->writeln('  '.$line);
            }
        }

        if ($role === 'explorer') {
            $output->writeln('write: items/'.$item->id.'/outputs/ (one focused research artifact)');
        }

        if ($item->artifacts !== []) {
            $output->writeln('artifacts:');
            foreach ($item->artifacts as $artifact) {
                $output->writeln('  - '.$artifact);
            }
        }

        if ($decisions !== '') {
            $output->writeln('decisions:');
            foreach (preg_split('/\R/', $decisions) ?: [] as $line) {
                $output->writeln('  '.$line);
            }
        }

        if ($upstream !== []) {
            $output->writeln('upstream artifacts:');
            foreach ($upstream as $id => $paths) {
                $output->writeln(sprintf('  %s: %s', $id, implode(', ', $paths)));
            }
        }

        if ($item->lastResult !== null && $role !== 'verifier') {
            $output->writeln(sprintf(
                'last result: %s · %s — %s',
                (string) ($item->lastResult['role'] ?? '?'),
                (string) ($item->lastResult['status'] ?? '?'),
                Text::truncate((string) ($item->lastResult['finding'] ?? ''), 100),
            ));
        }

        if ($role === 'verifier') {
            $output->writeln('producer claim: omitted; derive expectations from the criteria and artifacts.');
        }

        $output->writeln('return: one receipt (status, finding, artifacts, decision, next)');

        return self::SUCCESS;
    }

    private function approvalLabel(Item $item): string
    {
        if ($item->approval === null) {
            return 'none';
        }

        if ($item->hasStaleApproval()) {
            return 'STALE — re-approve before work';
        }

        return sprintf('current (%s, %s)', $item->approval->by, $item->approval->at);
    }

    /**
     * @param array<string, Item> $items
     *
     * @return list<string>
     */
    private function dependencyLines(Item $item, array $items): array
    {
        $lines = [];
        foreach ($item->dependsOn as $dependencyId) {
            $dependency = $items[$dependencyId] ?? null;
            $lines[] = $dependency === null
                ? $dependencyId.' (missing)'
                : $dependencyId.' ('.$dependency->status->value.')';
        }

        return $lines;
    }

    /**
     * @param array<string, Item> $items
     *
     * @return array<string, list<string>>
     */
    private function upstreamArtifacts(Item $item, array $items): array
    {
        $upstream = [];
        foreach ($item->dependsOn as $dependencyId) {
            $dependency = $items[$dependencyId] ?? null;
            if ($dependency !== null && $dependency->artifacts !== []) {
                $upstream[$dependencyId] = $dependency->artifacts;
            }
        }

        return $upstream;
    }
}
