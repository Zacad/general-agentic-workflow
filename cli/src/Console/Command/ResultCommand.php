<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Domain\WorkflowException;
use Workflow\Support\Clock;

final class ResultCommand extends BaseCommand
{
    private const STATUSES = ['done', 'blocked', 'needs-decision', 'failed'];
    private const ROLES = ['orchestrator', 'explorer', 'worker', 'verifier'];

    public function __construct()
    {
        parent::__construct('result');
    }

    protected function configure(): void
    {
        $this->setDescription('Record a compact worker receipt in the item log.')
            ->addArgument('id', InputArgument::REQUIRED, 'Item ID.')
            ->addOption('status', null, InputOption::VALUE_REQUIRED, 'done, blocked, needs-decision or failed.')
            ->addOption('role', null, InputOption::VALUE_REQUIRED, 'orchestrator, explorer, worker or verifier.', 'worker')
            ->addOption('finding', null, InputOption::VALUE_REQUIRED, 'One or two sentences.')
            ->addOption('artifacts', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Artifact path (repeatable or comma-separated).')
            ->addOption('decision', null, InputOption::VALUE_REQUIRED, 'Decision needed, if any.')
            ->addOption('next', null, InputOption::VALUE_REQUIRED, 'One bounded next action.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $status = strtolower((string) $input->getOption('status'));
        if (!in_array($status, self::STATUSES, true)) {
            $output->writeln('<error>Invalid --status: use done, blocked, needs-decision or failed.</error>');

            return self::INVALID;
        }

        $role = strtolower((string) $input->getOption('role'));
        if (!in_array($role, self::ROLES, true)) {
            $output->writeln('<error>Invalid --role: use orchestrator, explorer, worker or verifier.</error>');

            return self::INVALID;
        }

        $finding = trim((string) $input->getOption('finding'));
        if ($finding === '') {
            $output->writeln('<error>--finding is required.</error>');

            return self::INVALID;
        }

        $artifacts = [];
        foreach ($input->getOption('artifacts') as $entry) {
            foreach (explode(',', (string) $entry) as $path) {
                $path = trim($path);
                if ($path !== '') {
                    $artifacts[] = $path;
                }
            }
        }

        $decision = trim((string) $input->getOption('decision'));
        $next = trim((string) $input->getOption('next'));
        $at = Clock::now();

        $store = $this->store($input);
        $entry = sprintf("- %s · %s · %s\n", $at, $role, $status)
            .'  Finding: '.$finding."\n"
            .'  Artifacts: '.($artifacts !== [] ? implode(', ', $artifacts) : 'none')."\n"
            .'  Decision: '.($decision !== '' ? $decision : 'none')."\n"
            .'  Next: '.($next !== '' ? $next : 'none');
        try {
            $store->recordReceipt((string) $input->getArgument('id'), [
                'at' => $at,
                'role' => $role,
                'status' => $status,
                'finding' => $finding,
                'decision' => $decision !== '' ? $decision : null,
            ], $artifacts, $entry);
        } catch (WorkflowException $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');

            return self::FAILURE;
        }

        if ($input->getOption('json')) {
            $this->writeJson($output, ['id' => $input->getArgument('id'), 'role' => $role, 'status' => $status, 'at' => $at]);

            return self::SUCCESS;
        }

        $output->writeln(sprintf('%s result recorded: %s · %s', $input->getArgument('id'), $role, $status));

        return self::SUCCESS;
    }
}
