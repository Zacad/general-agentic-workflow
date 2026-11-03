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
            ->addOption('delta', null, InputOption::VALUE_NONE, 'Delta approval: approve only non-material changes.')
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
        $item = $store->load($id);

        // Check for material changes if delta approval
        if ($isDelta) {
            $detector = new \Workflow\Support\ChangeDetector();
            $changes = $detector->detect($item, $item->lastApprovedScope);
            if ($changes['has_material']) {
                $output->writeln('<error>Cannot use --delta: material changes detected. Run "workflow change '.$id.'" for details.</error>');

                return self::INVALID;
            }
        }

        $result = $store->mutate($id, function (Item $item) use ($by, $confirmation, $at): array {
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
