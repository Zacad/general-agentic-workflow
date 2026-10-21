<?php

declare(strict_types=1);

namespace Workflow\Support;

use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Domain\Item;

final class ScopeOutput
{
    /**
     * Actual values, not the sorted/deduplicated identity used by scopeHash().
     * Owned paths are context, not part of the approved scope.
     *
     * @return array<string, mixed>
     */
    public static function scope(Item $item): array
    {
        return [
            'kind' => $item->kind->value,
            'parent' => $item->parent,
            'title' => $item->title,
            'outcome' => $item->outcome,
            'scope' => ['in' => $item->scopeIn, 'out' => $item->scopeOut],
            'acceptance' => $item->acceptance,
            'depends_on' => $item->dependsOn,
        ];
    }

    /**
     * @param array<string, mixed> $scope
     * @param list<string>         $owns
     */
    public static function write(OutputInterface $output, array $scope, array $owns, string $indent = ''): void
    {
        foreach ([
            'kind' => $scope['kind'],
            'parent' => $scope['parent'],
            'title' => $scope['title'],
            'outcome' => $scope['outcome'],
            'scope in' => $scope['scope']['in'],
            'scope out' => $scope['scope']['out'],
            'acceptance' => $scope['acceptance'],
            'depends' => $scope['depends_on'],
            'owns' => $owns,
        ] as $label => $value) {
            $output->writeln($indent.$label.': '.self::literal($value), OutputInterface::OUTPUT_RAW);
        }
    }

    /**
     * Bare simple strings stay readable; all other strings and lists are JSON
     * literals. The null marker cannot collide with a bare string.
     */
    public static function literal(string|array|null $value): string
    {
        if ($value === null) {
            return 'none (null)';
        }
        if (is_string($value) && trim($value) === $value
            && preg_match('/\A[A-Za-z][A-Za-z0-9 ._\/-]*\z/', $value) === 1
            && !in_array($value, ['null', 'true', 'false'], true)) {
            return $value;
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
