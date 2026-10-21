<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Domain\Item;
use Workflow\Domain\Status;
use Workflow\Support\Clock;
use Workflow\Support\Text;

final class StatusCommand extends BaseCommand
{
    private const ACTIVE_LIMIT = 10;
    private const SUMMARY_LIMIT = 5;
    public function __construct()
    {
        parent::__construct('status');
    }

    protected function configure(): void
    {
        $this->setDescription('Show the working set: active, ready, waiting and decisions.')
            ->addOption('all', null, InputOption::VALUE_NONE, 'List every item in each section, including done and cancelled.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $store = $this->store($input);
        $view = $this->view($input);
        $items = $view->items();
        $all = (bool) $input->getOption('all');
        $activeLimit = $all ? null : self::ACTIVE_LIMIT;
        $summaryLimit = $all ? null : self::SUMMARY_LIMIT;

        $readyRows = $view->ready($items);
        $readyIds = array_map(static fn (array $row): string => $row['item']->id, $readyRows);

        $active = array_values(array_filter($items, static fn (Item $item): bool => $item->status === Status::InProgress));
        $drafts = array_values(array_filter($items, static fn (Item $item): bool => $item->status === Status::Draft));
        $decisions = array_values(array_filter(
            $items,
            static fn (Item $item): bool => ($item->lastResult['status'] ?? null) === 'needs-decision'
                && $item->status->isOpen(),
        ));

        $waiting = [];
        foreach ($items as $item) {
            if ($item->status !== Status::Todo || in_array($item->id, $readyIds, true)) {
                continue;
            }
            $waiting[] = [$item, $view->blockReason($item, $items) ?? 'not ready'];
        }

        $counts = [];
        foreach (Status::cases() as $status) {
            $counts[$status->value] = count(array_filter($items, static fn (Item $item): bool => $item->status === $status));
        }

        if ($input->getOption('json')) {
            $data = [
                'project' => basename($store->root()),
                'active' => array_map(fn (Item $item): array => $this->itemSummary($item), $active),
                'drafts' => array_map(fn (Item $item): array => $this->itemSummary($item), $drafts),
                'ready' => array_map(static fn (array $row): array => [
                    'item' => $row['item']->id,
                    'title' => $row['item']->title,
                    'depends_on' => $row['item']->dependsOn,
                    'owns' => $row['item']->owns,
                    'overlaps' => $row['overlaps'],
                ], $readyRows),
                'waiting' => array_map(static fn (array $row): array => [
                    'item' => $row[0]->id,
                    'title' => $row[0]->title,
                    'reason' => $row[1],
                ], $waiting),
                'decisions' => array_map(fn (Item $item): array => $this->itemSummary($item), $decisions),
                'counts' => $counts,
            ];
            if ($all) {
                foreach ([Status::Done, Status::Cancelled] as $status) {
                    $group = array_values(array_filter($items, static fn (Item $item): bool => $item->status === $status));
                    $data[$status->value] = array_map(fn (Item $item): array => $this->itemSummary($item), $group);
                }
            }
            $this->writeJson($output, $data);

            return self::SUCCESS;
        }

        $output->writeln(basename($store->root()));

        if ($drafts !== []) {
            $this->section($output, 'drafts', $drafts, static fn (Item $item): string => sprintf(
                '%-7s %-6s %s',
                $item->id,
                $item->kind->value,
                Text::truncate($item->title, 60),
            ).'  (awaiting scope + approval)', $summaryLimit);
        }

        $this->section($output, 'active', $active, static function (Item $item): string {
            $role = (string) ($item->lastResult['role'] ?? '');
            $age = Clock::age((string) ($item->lastResult['at'] ?? $item->updated));
            $needs = ($item->lastResult['status'] ?? null) === 'needs-decision' ? '  needs: decision' : '';

            return trim(sprintf('%-7s %-9s %-4s %s%s', $item->id, $role !== '' ? $role : '—', $age, Text::truncate($item->title, 60), $needs));
        }, $activeLimit);

        if ($readyRows !== []) {
            $output->writeln('');
            $output->writeln(sprintf('ready (%d)', count($readyRows)));
            foreach (array_slice($readyRows, 0, $summaryLimit) as $row) {
                $item = $row['item'];
                $suffix = 'deps:'.Text::inlineList($item->dependsOn);
                if ($item->owns !== []) {
                    $suffix .= '  owns:'.implode(',', $item->owns);
                }
                if ($row['overlaps'] !== []) {
                    $suffix .= '  overlap:'.implode(',', $row['overlaps']);
                }
                $output->writeln(sprintf('  %-7s %s  %s', $item->id, Text::truncate($item->title, 60), $suffix));
            }
            $this->moreLine($output, count($readyRows), $summaryLimit ?? count($readyRows));
        }

        if ($waiting !== []) {
            $output->writeln('');
            $output->writeln(sprintf('waiting (%d)', count($waiting)));
            foreach (array_slice($waiting, 0, $summaryLimit) as [$item, $reason]) {
                $output->writeln(sprintf('  %-7s %s  %s', $item->id, Text::truncate($item->title, 60), $reason));
            }
            $this->moreLine($output, count($waiting), $summaryLimit ?? count($waiting));
        }

        if ($decisions !== []) {
            $output->writeln('');
            $output->writeln(sprintf('decisions (%d)', count($decisions)));
            foreach (array_slice($decisions, 0, $summaryLimit) as $item) {
                $text = (string) ($item->lastResult['decision'] ?? '');
                if ($text === '') {
                    $text = (string) ($item->lastResult['finding'] ?? '');
                }
                $output->writeln(sprintf('  %-7s %s  %s', $item->id, Text::truncate($item->title, 45), Text::truncate($text, 80)));
            }
            $this->moreLine($output, count($decisions), $summaryLimit ?? count($decisions));
        }

        if ($all) {
            foreach ([Status::Done, Status::Cancelled] as $status) {
                $group = array_values(array_filter($items, static fn (Item $item): bool => $item->status === $status));
                $this->section($output, $status->value, $group, static fn (Item $item): string => sprintf(
                    '%-7s %s',
                    $item->id,
                    Text::truncate($item->title, 60),
                ));
            }
        }

        $output->writeln('');
        $output->writeln(sprintf(
            '%d open · %d done · %d cancelled',
            $counts['draft'] + $counts['todo'] + $counts['in-progress'],
            $counts['done'],
            $counts['cancelled'],
        ));

        return self::SUCCESS;
    }

    /**
     * @param list<Item>             $items
     * @param callable(Item): string $line
     */
    private function section(OutputInterface $output, string $label, array $items, callable $line, ?int $limit = null): void
    {
        if ($items === []) {
            return;
        }

        $total = count($items);
        $shown = $limit === null ? $items : array_slice($items, 0, $limit);

        $output->writeln('');
        $output->writeln(sprintf('%s (%d)', $label, $total));
        foreach ($shown as $item) {
            $output->writeln('  '.$line($item));
        }

        $this->moreLine($output, $total, count($shown));
    }

    private function moreLine(OutputInterface $output, int $total, int $shown): void
    {
        if ($total > $shown) {
            $output->writeln(sprintf('  … and %d more', $total - $shown));
        }
    }
}
