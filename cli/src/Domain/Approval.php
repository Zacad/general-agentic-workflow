<?php

declare(strict_types=1);

namespace Workflow\Domain;

final class Approval
{
    /**
     * @param non-empty-string $hashKey YAML hash key this record renders under:
     *                                  scope records carry scope_hash, design
     *                                  records carry design_hash.
     */
    public function __construct(
        public readonly string $at,
        public readonly string $by,
        public readonly string $hash,
        public readonly ?string $confirmation = null,
        public readonly string $hashKey = 'scope_hash',
    ) {
    }

    public static function fromArray(array $data): self
    {
        // One value object reads both hash keys: the design-approval block
        // reuses the record shape under its own design_hash key.
        $hashKey = array_key_exists('design_hash', $data) ? 'design_hash' : 'scope_hash';

        return new self(
            (string) ($data['at'] ?? ''),
            (string) ($data['by'] ?? ''),
            (string) ($data[$hashKey] ?? ''),
            self::nullableString($data['confirmation'] ?? null),
            $hashKey,
        );
    }

    /** Named constructor for the design-approval block (design_hash key). */
    public static function forDesign(string $at, string $by, string $hash, ?string $confirmation = null): self
    {
        return new self($at, $by, $hash, $confirmation, 'design_hash');
    }

    public function toArray(): array
    {
        $record = [
            'at' => $this->at,
            'by' => $this->by,
            $this->hashKey => $this->hash,
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
