<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Domain\Status;
use Workflow\Domain\WorkflowException;
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
            ->addOption('force', null, InputOption::VALUE_NONE, 'Close done without a verifier receipt.')
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
        $force = (bool) $input->getOption('force');

        try {
            $verified = $store->close($id, $status, $reason, $at, $force);
        } catch (WorkflowException $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');

            return self::FAILURE;
        }

        $this->refreshBoard($input, $output);

        if ($input->getOption('json')) {
            $this->writeJson($output, ['id' => $id, 'status' => $status->value, 'verified' => $verified]);

            return self::SUCCESS;
        }

        $output->writeln(sprintf('Closed %s as %s', $id, $status->value));

        $store = $this->store($input);
        $openChildren = [];
        $uncheckedChildren = false;
        foreach ($store->ids() as $candidateId) {
            try {
                $candidate = $store->load($candidateId);
            } catch (WorkflowException) {
                $uncheckedChildren = true;
                continue;
            }
            if ($candidate->parent === $id && $candidate->status->isOpen()) {
                $openChildren[] = $candidate->id;
            }
        }

        if ($uncheckedChildren) {
            $output->writeln('<comment>warning: child status was not fully checked because some legacy records could not be loaded; run "workflow validate".</comment>');
        }

        if ($openChildren !== []) {
            $output->writeln(sprintf(
                '<comment>warning: children still open: %s</comment>',
                implode(', ', $openChildren),
            ));
        }

        if ($status === Status::Done && !$verified) {
            $detail = $force ? 'forced' : 'existing completed history; no new verification';
            $output->writeln('<comment>warning: closed done without a current verifier receipt ('.$detail.').</comment>');
        }

        if ($status === Status::Done && trim($store->load($id)->section('Design')) === '') {
            $output->writeln('<comment>warning: closed done without a design; create a new follow-up item if material work is needed.</comment>');
        }

        if ($status === Status::Cancelled && $reason === '') {
            $output->writeln('<comment>warning: cancellation has no reason.</comment>');
        }

        return self::SUCCESS;
    }
}
