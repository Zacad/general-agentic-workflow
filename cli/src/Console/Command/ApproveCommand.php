<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Domain\Approval;
use Workflow\Domain\Item;
use Workflow\Domain\Status;
use Workflow\Support\Clock;
use Workflow\Support\ScopeOutput;

final class ApproveCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('approve');
    }

    protected function configure(): void
    {
        $this->setDescription('Record approval of the current item scope.')
            ->addArgument('id', InputArgument::REQUIRED, 'Item ID.')
            ->addOption('by', null, InputOption::VALUE_REQUIRED, 'Who approved; "unspecified" when omitted.')
            ->addOption('confirmation', null, InputOption::VALUE_REQUIRED, 'Recorded user confirmation quote for this approval.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = (string) $input->getArgument('id');
        $by = trim((string) $input->getOption('by'));
        if ($by === '') {
            // Bare approval must not fabricate an approver name.
            $by = 'unspecified';
        }
        $confirmation = trim((string) $input->getOption('confirmation'));
        $at = Clock::now();

        $result = $this->store($input)->mutate($id, function (Item $item) use ($by, $confirmation, $at): array {
            $hash = $item->scopeHash();
            $item->approval = new Approval($at, $by, $hash, $confirmation !== '' ? $confirmation : null);
            if ($item->status === Status::Draft) {
                $item->status = Status::Todo;
            }

            $result = [
                'id' => $item->id,
                'by' => $item->approval->by,
                'scope_hash' => $hash,
                'status' => $item->status->value,
                ...ScopeOutput::scope($item),
                'owns' => $item->owns,
            ];
            if ($item->approval->confirmation !== null) {
                $result['confirmation'] = $item->approval->confirmation;
            }

            return $result;
        });
        $this->refreshBoard($input, $output);

        if ($input->getOption('json')) {
            $this->writeJson($output, $result);

            return self::SUCCESS;
        }

        $output->writeln(sprintf(
            'Approved %s by %s',
            ScopeOutput::literal($result['id']),
            ScopeOutput::literal($result['by']),
        ), OutputInterface::OUTPUT_RAW);
        ScopeOutput::write($output, $result, $result['owns']);
        $output->writeln('hash: '.$result['scope_hash']);
        $output->writeln('status: '.$result['status']);
        $output->writeln(sprintf(
            'Recorded approval by %s at %s — this certifies an explicit user confirmation of hash %s; if confirmation was implicit, record it via workflow log %s.',
            ScopeOutput::literal($result['by']),
            $at,
            $result['scope_hash'],
            $result['id'],
        ), OutputInterface::OUTPUT_RAW);

        return self::SUCCESS;
    }
}
