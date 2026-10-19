<?php

declare(strict_types=1);

namespace Workflow\Support;

final class Clock
{
    public static function now(): string
    {
        return gmdate('c');
    }

    public static function age(?string $iso): string
    {
        if ($iso === null || $iso === '') {
            return '';
        }

        try {
            $then = new \DateTimeImmutable($iso);
        } catch (\Exception) {
            return '';
        }

        $seconds = max(0, time() - $then->getTimestamp());
        if ($seconds < 60) {
            return '<1m';
        }
        if ($seconds < 3600) {
            return intdiv($seconds, 60).'m';
        }
        if ($seconds < 86400) {
            return intdiv($seconds, 3600).'h';
        }

        return intdiv($seconds, 86400).'d';
    }
}
