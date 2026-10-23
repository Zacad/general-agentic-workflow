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
use Workflow\Support\Clock;
use Workflow\Store\ItemStore;

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
                $output->writeln(sprintf('<error>Design file not found: %s</error>', $file));

                return self::INVALID;
            }
            $text = file_get_contents((string) $file);
            if ($text === false) {
                $output->writeln(sprintf('<error>Cannot read design file: %s</error>', $file));

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
            $output->writeln(sprintf(
                'Recorded design approval by %s at %s — this certifies an explicit user confirmation of design hash %s; if confirmation was implicit, record it via workflow log %s.',
                $by,
                $at,
                $hash,
                $id,
            ), OutputInterface::OUTPUT_RAW);
        }
        $this->refreshBoard($input, $output);

        return self::SUCCESS;
    }
}
