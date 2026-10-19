<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Store\Project;
use Workflow\Store\ProjectInitializer;

final class InitCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('init');
    }

    protected function configure(): void
    {
        $this->setDescription('Scaffold .workflow/ in a project.')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Project name for the document templates.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $root = Project::find($input->getOption('project'));
        $initializer = new ProjectInitializer(dirname(__DIR__, 3).'/templates');
        $created = $initializer->initialize($root, $input->getOption('name'));

        if ($created === []) {
            $output->writeln(sprintf('Already initialized: %s', $root));

            return self::SUCCESS;
        }

        $output->writeln(sprintf('Initialized %s', $root));
        foreach ($created as $path) {
            $output->writeln('  created '.$path);
        }

        return self::SUCCESS;
    }
}
