<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Domain\Item;
use Workflow\Domain\Status;
use Workflow\Support\Clock;

final class CloseCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('close');
    }

    protected function configure(): void
    {
        $this->setDescription('Close an item as done or cancelled.')
            ->addArgument('id', InputArgument::REQUIRED, 'Item ID.')
            ->addOption('status', null, InputOption::VALUE_REQUIRED, 'done or cancelled.', 'done')
            ->addOption('reason', null, InputOption::VALUE_REQUIRED, 'Reason for cancellation or closure.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $status = Status::tryFrom(strtolower((string) $input->getOption('status')));
        if ($status !== Status::Done && $status !== Status::Cancelled) {
            $output->writeln('<error>Invalid --status: use done or cancelled.</error>');

            return self::INVALID;
        }

        $id = (string) $input->getArgument('id');
        $reason = trim((string) $input->getOption('reason'));
        $at = Clock::now();
        $store = $this->store($input);

        $result = $store->mutate($id, function (Item $item) use ($store, $status, $reason, $at): array {
            $item->status = $status;
            $entry = sprintf('- %s · closed as %s', $at, $status->value);
            if ($reason !== '') {
                $entry .= "\n  Reason: ".$reason;
            }
            $store->appendProgress($item, $entry);

            return ['verified' => $item->verifiedAt !== null];
        });
        $this->refreshBoard($input, $output);

        if ($input->getOption('json')) {
            $this->writeJson($output, ['id' => $id, 'status' => $status->value, 'verified' => $result['verified']]);

            return self::SUCCESS;
        }

        $output->writeln(sprintf('Closed %s as %s', $id, $status->value));

        $store = $this->store($input);
        $openChildren = [];
        foreach ($store->all() as $candidate) {
            if ($candidate->parent === $id && $candidate->status->isOpen()) {
                $openChildren[] = $candidate->id;
            }
        }

        if ($openChildren !== []) {
            $output->writeln(sprintf(
                '<comment>warning: children still open: %s</comment>',
                implode(', ', $openChildren),
            ));
        }

        if ($status === Status::Done && !$result['verified']) {
            $output->writeln('<comment>warning: no verifier receipt recorded for this item.</comment>');
        }

        if ($status === Status::Cancelled && $reason === '') {
            $output->writeln('<comment>warning: cancellation has no reason.</comment>');
        }

        return self::SUCCESS;
    }
}
