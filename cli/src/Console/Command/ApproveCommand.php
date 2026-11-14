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
use Workflow\Domain\WorkflowException;
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
            ->addOption('delta', null, InputOption::VALUE_NONE, 'Delta approval: approve only non-material changes.')
            ->addOption('run', null, InputOption::VALUE_REQUIRED, 'Authorize an automated approval using this run ID.')
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
        $isDelta = (bool) $input->getOption('delta');
        $at = Clock::now();

        $store = $this->store($input);
        $run = $input->getOption('run');
        if ($run !== null && ($input->getOption('by') !== null || $input->getOption('confirmation') !== null)) {
            $output->writeln('<error>Automated approval cannot use --by or --confirmation.</error>');
            return self::INVALID;
        }
        try {
        if ($run !== null) {
            $approved = $store->approveScopeForRun($id, (string) $run, $isDelta);
            $item = $approved['item'];
            $result = ['id' => $item->id, 'by' => 'automated', 'scope_hash' => $item->approval->hash,
                'status' => $item->status->value, ...ScopeOutput::scope($item), 'owns' => $item->owns,
                'provenance' => $item->approval->provenance];
        } else {
        $result = $store->mutate($id, function (Item $item) use ($by, $confirmation, $at, $isDelta, $id): array {
            if ($isDelta && (new \Workflow\Support\ChangeDetector())->detect($item, $item->lastApprovedScope)['has_material']) {
                throw new WorkflowException('Cannot use --delta: material changes detected. Run "workflow change '.$id.'" for details.');
            }
            $hash = $item->scopeHash();
            $item->approval = new Approval($at, $by, $hash, $confirmation !== '' ? $confirmation : null);
            $item->lastApprovedScope = $item->snapshotScope();
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
        }
        } catch (WorkflowException $exception) {
            $output->writeln('Error: '.ScopeOutput::literal($exception->getMessage()), OutputInterface::OUTPUT_RAW);
            return self::INVALID;
        }
        $this->refreshBoard($input, $output);

        if ($input->getOption('json')) {
            $this->writeJson($output, $result);

            return self::SUCCESS;
        }

        $approvalType = $isDelta ? 'Delta approved' : 'Approved';
        $output->writeln(sprintf(
            '%s %s by %s',
            $approvalType,
            ScopeOutput::literal($result['id']),
            ScopeOutput::literal($result['by']),
        ), OutputInterface::OUTPUT_RAW);
        ScopeOutput::write($output, $result, $result['owns']);
        $output->writeln('hash: '.$result['scope_hash']);
        $output->writeln('status: '.$result['status']);
        if ($run !== null) {
            $output->writeln('Automated approval by run '.ScopeOutput::literal($run).'.', OutputInterface::OUTPUT_RAW);
        } elseif ($confirmation !== '') {
            $output->writeln(sprintf('Recorded approval by %s at %s — this certifies an explicit user confirmation of hash %s; if confirmation was implicit, record it via workflow log %s.', ScopeOutput::literal($by), $at, $result['scope_hash'], $id), OutputInterface::OUTPUT_RAW);
        } else {
            $output->writeln('Approval source is legacy/unspecified; no confirmation quote recorded.', OutputInterface::OUTPUT_RAW);
        }

        return self::SUCCESS;
    }
}
