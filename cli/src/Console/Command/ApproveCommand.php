<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Domain\Approval;
use Workflow\Domain\Item;
use Workflow\Domain\Status;
use Workflow\Support\Clock;
use Workflow\Support\Text;

final class ApproveCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('approve');
    }

    protected function configure(): void
    {
        $this->setDescription('Record approval of the current item scope.')
            ->addArgument('id', InputArgument::REQUIRED, 'Item ID.')
            ->addOption('by', null, InputOption::VALUE_REQUIRED, 'Who approved.', 'user')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = (string) $input->getArgument('id');
        $by = (string) $input->getOption('by');

        $result = $this->store($input)->mutate($id, function (Item $item) use ($by): array {
            $hash = $item->scopeHash();
            $item->approval = new Approval(Clock::now(), $by, $hash);
            if ($item->status === Status::Draft) {
                $item->status = Status::Todo;
            }

            return ['hash' => $hash, 'status' => $item->status->value];
        });
        $this->refreshBoard($input, $output);

        if ($input->getOption('json')) {
            $this->writeJson($output, ['id' => $id, 'by' => $by, 'scope_hash' => $result['hash'], 'status' => $result['status']]);

            return self::SUCCESS;
        }

        $item = $this->store($input)->load($id);

        $output->writeln(sprintf('Approved %s by %s', $id, $by));
        $output->writeln('kind: '.$item->kind->value);
        $output->writeln('parent: '.($item->parent ?? 'none'));
        $output->writeln('title: '.$item->title);
        $output->writeln('outcome: '.($item->outcome !== '' ? $item->outcome : '(not set)'));
        $output->writeln('scope in: '.Text::inlineList($item->scopeIn));
        $output->writeln('scope out: '.Text::inlineList($item->scopeOut));
        $output->writeln('acceptance:');
        if ($item->acceptance === []) {
            $output->writeln('  (not set)');
        } else {
            foreach ($item->acceptance as $index => $criterion) {
                $output->writeln(sprintf('  %d. %s', $index + 1, $criterion));
            }
        }
        $output->writeln('depends: '.Text::inlineList($item->dependsOn));
        $output->writeln('owns: '.Text::inlineList($item->owns));
        $output->writeln('hash: '.$result['hash']);
        $output->writeln('status: '.$result['status']);

        return self::SUCCESS;
    }
}
