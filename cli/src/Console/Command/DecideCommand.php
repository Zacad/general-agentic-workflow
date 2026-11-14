<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Store\ItemStore;
use Workflow\Support\Clock;
use Workflow\Domain\WorkflowException;
use Workflow\Support\ScopeOutput;

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
            ->addArgument('decision', InputArgument::REQUIRED, 'The decision and its reason.')
            ->addOption('run', null, InputOption::VALUE_REQUIRED, 'Authorize an automated decision using this run ID.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = (string) $input->getArgument('id');
        $decision = trim((string) $input->getArgument('decision'));
        $entry = sprintf('- %s · %s', Clock::now(), $decision);
        $store = $this->store($input);

        $run = $input->getOption('run');
        if ($run !== null) {
            try {
                $store->decideForRun($id, (string) $run, (string) $input->getArgument('decision'));
            } catch (WorkflowException $exception) {
                $output->writeln('Error: '.ScopeOutput::literal($exception->getMessage()), OutputInterface::OUTPUT_RAW);
                return self::INVALID;
            }
            $output->writeln('Automated decision recorded for '.ScopeOutput::literal($id).' by run '.ScopeOutput::literal($run).'.', OutputInterface::OUTPUT_RAW);
            $this->refreshBoard($input, $output);
            return self::SUCCESS;
        }

        $store->mutate($id, function ($item) use ($store, $entry): void {
            $item->body = ItemStore::appendToSection($item->body, 'Decisions', $entry);
        });

        $output->writeln(sprintf('Decision recorded for %s', $id));
        $output->writeln('Decision recorded. If this changes project-level facts, reconcile .workflow/PRD.md or .workflow/Architecture.md now.');

        return self::SUCCESS;
    }
}
