<?php

declare(strict_types=1);

namespace Workflow\Domain;

enum Status: string
{
    case Draft = 'draft';
    case Todo = 'todo';
    case InProgress = 'in-progress';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function isOpen(): bool
    {
        return $this !== self::Done && $this !== self::Cancelled;
    }
}
