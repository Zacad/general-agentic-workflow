<?php

declare(strict_types=1);

namespace Workflow\Console;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Workflow\Console\Command\ApproveCommand;
use Workflow\Console\Command\BoardCommand;
use Workflow\Console\Command\BriefCommand;
use Workflow\Console\Command\CloseCommand;
use Workflow\Console\Command\DecideCommand;
use Workflow\Console\Command\DepsCommand;
use Workflow\Console\Command\DesignCommand;
use Workflow\Console\Command\InitCommand;
use Workflow\Console\Command\InstallCommand;
use Workflow\Console\Command\LogCommand;
use Workflow\Console\Command\NewCommand;
use Workflow\Console\Command\PlanCommand;
use Workflow\Console\Command\ReadyCommand;
use Workflow\Console\Command\ResultCommand;
use Workflow\Console\Command\ReviewCommand;
use Workflow\Console\Command\SetCommand;
use Workflow\Console\Command\ShowCommand;
use Workflow\Console\Command\StatusCommand;
use Workflow\Console\Command\ValidateCommand;

final class Application extends \Symfony\Component\Console\Application
{
    public function __construct()
    {
        parent::__construct('workflow', '5.0.0');

        $this->addCommands([
            new InitCommand(),
            new NewCommand(),
            new PlanCommand(),
            new DesignCommand(),
            new DecideCommand(),
            new StatusCommand(),
            new ReadyCommand(),
            new BriefCommand(),
            new ReviewCommand(),
            new ResultCommand(),
            new DepsCommand(),
            new SetCommand(),
            new ApproveCommand(),
            new LogCommand(),
            new CloseCommand(),
            new ValidateCommand(),
            new BoardCommand(),
            new ShowCommand(),
            new InstallCommand(),
        ]);
    }

    protected function getDefaultInputDefinition(): InputDefinition
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOption(new InputOption(
            'project',
            null,
            InputOption::VALUE_REQUIRED,
            'Project root containing .workflow (defaults to the nearest parent of the current directory).',
        ));

        return $definition;
    }
}
