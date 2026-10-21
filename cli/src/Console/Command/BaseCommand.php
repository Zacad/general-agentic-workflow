<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Board\BoardWriter;
use Workflow\Domain\Item;
use Workflow\Read\ProjectView;
use Workflow\Store\ItemStore;
use Workflow\Store\Project;

abstract class BaseCommand extends Command
{
    protected function projectRoot(InputInterface $input): string
    {
        return Project::require($input->getOption('project'));
    }

    protected function store(InputInterface $input): ItemStore
    {
        return new ItemStore($this->projectRoot($input));
    }

    protected function view(InputInterface $input): ProjectView
    {
        return new ProjectView($this->store($input));
    }

    protected function writeJson(OutputInterface $output, mixed $data): void
    {
        $output->writeln(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            OutputInterface::OUTPUT_RAW,
        );
    }

    /**
     * Rewrite the generated board after a state-changing command. The board is
     * derived state: a failure must not fail the command that changed the item.
     */
    protected function refreshBoard(InputInterface $input, OutputInterface $output): void
    {
        try {
            (new BoardWriter())->write($this->store($input));
        } catch (\Throwable $exception) {
            $errorOutput = $output instanceof ConsoleOutput ? $output->getErrorOutput() : $output;
            $errorOutput->writeln('<comment>warning: board not refreshed: '.$exception->getMessage().'</comment>');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function itemSummary(Item $item): array
    {
        return [
            'id' => $item->id,
            'kind' => $item->kind->value,
            'parent' => $item->parent,
            'title' => $item->title,
            'status' => $item->status->value,
            'phase' => $item->phase->value,
            'priority' => $item->priority,
            'updated' => $item->updated,
            'last_result' => $item->lastResult,
        ];
    }
}
