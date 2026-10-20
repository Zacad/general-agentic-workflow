<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
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
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Read the design from a file.');
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

        $id = (string) $input->getArgument('id');
        $content = trim((string) $text);

        $store = $this->store($input);
        $store->mutate($id, function ($item) use ($content): void {
            $item->body = ItemStore::replaceSection($item->body, 'Design', $content);
        });

        $output->writeln(sprintf('Design updated for %s', $id));

        return self::SUCCESS;
    }
}
