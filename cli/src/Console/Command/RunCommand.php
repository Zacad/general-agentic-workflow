<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Domain\Run;
use Workflow\Domain\WorkflowException;
use Workflow\Store\RunStore;

final class RunCommand extends BaseCommand
{
    public function __construct() { parent::__construct('run'); }

    protected function configure(): void
    {
        $this->setDescription('Create, inspect and reconcile autonomous run authority.')
            ->addArgument('operation', InputArgument::REQUIRED, 'create, show, report, resume or revoke')
            ->addArgument('id', InputArgument::OPTIONAL, 'Run ID')
            ->addOption('authorize-autonomous', null, InputOption::VALUE_NONE)
            ->addOption('goal', null, InputOption::VALUE_REQUIRED)
            ->addOption('criterion', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)
            ->addOption('action', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)
            ->addOption('root', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)
            ->addOption('expires-at', null, InputOption::VALUE_REQUIRED)
            ->addOption('max-roots', null, InputOption::VALUE_REQUIRED)
            ->addOption('json', null, InputOption::VALUE_NONE)
            ->addOption('status', null, InputOption::VALUE_REQUIRED)
            ->addOption('reason', null, InputOption::VALUE_REQUIRED)
            ->addOption('recovery', null, InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $op = $input->getArgument('operation');
        $allowed = [
            'create' => ['authorize-autonomous', 'goal', 'criterion', 'action', 'root', 'expires-at', 'max-roots'],
            'show' => ['json'], 'report' => ['status', 'reason', 'recovery'],
            'resume' => ['recovery'], 'revoke' => ['reason'],
        ];
        if (!isset($allowed[$op])) throw new WorkflowException('Unknown run operation.');
        foreach (['authorize-autonomous', 'goal', 'criterion', 'action', 'root', 'expires-at', 'max-roots', 'json', 'status', 'reason', 'recovery'] as $name) {
            $value = $input->getOption($name);
            if ($value !== null && $value !== false && $value !== [] && !in_array($name, $allowed[$op], true)) throw new WorkflowException('Option --'.$name.' is not valid for '.$op.'.');
        }
        $id = $input->getArgument('id');
        if (($op === 'create') !== ($id === null)) throw new WorkflowException('Run ID is required except for create.');
        $store = new RunStore($this->projectRoot($input));
        $now = gmdate('Y-m-d\TH:i:s\Z');
        if ($op === 'create') {
            $max = $input->getOption('max-roots');
            if (!$input->getOption('authorize-autonomous') || !is_string($max) || !preg_match('/\A[1-9][0-9]*\z/', $max) || strlen($max) > 3 || (int) $max > 256) throw new WorkflowException('Explicit authorization and canonical --max-roots (1–256) are required.');
            $id = 'R-'.bin2hex(random_bytes(16));
            $run = Run::fromArray([
                'version' => 1, 'id' => $id, 'autonomous' => true,
                'goal' => $input->getOption('goal'), 'success_criteria' => $input->getOption('criterion'),
                'permissions' => ['actions' => $input->getOption('action'), 'roots' => $input->getOption('root')],
                'limits' => ['expires_at' => $input->getOption('expires-at'), 'max_roots' => (int) $max],
                'status' => 'active', 'status_reason' => null, 'recovery' => null, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $store->create($run);
            $output->writeln($id, OutputInterface::OUTPUT_RAW);
            return self::SUCCESS;
        }
        if ($op === 'show') {
            $run = $store->load($id);
            $data = $run->toArray();
            $data['effective_status'] = $run->effectiveStatus(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
            $data['authority_usable'] = $data['effective_status'] === 'active';
            if ($input->getOption('json')) $this->writeJson($output, $data);
            else foreach ($data as $key => $value) $output->writeln($key.': '.json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);
            return self::SUCCESS;
        }
        $store->mutate($id, function (Run $run) use ($op, $input): Run {
            $data = $run->toArray();
            $data['updated_at'] = gmdate('Y-m-d\TH:i:s\Z');
            if ($op === 'report') {
                $status = $input->getOption('status');
                if (!in_array($status, ['blocked', 'interrupted', 'limit-exceeded'], true)) throw new WorkflowException('Invalid report status.');
                $data['status'] = $status;
                $data['status_reason'] = $input->getOption('reason');
                $data['recovery'] = $input->getOption('recovery');
            } elseif ($op === 'resume') {
                $data['status'] = 'active';
                $data['status_reason'] = null;
                $data['recovery'] = $input->getOption('recovery');
            } else {
                $data['status'] = 'revoked';
                $data['status_reason'] = $input->getOption('reason');
            }
            return Run::fromArray($data);
        });
        $output->writeln('Run '.$id.' '.$op.' recorded.', OutputInterface::OUTPUT_RAW);
        return self::SUCCESS;
    }
}
