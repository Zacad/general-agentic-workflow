<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Domain\Item;
use Workflow\Domain\Phase;
use Workflow\Domain\Status;
use Workflow\Domain\WorkflowException;

final class SetCommand extends BaseCommand
{
    /** @var array<string, string> */
    private const LIST_FIELDS = [
        'depends_on' => ',',
        'owns' => ',',
        'acceptance' => ';',
        'scope_in' => ';',
        'scope_out' => ';',
    ];

    public function __construct()
    {
        parent::__construct('set');
    }

    protected function configure(): void
    {
        $this->setDescription('Update mutable item fields. Usage: workflow set T-001 status=in-progress phase=plan')
            ->addArgument('id', InputArgument::REQUIRED, 'Item ID.')
            ->addArgument('assignments', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'key=value assignments.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = (string) $input->getArgument('id');
        $assignments = $input->getArgument('assignments');

        try {
            [$changed, $stale] = $this->store($input)->mutate($id, function (Item $item) use ($assignments): array {
                $changed = [];
                $seenLists = [];

                foreach ($assignments as $assignment) {
                    $parts = explode('=', (string) $assignment, 2);
                    if (count($parts) !== 2) {
                        throw new WorkflowException(sprintf('Expected key=value, got: %s', $assignment));
                    }

                    $key = trim($parts[0]);
                    $value = trim($parts[1]);

                    if (isset(self::LIST_FIELDS[$key])) {
                        if (isset($seenLists[$key])) {
                            throw new WorkflowException(sprintf(
                                "Duplicate field '%s' in one set call. Use '%s' between multiple values.",
                                $key,
                                self::LIST_FIELDS[$key],
                            ));
                        }
                        $seenLists[$key] = true;
                    }

                    match ($key) {
                        'status' => $item->status = $this->status($value),
                        'phase' => $item->phase = $this->phase($value),
                        'parent' => $item->parent = $this->nullable($value),
                        'title' => $item->title = $value,
                        'outcome' => $item->outcome = $value,
                        'priority' => $item->priority = (int) $value,
                        'depends_on' => $item->dependsOn = $this->list($value),
                        'owns' => $item->owns = $this->list($value),
                        'acceptance' => $item->acceptance = $this->list($value, ';'),
                        'scope_in' => $item->scopeIn = $this->list($value, ';'),
                        'scope_out' => $item->scopeOut = $this->list($value, ';'),
                        'standalone_reason' => $item->extra['standalone_reason'] = $value,
                        default => throw new WorkflowException(sprintf('Unsupported field: %s', $key)),
                    };

                    $changed[] = $key;
                }

                return [array_values(array_unique($changed)), $item->hasStaleApproval()];
            });
        } catch (WorkflowException $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');

            return self::INVALID;
        }

        $output->writeln(sprintf('Updated %s: %s', $id, implode(', ', $changed)));

        $item = $this->store($input)->load($id);
        foreach (self::LIST_FIELDS as $field => $separator) {
            if (!in_array($field, $changed, true)) {
                continue;
            }

            $values = match ($field) {
                'depends_on' => $item->dependsOn,
                'owns' => $item->owns,
                'acceptance' => $item->acceptance,
                'scope_in' => $item->scopeIn,
                'scope_out' => $item->scopeOut,
            };

            $output->writeln(sprintf('%s: %d value(s), separated by "%s"', $field, count($values), $separator));
        }

        $this->refreshBoard($input, $output);

        if ($stale) {
            $output->writeln(sprintf('<comment>warning: scope changed; approval is stale. Run "workflow approve %s".</comment>', $id));
        }

        return self::SUCCESS;
    }

    private function status(string $value): Status
    {
        $status = Status::tryFrom(strtolower($value));
        if ($status === null) {
            throw new WorkflowException('Invalid status: use draft, todo, in-progress, done or cancelled.');
        }

        return $status;
    }

    private function phase(string $value): Phase
    {
        $phase = Phase::tryFrom(strtolower($value));
        if ($phase === null) {
            throw new WorkflowException('Invalid phase: use plan, explore, design, implement or verify.');
        }

        return $phase;
    }

    private function nullable(string $value): ?string
    {
        return in_array($value, ['', 'none', '-'], true) ? null : $value;
    }

    /**
     * @return list<string>
     */
    private function list(string $value, string $separator = ','): array
    {
        return array_values(array_filter(array_map('trim', explode($separator, $value))));
    }
}
