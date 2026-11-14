<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Domain\Kind;
use Workflow\Domain\Item;
use Workflow\Domain\Status;
use Workflow\Domain\WorkflowException;
use Workflow\Support\Clock;
use Workflow\Support\ScopeOutput;

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
            ->addOption('confirmation', null, InputOption::VALUE_REQUIRED, 'Closing a task/subtask from review: the user\'s literal acceptance words, or the named external gate that passed.')
            ->addOption('direct', null, InputOption::VALUE_NONE, 'Close a task/subtask done without a review hold; requires --reason.')
            ->addOption('run', null, InputOption::VALUE_REQUIRED, 'Authorize automated review acceptance using this run ID.')
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
        $confirmation = trim((string) $input->getOption('confirmation'));
        $direct = (bool) $input->getOption('direct');

        $run = $input->getOption('run');
        if ($run !== null) {
            if ($status !== Status::Done || $force || $direct || $input->getOption('confirmation') !== null || $reason === '') {
                $output->writeln('<error>Automated acceptance requires done and a nonblank --reason; --confirmation, --force and --direct are forbidden.</error>');
                return self::INVALID;
            }
            try {
                $event = $store->acceptReviewForRun($id, (string) $run, (string) $input->getOption('reason'));
            } catch (WorkflowException $exception) {
                $output->writeln('Error: '.ScopeOutput::literal($exception->getMessage()), OutputInterface::OUTPUT_RAW);
                return self::FAILURE;
            }
            $this->refreshBoard($input, $output);
            if ($input->getOption('json')) {
                $this->writeJson($output, ['id' => $id, 'status' => 'done', 'verified' => true, 'automated_acceptance' => $event]);
            } else {
                $output->writeln('Closed '.ScopeOutput::literal($id).' as done with automated acceptance by run '.ScopeOutput::literal($run).'.', OutputInterface::OUTPUT_RAW);
            }
            return self::SUCCESS;
        }

        try {
            $verified = $store->close($id, $status, $reason, $at, $force, function (Item $current, string $reason) use ($status, $id, $confirmation, $direct): string {
                if ($status === Status::Done && $current->kind !== Kind::Epic && $current->status->isOpen()) {
                    $gate = $this->reviewGate($id, $current->status, $confirmation, $direct, $reason);
                    if ($gate !== null) {
                        throw new WorkflowException($gate);
                    }
                    if ($current->status === Status::Review) {
                        $reason = trim('Accepted: '.$confirmation.($reason !== '' ? ' — '.$reason : ''));
                    } else {
                        $reason = 'Direct close without review: '.$reason;
                    }
                }
                return $reason;
            });
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

    private function reviewGate(string $id, Status $current, string $confirmation, bool $direct, string $reason): ?string
    {
        if ($current === Status::Review) {
            return $confirmation === '' ? sprintf(
                '%s is in review: close needs --confirmation with the user\'s literal acceptance or the external gate that passed. Review findings: workflow set %s status=in-progress.',
                $id,
                $id,
            ) : null;
        }
        if ($direct) {
            return $reason === '' ? '--direct needs --reason naming why no review hold applies.' : null;
        }

        return sprintf(
            '%s is %s, not review: hold it with workflow set %s status=review and workflow log %s "what is pending", then close with --confirmation once the user accepts or the gate passes. No review applies: add --direct --reason "why".',
            $id,
            $current->value,
            $id,
            $id,
        );
    }
}
