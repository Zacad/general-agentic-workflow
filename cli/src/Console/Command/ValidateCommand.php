<?php

declare(strict_types=1);

namespace Workflow\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Validate\ItemValidator;

final class ValidateCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('validate');
    }

    protected function configure(): void
    {
        $this->setDescription('Check record structure, dependencies, approvals and file layout.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $store = $this->store($input);
        $result = (new ItemValidator($store))->validate();

        if ($input->getOption('json')) {
            $this->writeJson($output, [
                'ok' => $result->ok(),
                'errors' => $result->errors,
                'warnings' => $result->warnings,
            ]);
        } else {
            foreach ($result->errors as $error) {
                $output->writeln('<error>ERROR</error> '.$error);
            }
            foreach ($result->warnings as $warning) {
                $output->writeln('<comment>WARN</comment>  '.$warning);
            }

            if ($result->ok()) {
                $output->writeln(sprintf(
                    'OK (%d warning%s)',
                    count($result->warnings),
                    count($result->warnings) === 1 ? '' : 's',
                ));
            } else {
                $output->writeln(sprintf('FAILED (%d error%s)', count($result->errors), count($result->errors) === 1 ? '' : 's'));
            }
        }

        return $result->ok() ? self::SUCCESS : self::FAILURE;
    }
}
