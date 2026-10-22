<?php

declare(strict_types=1);

namespace Workflow\Domain;

final class Approval
{
    public function __construct(
        public readonly string $at,
        public readonly string $by,
        public readonly string $scopeHash,
        public readonly ?string $confirmation = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['at'] ?? ''),
            (string) ($data['by'] ?? ''),
            (string) ($data['scope_hash'] ?? ''),
            self::nullableString($data['confirmation'] ?? null),
        );
    }

    public function toArray(): array
    {
        $record = [
            'at' => $this->at,
            'by' => $this->by,
            'scope_hash' => $this->scopeHash,
        ];

        // Old records without a confirmation round-trip unchanged.
        if ($this->confirmation !== null) {
            $record['confirmation'] = $this->confirmation;
        }

        return $record;
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
