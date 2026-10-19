<?php

declare(strict_types=1);

namespace Workflow\Domain;

enum Kind: string
{
    case Epic = 'epic';
    case Task = 'task';
    case Subtask = 'subtask';

    public function prefix(): string
    {
        return match ($this) {
            self::Epic => 'E',
            self::Task => 'T',
            self::Subtask => 'S',
        };
    }
}
