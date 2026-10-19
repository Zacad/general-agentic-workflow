<?php

declare(strict_types=1);

namespace Workflow\Domain;

enum Phase: string
{
    case Explore = 'explore';
    case Plan = 'plan';
    case Implement = 'implement';
    case Verify = 'verify';
}
