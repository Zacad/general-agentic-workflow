<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Board\BoardRenderer;

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
        $items = $this->view($input)->items();
        $html = (new BoardRenderer())->render(basename($store->root()), $items);
        $path = $store->workflowDir().'/board.html';

        if (file_put_contents($path, $html) === false) {
            $output->writeln('<error>Cannot write board.html</error>');

            return self::FAILURE;
        }

        $output->writeln($path);

        return self::SUCCESS;
    }
}
