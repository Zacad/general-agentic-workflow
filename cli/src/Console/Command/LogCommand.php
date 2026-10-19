<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Support\Clock;

final class LogCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('log');
    }

    protected function configure(): void
    {
        $this->setDescription('Append a progress line to an item.')
            ->addArgument('id', InputArgument::REQUIRED, 'Item ID.')
            ->addArgument('message', InputArgument::REQUIRED, 'Progress message.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = (string) $input->getArgument('id');
        $message = trim((string) $input->getArgument('message'));
        $entry = sprintf('- %s · %s', Clock::now(), $message);
        $store = $this->store($input);

        $store->mutate($id, function ($item) use ($store, $entry): void {
            $store->appendProgress($item, $entry);
        });

        $output->writeln(sprintf('Logged to %s', $id));

        return self::SUCCESS;
    }
}
