<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Domain\Approval;
use Workflow\Domain\Item;
use Workflow\Domain\Kind;
use Workflow\Domain\Phase;
use Workflow\Domain\Status;
use Workflow\Domain\WorkflowException;
use Workflow\Support\Clock;
use Workflow\Store\ItemStore;
use Workflow\Support\ScopeOutput;

final class DesignCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('design');
    }

    protected function configure(): void
    {
        $this->setDescription('Replace the item\'s ## Design section from text or a file.')
            ->addArgument('id', InputArgument::REQUIRED, 'Item ID.')
            ->addOption('text', null, InputOption::VALUE_REQUIRED, 'Design text. Use ### for subsections.')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Read the design from a file.')
            ->addOption('phase', null, InputOption::VALUE_REQUIRED, 'Also set phase: plan, explore, design, implement or verify.')
            ->addOption('start', null, InputOption::VALUE_NONE, 'Also set status to in-progress.')
            ->addOption('approve', null, InputOption::VALUE_NONE, 'Also record design approval of the just-written Design (tasks and subtasks only).')
            ->addOption('run', null, InputOption::VALUE_REQUIRED, 'Authorize an automated design approval using this run ID.')
            ->addOption('by', null, InputOption::VALUE_REQUIRED, 'Who approved; "unspecified" when omitted.')
            ->addOption('confirmation', null, InputOption::VALUE_REQUIRED, 'Recorded user confirmation quote for this design approval.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $text = $input->getOption('text');
        $file = $input->getOption('file');

        if (($text === null) === ($file === null)) {
            $output->writeln('<error>Provide exactly one of --text or --file.</error>');

            return self::INVALID;
        }

        if ($file !== null) {
            if (!is_file((string) $file)) {
                $output->writeln('Design file not found: '.ScopeOutput::literal((string) $file), OutputInterface::OUTPUT_RAW);

                return self::INVALID;
            }
            $text = file_get_contents((string) $file);
            if ($text === false) {
                $output->writeln('Cannot read design file: '.ScopeOutput::literal((string) $file), OutputInterface::OUTPUT_RAW);

                return self::INVALID;
            }
        }

        $phaseValue = $input->getOption('phase');
        $phase = $phaseValue === null ? null : Phase::tryFrom(strtolower((string) $phaseValue));
        if ($phaseValue !== null && $phase === null) {
            $output->writeln('<error>Invalid phase: use plan, explore, design, implement or verify.</error>');

            return self::INVALID;
        }
        $start = (bool) $input->getOption('start');
        $approve = (bool) $input->getOption('approve');

        $id = (string) $input->getArgument('id');
        $content = trim((string) $text);

        $store = $this->store($input);
        $run = $input->getOption('run');
        if ($run !== null) {
            if (!$approve || $phaseValue !== null || $start || $input->getOption('by') !== null || $input->getOption('confirmation') !== null) {
                $output->writeln('<error>Automated design requires --approve and forbids --phase, --start, --by and --confirmation.</error>');
                return self::INVALID;
            }
            try {
                $result = $store->approveDesignForRun($id, (string) $run, (string) $text);
            } catch (WorkflowException $exception) {
                $output->writeln('Error: '.ScopeOutput::literal($exception->getMessage()), OutputInterface::OUTPUT_RAW);
                return self::INVALID;
            }
            $output->writeln('Design updated for '.ScopeOutput::literal($id), OutputInterface::OUTPUT_RAW);
            $output->writeln('hash: '.$result['approval']['design_hash'], OutputInterface::OUTPUT_RAW);
            $output->writeln('Automated design approval by run '.ScopeOutput::literal($run).'.', OutputInterface::OUTPUT_RAW);
            $this->refreshBoard($input, $output);
            return self::SUCCESS;
        }
        // Validation before mutation: both refusals leave record and board
        // bytes untouched.
        if ($approve) {
            $item = $store->load($id);
            if (!in_array($item->kind, [Kind::Task, Kind::Subtask], true)) {
                $output->writeln('<error>Design approval applies to tasks and subtasks; epics keep scope approval only.</error>');

                return self::INVALID;
            }

            if ($item->status === Status::Done) {
                // Shared done-history refusal, not a new gate text.
                $output->writeln(sprintf(
                    '<error>%s: completed work is historical; create a new follow-up item with "workflow new" and explicitly approve its scope instead of changing or reopening this item.</error>',
                    $id,
                ));

                return self::INVALID;
            }
        }

        $by = trim((string) $input->getOption('by'));
        if ($by === '') {
            // Bare approval must not fabricate an approver name.
            $by = 'unspecified';
        }
        $confirmation = trim((string) $input->getOption('confirmation'));
        $at = Clock::now();

        $hash = $store->mutate($id, function (Item $item) use ($content, $phase, $start, $approve, $by, $confirmation, $at): string {
            if ($approve && (!in_array($item->kind, [Kind::Task, Kind::Subtask], true) || $item->status === Status::Done)) {
                throw new WorkflowException('Design approval requires a task or subtask that is not completed history.');
            }
            $item->body = ItemStore::replaceSection($item->body, 'Design', $content);
            if ($phase !== null) {
                $item->phase = $phase;
            }
            if ($start) {
                $item->status = Status::InProgress;
            }
            if ($approve) {
                // One atomic mutation: the hash is taken from the Design just
                // written, so the approval cannot certify different content.
                $item->designApproval = Approval::forDesign(
                    $at,
                    $by,
                    $item->designHash(),
                    $confirmation !== '' ? $confirmation : null,
                );
            }

            return $item->designHash();
        });

        $output->writeln(sprintf('Design updated for %s', $id));
        if ($approve) {
            $output->writeln('hash: '.$hash);
            $output->writeln($confirmation !== ''
                ? sprintf('Recorded design approval by %s at %s — this certifies an explicit user confirmation of design hash %s; if confirmation was implicit, record it via workflow log %s.', ScopeOutput::literal($by), $at, $hash, $id)
                : 'Design approval source is legacy/unspecified; no confirmation quote recorded.', OutputInterface::OUTPUT_RAW);
        }
        $this->refreshBoard($input, $output);

        return self::SUCCESS;
    }
}
