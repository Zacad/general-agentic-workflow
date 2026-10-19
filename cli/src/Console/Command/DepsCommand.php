<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Domain\Item;
use Workflow\Support\Text;

final class DepsCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('deps');
    }

    protected function configure(): void
    {
        $this->setDescription('Show dependency and dependent relationships.')
            ->addArgument('id', InputArgument::OPTIONAL, 'Item ID; omit for all edges.')
            ->addOption('graph', null, InputOption::VALUE_NONE, 'Emit a Mermaid graph.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $view = $this->view($input);
        $items = $view->items();
        $id = $input->getArgument('id');

        if ($input->getOption('graph')) {
            $output->writeln('graph TD');
            foreach ($items as $item) {
                foreach ($item->dependsOn as $dependencyId) {
                    $output->writeln(sprintf(
                        '  "%s" --> "%s"',
                        $dependencyId,
                        $item->id,
                    ));
                }
            }

            return self::SUCCESS;
        }

        if ($id !== null) {
            $item = $items[$id] ?? null;
            if ($item === null) {
                $output->writeln(sprintf('<error>Item not found: %s</error>', $id));

                return self::INVALID;
            }

            $depends = [];
            foreach ($item->dependsOn as $dependencyId) {
                $dependency = $items[$dependencyId] ?? null;
                $depends[] = ['id' => $dependencyId, 'status' => $dependency?->status->value ?? 'missing', 'title' => $dependency?->title ?? ''];
            }

            $blocks = [];
            foreach ($items as $other) {
                if (in_array($item->id, $other->dependsOn, true)) {
                    $blocks[] = ['id' => $other->id, 'status' => $other->status->value, 'title' => $other->title];
                }
            }

            if ($input->getOption('json')) {
                $this->writeJson($output, ['id' => $item->id, 'depends_on' => $depends, 'blocks' => $blocks]);

                return self::SUCCESS;
            }

            $output->writeln(sprintf('%s · %s', $item->id, $item->title));
            $output->writeln('depends on:');
            if ($depends === []) {
                $output->writeln('  none');
            } else {
                foreach ($depends as $dependency) {
                    $output->writeln(sprintf('  %-7s %-12s %s', $dependency['id'], $dependency['status'], $dependency['title']));
                }
            }
            $output->writeln('blocks:');
            if ($blocks === []) {
                $output->writeln('  none');
            } else {
                foreach ($blocks as $blocked) {
                    $output->writeln(sprintf('  %-7s %-12s %s', $blocked['id'], $blocked['status'], $blocked['title']));
                }
            }

            return self::SUCCESS;
        }

        if ($input->getOption('json')) {
            $this->writeJson($output, array_map(fn (Item $item): array => [
                'id' => $item->id,
                'depends_on' => $item->dependsOn,
            ], array_values($items)));

            return self::SUCCESS;
        }

        foreach ($items as $item) {
            $output->writeln(sprintf(
                '%-7s depends on: %-30s %s',
                $item->id,
                Text::inlineList($item->dependsOn),
                $item->title,
            ));
        }

        return self::SUCCESS;
    }
}
