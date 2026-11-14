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
        public readonly ?array $provenance = null,
    ) {
        if ($provenance !== null) {
            $keys = array_keys($provenance);
            sort($keys);
            if ($keys !== ['kind', 'run_id'] || ($provenance['kind'] ?? null) !== 'automated'
                || !is_string($provenance['run_id'] ?? null)
                || !preg_match('/\AR-[0-9a-f]{32}\z/', $provenance['run_id'])
                || $by !== 'automated' || $confirmation !== null) {
                throw new InvalidItemException('Invalid automated approval provenance.');
            }
        }
    }

    public static function fromArray(array $data): self
    {
        // One value object reads both hash keys: the design-approval block
        // reuses the record shape under its own design_hash key.
        $hashKey = array_key_exists('design_hash', $data) ? 'design_hash' : 'scope_hash';
        if (array_key_exists('provenance', $data) && !is_array($data['provenance'])) {
            throw new InvalidItemException('Invalid automated approval provenance.');
        }
        if (array_key_exists('provenance', $data) && array_key_exists('confirmation', $data)) {
            throw new InvalidItemException('Automated approval must not contain a confirmation.');
        }
        if (array_key_exists('provenance', $data)) {
            $keys = array_keys($data); sort($keys);
            $expected = ['at', 'by', $hashKey, 'provenance']; sort($expected);
            if ($keys !== $expected || !is_string($data['at']) || trim($data['at']) === ''
                || !is_string($data['by']) || !is_string($data[$hashKey])
                || !preg_match('/\A[0-9a-f]{64}\z/', $data[$hashKey])) {
                throw new InvalidItemException('Invalid automated approval record.');
            }
        }

        return new self(
            (string) ($data['at'] ?? ''),
            (string) ($data['by'] ?? ''),
            (string) ($data[$hashKey] ?? ''),
            self::nullableString($data['confirmation'] ?? null),
            $hashKey,
            $data['provenance'] ?? null,
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
        if ($this->provenance !== null) {
            $record['provenance'] = $this->provenance;
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
