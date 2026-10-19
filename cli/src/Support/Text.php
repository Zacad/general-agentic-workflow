<?php

declare(strict_types=1);

namespace Workflow\Support;

final class Text
{
    public static function oneLine(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    public static function truncate(string $value, int $maximum): string
    {
        $value = self::oneLine($value);

        return mb_strlen($value) <= $maximum ? $value : mb_substr($value, 0, $maximum - 1).'…';
    }

    /**
     * @param list<string> $values
     */
    public static function inlineList(array $values, string $empty = 'none'): string
    {
        return $values === [] ? $empty : implode(', ', $values);
    }
}
