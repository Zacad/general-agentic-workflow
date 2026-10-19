<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Domain\Item;
use Workflow\Domain\Kind;
use Workflow\Domain\Phase;
use Workflow\Domain\Status;
use Workflow\Support\Clock;

final class NewCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('new');
    }

    protected function configure(): void
    {
        $this->setDescription('Create a draft work item.')
            ->addArgument('title', InputArgument::REQUIRED, 'Item title.')
            ->addOption('kind', null, InputOption::VALUE_REQUIRED, 'epic, task or subtask.', Kind::Task->value)
            ->addOption('parent', null, InputOption::VALUE_REQUIRED, 'Parent item ID (or none).')
            ->addOption('phase', null, InputOption::VALUE_REQUIRED, 'explore, plan, implement or verify.', Phase::Explore->value)
            ->addOption('priority', null, InputOption::VALUE_REQUIRED, 'Priority number; lower is sooner.', '2')
            ->addOption('outcome', null, InputOption::VALUE_REQUIRED, 'One-sentence observable outcome.', '')
            ->addOption('standalone-reason', null, InputOption::VALUE_REQUIRED, 'Why a parentless task stands alone.')
            ->addOption('owns', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Owned path (repeatable).')
            ->addOption('depends', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Dependency ID (repeatable).')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $store = $this->store($input);

        $kind = Kind::tryFrom(strtolower((string) $input->getOption('kind')));
        if ($kind === null) {
            $output->writeln('<error>Invalid --kind: use epic, task or subtask.</error>');

            return self::INVALID;
        }

        $phase = Phase::tryFrom(strtolower((string) $input->getOption('phase')));
        if ($phase === null) {
            $output->writeln('<error>Invalid --phase: use explore, plan, implement or verify.</error>');

            return self::INVALID;
        }

        $parent = trim((string) $input->getOption('parent'));
        if (in_array($parent, ['', 'none', '-'], true)) {
            $parent = null;
        }

        if ($parent !== null && !$store->exists($parent)) {
            $output->writeln(sprintf('<error>Parent does not exist: %s</error>', $parent));

            return self::INVALID;
        }

        $depends = array_values(array_filter(array_map('trim', $input->getOption('depends'))));
        foreach ($depends as $dependency) {
            if (!$store->exists($dependency)) {
                $output->writeln(sprintf('<error>Dependency does not exist: %s</error>', $dependency));

                return self::INVALID;
            }
        }

        $now = Clock::now();
        $body = "## Plan\n\n## Progress\n";

        $item = new Item(
            id: $store->nextId($kind),
            kind: $kind,
            parent: $parent,
            title: trim((string) $input->getArgument('title')),
            status: Status::Draft,
            phase: $phase,
            priority: (int) $input->getOption('priority'),
            dependsOn: $depends,
            owns: array_values(array_filter(array_map('trim', $input->getOption('owns')))),
            artifacts: [],
            approval: null,
            lastResult: null,
            verifiedAt: null,
            created: $now,
            updated: $now,
            outcome: trim((string) $input->getOption('outcome')),
            scopeIn: [],
            scopeOut: [],
            acceptance: [],
            body: $body,
        );

        $standaloneReason = trim((string) $input->getOption('standalone-reason'));
        if ($standaloneReason !== '') {
            $item->extra['standalone_reason'] = $standaloneReason;
        }

        $store->create($item);

        if ($input->getOption('json')) {
            $this->writeJson($output, ['id' => $item->id, 'status' => $item->status->value, 'path' => $store->itemPath($item->id)]);

            return self::SUCCESS;
        }

        $output->writeln(sprintf('Created %s (%s) at .workflow/items/%s/item.md', $item->id, $item->kind->value, $item->id));
        $output->writeln('Next: fill outcome/scope/acceptance, then run "workflow approve '.$item->id.'".');

        return self::SUCCESS;
    }
}
