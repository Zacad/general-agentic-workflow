<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Domain\Item;
use Workflow\Domain\Kind;
use Workflow\Support\Text;

final class ReviewCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('review');
    }

    protected function configure(): void
    {
        $this->setDescription('Print the exact scope and decomposition packet for an epic and its descendants.')
            ->addArgument('id', InputArgument::REQUIRED, 'Epic item ID.');
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

        $output->writeln(sprintf('%s · %s', $epic->id, $epic->title));
        $this->printItem($epic, $output);

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

        $output->writeln('');
        $output->writeln('## Decomposition');
        if (!isset($children[$epic->id])) {
            $output->writeln('(no child items)');
        } else {
            $this->printTree($children[$epic->id], $children, $output, 0);
        }

        $treeIds = [];
        $this->collectIds($epic->id, $children, $treeIds);

        $tasks = 0;
        $subtasks = 0;
        $open = 0;
        foreach ($items as $item) {
            if (!in_array($item->id, $treeIds, true)) {
                continue;
            }
            if ($item->kind === Kind::Task) {
                $tasks++;
            } elseif ($item->kind === Kind::Subtask) {
                $subtasks++;
            }
            if ($item->status->isOpen()) {
                $open++;
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
     * @param list<string>              $ids
     */
    private function collectIds(string $id, array $children, array &$ids): void
    {
        foreach ($children[$id] ?? [] as $child) {
            $ids[] = $child->id;
            $this->collectIds($child->id, $children, $ids);
        }
    }

    /**
     * @param list<Item>                $items
     * @param array<string, list<Item>> $children
     */
    private function printTree(array $items, array $children, OutputInterface $output, int $depth): void
    {
        $indent = str_repeat('  ', $depth);
        foreach ($items as $item) {
            $output->writeln(sprintf('%s- %s · %s', $indent, $item->id, $item->title));
            $this->printItem($item, $output, $indent.'  ');

            if (isset($children[$item->id])) {
                $this->printTree($children[$item->id], $children, $output, $depth + 1);
            }
        }
    }

    private function printItem(Item $item, OutputInterface $output, string $indent = ''): void
    {
        $output->writeln(sprintf(
            '%skind: %s · status: %s · phase: %s · parent: %s · approval: %s',
            $indent,
            $item->kind->value,
            $item->status->value,
            $item->phase->value,
            $item->parent ?? 'none',
            $this->approvalLabel($item),
        ));
        $output->writeln($indent.'outcome: '.($item->outcome !== '' ? $item->outcome : '(not set)'));
        $output->writeln($indent.'scope in: '.Text::inlineList($item->scopeIn));
        $output->writeln($indent.'scope out: '.Text::inlineList($item->scopeOut));

        if ($item->acceptance === []) {
            $output->writeln($indent.'acceptance: (not set)');
        } else {
            $output->writeln($indent.'acceptance:');
            foreach ($item->acceptance as $index => $criterion) {
                $output->writeln(sprintf('%s  %d. %s', $indent, $index + 1, $criterion));
            }
        }

        $output->writeln(sprintf(
            '%sdepends: %s · owns: %s',
            $indent,
            Text::inlineList($item->dependsOn),
            Text::inlineList($item->owns),
        ));
        $output->writeln(sprintf(
            '%splan: %s · design: %s',
            $indent,
            trim($item->section('Plan')) !== '' ? 'written' : 'missing',
            trim($item->section('Design')) !== '' ? 'written' : 'missing',
        ));

        foreach (['Plan', 'Design'] as $section) {
            $text = trim($item->section($section));
            if ($text === '') {
                continue;
            }
            $output->writeln($indent.strtolower($section).':');
            foreach (preg_split('/\R/', $text) ?: [] as $line) {
                $output->writeln($indent.'  '.$line);
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
