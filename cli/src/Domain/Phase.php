<?php

declare(strict_types=1);

namespace Workflow\Domain;

enum Phase: string
{
    case Plan = 'plan';
    case Explore = 'explore';
    case Design = 'design';
    case Implement = 'implement';
    case Verify = 'verify';
}
