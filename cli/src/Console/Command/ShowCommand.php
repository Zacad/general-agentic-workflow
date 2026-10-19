<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ShowCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('show');
    }

    protected function configure(): void
    {
        $this->setDescription('Print a raw item record. Human/debug use; the orchestrator should use brief.')
            ->addArgument('id', InputArgument::REQUIRED, 'Item ID.')
            ->addOption('section', null, InputOption::VALUE_REQUIRED, 'Print only one Markdown section.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $store = $this->store($input);
        $id = (string) $input->getArgument('id');
        $item = $store->load($id);

        $section = (string) $input->getOption('section');
        if ($section !== '') {
            $content = $item->section($section);
            $output->writeln($content === '' ? sprintf('(no "%s" section, or it is empty)', $section) : $content);

            return self::SUCCESS;
        }

        if ($input->getOption('json')) {
            $this->writeJson($output, ['item' => $item->toArray(), 'body' => $item->body]);

            return self::SUCCESS;
        }

        $raw = file_get_contents($store->itemPath($id));
        $output->write($raw === false ? '' : $raw);

        return self::SUCCESS;
    }
}
