<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Domain\Item;
use Workflow\Domain\Kind;
use Workflow\Read\ProjectDocuments;
use Workflow\Support\ScopeOutput;

final class ReviewCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('review');
    }

    protected function configure(): void
    {
        $this->setDescription('Print the exact scope and decomposition packet for an epic and its descendants.')
            ->addArgument('id', InputArgument::REQUIRED, 'Epic item ID.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $store = $this->store($input);
        $items = $this->view($input)->items();
        $epic = $store->load((string) $input->getArgument('id'));

        if ($epic->kind !== Kind::Epic) {
            $output->writeln(sprintf('<error>%s is not an epic; review expects an epic ID.</error>', $epic->id));

            return self::INVALID;
        }

        $children = [];
        foreach ($items as $item) {
            if ($item->parent !== null) {
                $children[$item->parent][] = $item;
            }
        }
        foreach ($children as &$group) {
            usort($group, static fn (Item $a, Item $b): int => strnatcmp($a->id, $b->id));
        }
        unset($group);

        $descendants = $this->descendants($epic->id, $children);
        $tasks = 0;
        $subtasks = 0;
        $open = 0;
        foreach ($descendants as $item) {
            if ($item->kind === Kind::Task) {
                $tasks++;
            } elseif ($item->kind === Kind::Subtask) {
                $subtasks++;
            }
            if ($item->status->isOpen()) {
                $open++;
            }
        }

        $packet = [
            'epic' => $this->packet($epic),
            'descendants' => array_map($this->packet(...), $descendants),
            'counts' => ['tasks' => $tasks, 'subtasks' => $subtasks, 'open' => $open],
            'prd' => $this->prd($input),
        ];
        if ($input->getOption('json')) {
            $this->writeJson($output, $packet);

            return self::SUCCESS;
        }

        $output->writeln(sprintf('%s · %s', $epic->id, ScopeOutput::literal($epic->title)), OutputInterface::OUTPUT_RAW);
        $this->printItem($packet['epic'], $output);
        $this->printPrd($packet['prd'], $output);
        $output->writeln('');
        $output->writeln('## Decomposition');
        if ($packet['descendants'] === []) {
            $output->writeln('(no child items)');
        } else {
            $depths = [$epic->id => 0];
            foreach ($packet['descendants'] as $item) {
                $depth = $depths[$item['parent']];
                $indent = str_repeat('  ', $depth);
                $output->writeln(sprintf('%s- %s · %s', $indent, $item['id'], ScopeOutput::literal($item['title'])), OutputInterface::OUTPUT_RAW);
                $this->printItem($item, $output, $indent.'  ');
                $depths[$item['id']] = $depth + 1;
            }
        }

        $output->writeln('');
        $output->writeln(sprintf(
            'counts: %d task(s), %d subtask(s), %d open item(s); review the exact scope above before approval.',
            $tasks,
            $subtasks,
            $open,
        ));

        return self::SUCCESS;
    }

    /**
     * @param array<string, list<Item>> $children
     *
     * @return list<Item>
     */
    private function descendants(string $id, array $children): array
    {
        $items = [];
        foreach ($children[$id] ?? [] as $child) {
            $items[] = $child;
            array_push($items, ...$this->descendants($child->id, $children));
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function packet(Item $item): array
    {
        return [
            'id' => $item->id,
            ...ScopeOutput::scope($item),
            'owns' => $item->owns,
            'status' => $item->status->value,
            'phase' => $item->phase->value,
            'scope_hash' => $item->scopeHash(),
            'approval' => $item->approval?->toArray(),
            'approval_state' => $this->approvalLabel($item),
            'plan' => $item->section('Plan'),
            'design' => $item->section('Design'),
            'decisions' => $item->section('Decisions'),
        ];
    }

    /**
     * Bounded product-truth context for the approval decision.
     *
     * @return array{state: string, goals: string, non_goals: string}
     */
    private function prd(InputInterface $input): array
    {
        return (new ProjectDocuments($this->projectRoot($input)))->prdExcerpt();
    }

    /**
     * @param array{state: string, goals: string, non_goals: string} $prd
     */
    private function printPrd(array $prd, OutputInterface $output): void
    {
        $output->writeln('PRD .workflow/PRD.md: '.$prd['state'], OutputInterface::OUTPUT_RAW);
        foreach (['goals' => $prd['goals'], 'non-goals' => $prd['non_goals']] as $label => $text) {
            if ($text === '') {
                continue;
            }
            $output->writeln($label.':', OutputInterface::OUTPUT_RAW);
            foreach (preg_split('/\R/u', $text) ?: [] as $line) {
                $output->writeln('  '.$line, OutputInterface::OUTPUT_RAW);
            }
        }
    }

    /**
     * @param array<string, mixed> $item
     */
    private function printItem(array $item, OutputInterface $output, string $indent = ''): void
    {
        ScopeOutput::write($output, $item, $item['owns'], $indent);
        $output->writeln(sprintf(
            '%sstatus: %s · phase: %s · approval: %s',
            $indent,
            $item['status'],
            $item['phase'],
            ScopeOutput::literal($item['approval_state']),
        ), OutputInterface::OUTPUT_RAW);
        $output->writeln($indent.'hash: '.$item['scope_hash']);
        $output->writeln($indent.'approval record: '.ScopeOutput::literal($item['approval']), OutputInterface::OUTPUT_RAW);
        $output->writeln(sprintf(
            '%splan: %s · design: %s',
            $indent,
            $item['plan'] !== '' ? 'written' : 'missing',
            $item['design'] !== '' ? 'written' : 'missing',
        ));

        foreach (['plan', 'design', 'decisions'] as $section) {
            $text = $item[$section];
            if ($text === '') {
                continue;
            }
            $output->writeln($indent.$section.':');
            foreach (preg_split('/\R/u', $text) ?: [] as $line) {
                $output->writeln($indent.'  '.$line, OutputInterface::OUTPUT_RAW);
            }
        }
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
}
