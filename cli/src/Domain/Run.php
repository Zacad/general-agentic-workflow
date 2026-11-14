<?php

declare(strict_types=1);

namespace Workflow\Domain;

/** A validated snapshot, not a lock or a proof of the ancestry supplied by a caller. */
final class Run
{
    private const ACTIONS = ['scope-approve', 'design-approve', 'decide', 'accept-review'];
    private const STATUSES = ['active', 'blocked', 'interrupted', 'limit-exceeded', 'revoked'];

    private function __construct(private readonly array $data) {}

    public static function fromArray(array $data): self
    {
        self::keys($data, ['version', 'id', 'autonomous', 'goal', 'success_criteria', 'permissions', 'limits', 'status', 'status_reason', 'recovery', 'created_at', 'updated_at']);
        if ($data['version'] !== 1 || $data['autonomous'] !== true || !is_string($data['id']) || !preg_match('/\AR-[0-9a-f]{32}\z/', $data['id'])) {
            throw new WorkflowException('Invalid run version, ID or autonomous authorization.');
        }
        self::text($data['goal']);
        self::stringList($data['success_criteria']);
        if (!is_array($data['permissions']) || !is_array($data['limits'])) {
            throw new WorkflowException('Invalid run permissions or limits.');
        }
        self::keys($data['permissions'], ['actions', 'roots']);
        self::keys($data['limits'], ['expires_at', 'max_roots']);
        self::stringList($data['permissions']['actions']);
        self::stringList($data['permissions']['roots']);
        foreach ($data['permissions']['actions'] as $action) {
            if (!in_array($action, self::ACTIONS, true)) {
                throw new WorkflowException('Invalid run action.');
            }
        }
        foreach ($data['permissions']['roots'] as $root) {
            if (!preg_match('/\A[ETS]-[0-9]+\z/', $root)) {
                throw new WorkflowException('Invalid run root.');
            }
        }
        $max = $data['limits']['max_roots'];
        if (!is_int($max) || $max < 1 || $max > 256 || count($data['permissions']['roots']) > $max) {
            throw new WorkflowException('Invalid max_roots.');
        }
        self::timestamp($data['limits']['expires_at']);
        self::timestamp($data['created_at']);
        self::timestamp($data['updated_at']);
        if ($data['updated_at'] < $data['created_at'] || $data['limits']['expires_at'] <= $data['created_at']) {
            throw new WorkflowException('Invalid run timestamps.');
        }
        if (!is_string($data['status']) || !in_array($data['status'], self::STATUSES, true)) {
            throw new WorkflowException('Invalid run status.');
        }
        foreach (['status_reason', 'recovery'] as $field) {
            if ($data[$field] !== null) self::text($data[$field]);
        }
        if (($data['status'] === 'active') !== ($data['status_reason'] === null)
            || (in_array($data['status'], ['blocked', 'interrupted', 'limit-exceeded'], true) && $data['recovery'] === null)) {
            throw new WorkflowException('Invalid status reason or recovery.');
        }
        return new self($data);
    }

    public function toArray(): array { return $this->data; }

    public function effectiveStatus(\DateTimeImmutable $now): string
    {
        if ($this->data['status'] === 'revoked') return 'revoked';
        return $now >= new \DateTimeImmutable($this->data['limits']['expires_at']) ? 'expired' : $this->data['status'];
    }

    /** The caller must supply the complete, current, validated ancestry under the project lock. */
    public function assertAuthorized(string $action, array $ancestry, \DateTimeImmutable $now): void
    {
        self::stringList($ancestry);
        foreach ($ancestry as $id) {
            if (!preg_match('/\A[ETS]-[0-9]+\z/', $id)) throw new WorkflowException('Invalid ancestry.');
        }
        if ($this->effectiveStatus($now) !== 'active' || !in_array($action, $this->data['permissions']['actions'], true)
            || array_intersect($ancestry, $this->data['permissions']['roots']) === []) {
            throw new WorkflowException('Run authority denied.');
        }
    }

    private static function keys(array $data, array $keys): void
    {
        $actual = array_keys($data); sort($actual); sort($keys);
        if ($actual !== $keys) throw new WorkflowException('Missing or unknown run fields.');
    }

    private static function text(mixed $value): void
    {
        if (!is_string($value) || trim($value) === '' || strlen($value) > 16384 || !preg_match('//u', $value)) {
            throw new WorkflowException('Invalid run text.');
        }
    }

    private static function stringList(mixed $value): void
    {
        if (!is_array($value) || !array_is_list($value) || count($value) < 1 || count($value) > 256) throw new WorkflowException('Invalid run list.');
        foreach ($value as $entry) self::text($entry);
        if (count(array_unique($value, SORT_STRING)) !== count($value)) throw new WorkflowException('Duplicate run list entry.');
    }

    private static function timestamp(mixed $value): void
    {
        if (!is_string($value) || !preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z\z/', $value)) throw new WorkflowException('Invalid UTC timestamp.');
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $value, new \DateTimeZone('UTC'));
        if ($date === false || $date->format('Y-m-d\TH:i:s\Z') !== $value) throw new WorkflowException('Invalid UTC timestamp.');
    }
}
