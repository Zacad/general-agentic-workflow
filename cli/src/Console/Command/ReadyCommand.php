<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Support\Text;

final class ReadyCommand extends BaseCommand
{
    private const LIMIT = 20;
    public function __construct()
    {
        parent::__construct('ready');
    }

    protected function configure(): void
    {
        $this->setDescription('List items that can start now, with dependency and ownership facts.')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Show every ready item instead of the first 20.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $view = $this->view($input);
        $items = $view->items();
        $rows = $view->ready($items);

        if ($input->getOption('json')) {
            $this->writeJson($output, array_map(static fn (array $row): array => [
                'id' => $row['item']->id,
                'title' => $row['item']->title,
                'priority' => $row['item']->priority,
                'depends_on' => $row['item']->dependsOn,
                'owns' => $row['item']->owns,
                'overlaps' => $row['overlaps'],
                'warnings' => $row['warnings'],
            ], $rows));

            return self::SUCCESS;
        }

        if ($rows === []) {
            $output->writeln('No ready items.');

            return self::SUCCESS;
        }

        $shown = $input->getOption('all') ? $rows : array_slice($rows, 0, self::LIMIT);

        foreach ($shown as $row) {
            $item = $row['item'];
            $suffix = 'deps:'.Text::inlineList($item->dependsOn);
            if ($item->owns !== []) {
                $suffix .= '  owns:'.implode(',', $item->owns);
            }
            if ($row['overlaps'] !== []) {
                $suffix .= '  overlap:'.implode(',', $row['overlaps']);
            }
            $output->writeln(sprintf('%-7s %s  %s', $item->id, Text::truncate($item->title, 60), $suffix));
            foreach ($row['warnings'] as $warning) {
                $output->writeln('<comment>warning: '.$item->id.' '.$warning.'</comment>');
            }
        }

        if (count($rows) > count($shown)) {
            $output->writeln(sprintf('… and %d more (use --all)', count($rows) - count($shown)));
        }

        return self::SUCCESS;
    }
}
