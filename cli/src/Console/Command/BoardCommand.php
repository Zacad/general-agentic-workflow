<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Board\BoardWriter;

final class BoardCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('board');
    }

    protected function configure(): void
    {
        $this->setDescription('Write a static, read-only HTML board.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $store = $this->store($input);
        $path = (new BoardWriter())->write($store);

        $output->writeln($path);

        return self::SUCCESS;
    }
}
