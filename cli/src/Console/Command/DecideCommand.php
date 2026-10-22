<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Store\ItemStore;
use Workflow\Support\Clock;

final class DecideCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('decide');
    }

    protected function configure(): void
    {
        $this->setDescription('Append a durable decision to the item.')
            ->addArgument('id', InputArgument::REQUIRED, 'Item ID.')
            ->addArgument('decision', InputArgument::REQUIRED, 'The decision and its reason.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = (string) $input->getArgument('id');
        $decision = trim((string) $input->getArgument('decision'));
        $entry = sprintf('- %s · %s', Clock::now(), $decision);
        $store = $this->store($input);

        $store->mutate($id, function ($item) use ($store, $entry): void {
            $item->body = ItemStore::appendToSection($item->body, 'Decisions', $entry);
        });

        $output->writeln(sprintf('Decision recorded for %s', $id));
        $output->writeln('Decision recorded. If this changes project-level facts, reconcile .workflow/PRD.md or .workflow/Architecture.md now.');

        return self::SUCCESS;
    }
}
