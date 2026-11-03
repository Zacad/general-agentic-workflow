<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Domain\Item;
use Workflow\Domain\WorkflowException;

final class ExternalDepsCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('external-deps');
    }

    protected function configure(): void
    {
        $this->setDescription('Manage external dependencies for an item.')
            ->addArgument('id', InputArgument::REQUIRED, 'Item ID.')
            ->addArgument('action', InputArgument::REQUIRED, 'Action: add, update, remove, list.')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Dependency type (e.g., pr-review, stakeholder).')
            ->addOption('description', null, InputOption::VALUE_REQUIRED, 'Dependency description.')
            ->addOption('status', null, InputOption::VALUE_REQUIRED, 'Dependency status: pending, satisfied, waived.')
            ->addOption('index', null, InputOption::VALUE_REQUIRED, 'Index for update/remove (1-based).')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = (string) $input->getArgument('id');
        $action = (string) $input->getArgument('action');

        try {
            $store = $this->store($input);
            $result = match ($action) {
                'add' => $this->add($input, $id, $store),
                'update' => $this->update($input, $id, $store),
                'remove' => $this->remove($input, $id, $store),
                'list' => $this->list($id, $store),
                default => throw new WorkflowException('Invalid action: use add, update, remove, or list.'),
            };
        } catch (WorkflowException $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');

            return self::INVALID;
        }

        if ($input->getOption('json')) {
            $this->writeJson($output, $result);

            return self::SUCCESS;
        }

        if ($action === 'list') {
            if ($result['dependencies'] === []) {
                $output->writeln('No external dependencies.');
            } else {
                $output->writeln(sprintf('External dependencies (%d):', count($result['dependencies'])));
                foreach ($result['dependencies'] as $index => $dep) {
                    $output->writeln(sprintf(
                        '  %d. [%s] %s (%s)',
                        $index + 1,
                        $dep['status'],
                        $dep['description'],
                        $dep['type']
                    ));
                }
            }
        } else {
            $output->writeln(sprintf('%s external dependency for %s', ucfirst($action), $id));
        }

        return self::SUCCESS;
    }

    /**
     * @return array{dependencies: list<array{type: string, description: string, status: string}>}
     */
    private function list(string $id, \Workflow\Store\ItemStore $store): array
    {
        $item = $store->load($id);

        return ['dependencies' => $item->externalDependencies];
    }

    /**
     * @return array{dependencies: list<array{type: string, description: string, status: string}>}
     */
    private function add(InputInterface $input, string $id, \Workflow\Store\ItemStore $store): array
    {
        $type = trim((string) $input->getOption('type'));
        $description = trim((string) $input->getOption('description'));
        $status = trim((string) $input->getOption('status'));

        if ($type === '' || $description === '') {
            throw new WorkflowException('--type and --description are required for add.');
        }

        if ($status === '') {
            $status = 'pending';
        }

        if (!in_array($status, ['pending', 'satisfied', 'waived'], true)) {
            throw new WorkflowException('Invalid status: use pending, satisfied, or waived.');
        }

        $store->mutate($id, function (Item $item) use ($type, $description, $status): void {
            $item->externalDependencies[] = [
                'type' => $type,
                'description' => $description,
                'status' => $status,
            ];
        });

        return $this->list($id, $store);
    }

    /**
     * @return array{dependencies: list<array{type: string, description: string, status: string}>}
     */
    private function update(InputInterface $input, string $id, \Workflow\Store\ItemStore $store): array
    {
        $index = (int) $input->getOption('index');
        $status = trim((string) $input->getOption('status'));

        if ($index < 1) {
            throw new WorkflowException('--index is required and must be >= 1.');
        }

        if ($status === '' || !in_array($status, ['pending', 'satisfied', 'waived'], true)) {
            throw new WorkflowException('--status is required and must be pending, satisfied, or waived.');
        }

        $store->mutate($id, function (Item $item) use ($index, $status): void {
            $idx = $index - 1;
            if (!isset($item->externalDependencies[$idx])) {
                throw new WorkflowException(sprintf('No dependency at index %d.', $index));
            }
            $item->externalDependencies[$idx]['status'] = $status;
        });

        return $this->list($id, $store);
    }

    /**
     * @return array{dependencies: list<array{type: string, description: string, status: string}>}
     */
    private function remove(InputInterface $input, string $id, \Workflow\Store\ItemStore $store): array
    {
        $index = (int) $input->getOption('index');

        if ($index < 1) {
            throw new WorkflowException('--index is required and must be >= 1.');
        }

        $store->mutate($id, function (Item $item) use ($index): void {
            $idx = $index - 1;
            if (!isset($item->externalDependencies[$idx])) {
                throw new WorkflowException(sprintf('No dependency at index %d.', $index));
            }
            array_splice($item->externalDependencies, $idx, 1);
        });

        return $this->list($id, $store);
    }
}
