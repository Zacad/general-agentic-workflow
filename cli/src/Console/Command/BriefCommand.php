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

        $packet = $this->packet($item, $items, $role);
        if ($input->getOption('json')) {
            $this->writeJson($output, $packet);
        } else {
            $this->writeText($output, $packet);
        }

        return self::SUCCESS;
    }

    /**
     * @param array<string, Item> $items
     *
     * @return array<string, mixed>
     */
    private function packet(Item $item, array $items, string $role): array
    {
        $parent = $item->parent !== null ? ($items[$item->parent] ?? null) : null;
        // A delegation packet is not the persisted Item: new metadata must not
        // silently become role input, especially for an independent verifier.
        $packet = [
            'item' => [
                'id' => $item->id,
                'kind' => $item->kind->value,
                'parent' => $item->parent,
                'title' => $item->title,
                'status' => $item->status->value,
                'phase' => $item->phase->value,
                'priority' => $item->priority,
                'depends_on' => $item->dependsOn,
                'owns' => $item->owns,
                'artifacts' => $item->artifacts,
                'outcome' => $item->outcome,
                'scope' => ['in' => $item->scopeIn, 'out' => $item->scopeOut],
                'acceptance' => $item->acceptance,
            ],
            'role' => $role,
            'approval' => $this->approvalLabel($item),
            'dependencies' => $this->dependencyLines($item, $items),
            'upstream_artifacts' => $this->upstreamArtifacts($item, $items),
            'plan' => $item->section('Plan'),
            'design' => $item->section('Design'),
            'parent_design' => $parent?->section('Design') ?? '',
            'decisions' => $item->section('Decisions'),
            'deliverable_paths' => $item->owns,
        ];

        if ($role === 'explorer') {
            $packet['output_target'] = '.workflow/items/'.$item->id.'/outputs/';
        }

        if ($role !== 'verifier' && $item->lastResult !== null) {
            $receipt = $item->lastResult;
            $packet['item']['last_result'] = [
                'role' => in_array($receipt['role'] ?? null, ['orchestrator', 'explorer', 'worker', 'verifier'], true)
                    ? $receipt['role'] : '?',
                'status' => in_array($receipt['status'] ?? null, ['done', 'blocked', 'needs-decision', 'failed'], true)
                    ? $receipt['status'] : '?',
                'finding' => Text::truncate(is_string($receipt['finding'] ?? null) ? $receipt['finding'] : '', 100),
            ];
        }

        return $packet;
    }

    /**
     * @param array<string, mixed> $packet
     */
    private function writeText(OutputInterface $output, array $packet): void
    {
        $item = $packet['item'];
        $role = $packet['role'];
        $plan = $packet['plan'];
        $design = $packet['design'];

        $output->writeln(sprintf('%s · %s', $item['id'], $item['title']));
        $output->writeln(sprintf(
            'kind: %s · status: %s · phase: %s · priority: %d · role: %s',
            $item['kind'],
            $item['status'],
            $item['phase'],
            $item['priority'],
            $role,
        ));
        $output->writeln(sprintf(
            'parent: %s · approval: %s',
            $item['parent'] ?? 'none',
            $packet['approval'],
        ));
        $output->writeln(sprintf(
            'depends: %s · owns: %s',
            Text::inlineList($packet['dependencies']),
            Text::inlineList($packet['deliverable_paths']),
        ));
        $output->writeln('outcome: '.($item['outcome'] !== '' ? $item['outcome'] : '(not set)'));
        $output->writeln('scope in: '.Text::inlineList($item['scope']['in']));
        $output->writeln('scope out: '.Text::inlineList($item['scope']['out']));

        if ($item['acceptance'] !== []) {
            $output->writeln('acceptance:');
            foreach ($item['acceptance'] as $index => $criterion) {
                $output->writeln(sprintf('  %d. %s', $index + 1, $criterion));
            }
        } else {
            $output->writeln('acceptance: (not set)');
        }

        $output->writeln('plan:');
        if ($plan === '') {
            $output->writeln('  (not written yet)');
        } else {
            foreach (preg_split('/\R/u', $plan) ?: [] as $line) {
                $output->writeln('  '.$line);
            }
        }

        if ($plan === '' && in_array($item['status'], ['todo', 'in-progress'], true)
            && ($role === 'explorer' || $item['phase'] !== 'plan')
        ) {
            $output->writeln('<comment>warning: no plan recorded; run "workflow plan '.$item['id'].'" before delegating.</comment>');
        }

        if ($packet['parent_design'] !== '') {
            $output->writeln('parent design:');
            foreach (preg_split('/\R/u', $packet['parent_design']) ?: [] as $line) {
                $output->writeln('  '.$line);
            }
        }

        $output->writeln('design:');
        if ($design === '') {
            $output->writeln('  (not written yet)');
        } else {
            foreach (preg_split('/\R/u', $design) ?: [] as $line) {
                $output->writeln('  '.$line);
            }
        }

        if ($design === '' && in_array($item['status'], ['todo', 'in-progress'], true)
            && in_array($item['phase'], ['implement', 'verify'], true)
        ) {
            $output->writeln('<comment>warning: no design recorded; run "workflow design '.$item['id'].'" before delegating.</comment>');
        }

        if (isset($packet['output_target'])) {
            $output->writeln('write: '.$packet['output_target'].' (one focused research artifact)');
        }

        if ($item['artifacts'] !== []) {
            $output->writeln('artifacts:');
            foreach ($item['artifacts'] as $artifact) {
                $output->writeln('  - '.$artifact);
            }
        }

        if ($packet['decisions'] !== '') {
            $output->writeln('decisions:');
            foreach (preg_split('/\R/u', $packet['decisions']) ?: [] as $line) {
                $output->writeln('  '.$line);
            }
        }

        if ($packet['upstream_artifacts'] !== []) {
            $output->writeln('upstream artifacts:');
            foreach ($packet['upstream_artifacts'] as $id => $paths) {
                $output->writeln(sprintf('  %s: %s', $id, implode(', ', $paths)));
            }
        }

        if (isset($item['last_result'])) {
            $receipt = $item['last_result'];
            $output->writeln(sprintf(
                'last result: %s · %s — %s',
                $receipt['role'],
                $receipt['status'],
                $receipt['finding'],
            ));
        }

        if ($role === 'verifier') {
            $output->writeln('producer claim: omitted; derive expectations from the criteria and artifacts.');
        }

        $output->writeln('return: one receipt (status, finding, artifacts, decision, next)');
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
