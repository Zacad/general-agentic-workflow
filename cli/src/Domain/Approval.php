<?php

declare(strict_types=1);

namespace Workflow\Domain;

final class Approval
{
    public function __construct(
        public readonly string $at,
        public readonly string $by,
        public readonly string $scopeHash,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['at'] ?? ''),
            (string) ($data['by'] ?? ''),
            (string) ($data['scope_hash'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'at' => $this->at,
            'by' => $this->by,
            'scope_hash' => $this->scopeHash,
        ];
    }
}
