<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Domain\Item;
use Workflow\Domain\Phase;
use Workflow\Domain\Status;
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
            ->addOption('start', null, InputOption::VALUE_NONE, 'Also set status to in-progress.');
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

        $id = (string) $input->getArgument('id');
        $content = trim((string) $text);

        $store = $this->store($input);
        $store->mutate($id, function (Item $item) use ($content, $phase, $start): void {
            $item->body = ItemStore::replaceSection($item->body, 'Design', $content);
            if ($phase !== null) {
                $item->phase = $phase;
            }
            if ($start) {
                $item->status = Status::InProgress;
            }
        });

        $output->writeln(sprintf('Design updated for %s', $id));
        $this->refreshBoard($input, $output);

        return self::SUCCESS;
    }
}
