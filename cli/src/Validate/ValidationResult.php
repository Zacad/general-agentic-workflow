<?php

declare(strict_types=1);

namespace Workflow\Validate;

final class ValidationResult
{
    /** @var list<string> */
    public array $errors = [];

    /** @var list<string> */
    public array $warnings = [];

    public function error(string $message): void
    {
        $this->errors[] = $message;
    }

    public function warning(string $message): void
    {
        $this->warnings[] = $message;
    }

    public function ok(): bool
    {
        return $this->errors === [];
    }
}
