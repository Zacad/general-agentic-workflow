<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Support\ChangeDetector;

final class ChangeCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('change');
    }

    protected function configure(): void
    {
        $this->setDescription('Detect and classify changes since last approval.')
            ->addArgument('id', InputArgument::REQUIRED, 'Item ID.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = (string) $input->getArgument('id');

        try {
            $item = $this->store($input)->load($id);
        } catch (\Exception $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');

            return self::INVALID;
        }

        $detector = new ChangeDetector();
        $changes = $detector->detect($item, $item->lastApprovedScope);

        if ($input->getOption('json')) {
            $this->writeJson($output, [
                'id' => $id,
                'has_approval' => $item->approval !== null,
                'has_material' => $changes['has_material'],
                'material' => $changes['material'],
                'non_material' => $changes['non_material'],
                'summary' => $changes['summary'],
                'recommendation' => $changes['has_material'] ? 'full-approval' : ($changes['non_material'] !== [] ? 'delta-approval' : 'no-approval-needed'),
            ]);

            return self::SUCCESS;
        }

        if ($item->approval === null) {
            $output->writeln(sprintf('Item %s has no previous approval. Full approval required.', $id));

            return self::SUCCESS;
        }

        if ($changes['material'] === [] && $changes['non_material'] === []) {
            $output->writeln(sprintf('No changes detected for %s since last approval.', $id));

            return self::SUCCESS;
        }

        $output->writeln(sprintf('Changes for %s since last approval:', $id));
        $output->writeln('');

        if ($changes['material'] !== []) {
            $output->writeln('<comment>Material changes (require full re-approval):</comment>');
            foreach ($changes['material'] as $change) {
                $output->writeln('  • '.$change);
            }
            $output->writeln('');
        }

        if ($changes['non_material'] !== []) {
            $output->writeln('<info>Non-material changes (delta approval sufficient):</info>');
            foreach ($changes['non_material'] as $change) {
                $output->writeln('  • '.$change);
            }
            $output->writeln('');
        }

        if ($changes['has_material']) {
            $output->writeln(sprintf('Recommendation: Run "workflow approve %s" for full re-approval.', $id));
        } else {
            $output->writeln(sprintf('Recommendation: Run "workflow approve %s --delta" for delta approval.', $id));
        }

        return self::SUCCESS;
    }
}
