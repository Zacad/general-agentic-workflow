<?php

declare(strict_types=1);

namespace Workflow\Domain;

final class Item
{
    /** Top-level item sections. Any other "## " heading is content or an error. */
    public const SECTIONS = ['Plan', 'Design', 'Progress', 'Decisions'];

    private const KNOWN_KEYS = [
        'id', 'kind', 'parent', 'title', 'status', 'phase', 'priority',
        'depends_on', 'owns', 'artifacts', 'approval', 'last_result',
        'verified_at', 'created', 'updated', 'outcome', 'scope', 'acceptance',
    ];

    /**
     * @param list<string>              $dependsOn
     * @param list<string>              $owns
     * @param list<string>              $artifacts
     * @param list<string>              $scopeIn
     * @param list<string>              $scopeOut
     * @param list<string>              $acceptance
     * @param array<string, mixed>|null $lastResult
     * @param array<string, mixed>      $extra
     */
    public function __construct(
        public string $id,
        public Kind $kind,
        public ?string $parent,
        public string $title,
        public Status $status,
        public Phase $phase,
        public int $priority,
        public array $dependsOn,
        public array $owns,
        public array $artifacts,
        public ?Approval $approval,
        public ?array $lastResult,
        public ?string $verifiedAt,
        public ?string $created,
        public ?string $updated,
        public string $outcome,
        public array $scopeIn,
        public array $scopeOut,
        public array $acceptance,
        public string $body,
        public array $extra = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, string $body): self
    {
        $id = trim((string) ($data['id'] ?? ''));
        if ($id === '') {
            throw new InvalidItemException('Item has no id.');
        }

        $kind = Kind::tryFrom((string) ($data['kind'] ?? ''));
        if ($kind === null) {
            throw new InvalidItemException(sprintf('Item %s has an invalid kind.', $id));
        }

        $status = Status::tryFrom((string) ($data['status'] ?? Status::Draft->value));
        if ($status === null) {
            throw new InvalidItemException(sprintf('Item %s has an invalid status.', $id));
        }

        $phase = Phase::tryFrom((string) ($data['phase'] ?? Phase::Explore->value));
        if ($phase === null) {
            throw new InvalidItemException(sprintf('Item %s has an invalid phase.', $id));
        }

        $scope = is_array($data['scope'] ?? null) ? $data['scope'] : [];
        $approval = is_array($data['approval'] ?? null) ? Approval::fromArray($data['approval']) : null;
        $lastResult = is_array($data['last_result'] ?? null) ? $data['last_result'] : null;

        return new self(
            id: $id,
            kind: $kind,
            parent: self::nullableString($data['parent'] ?? null),
            title: (string) ($data['title'] ?? ''),
            status: $status,
            phase: $phase,
            priority: (int) ($data['priority'] ?? 2),
            dependsOn: self::stringList($data['depends_on'] ?? []),
            owns: self::stringList($data['owns'] ?? []),
            artifacts: self::stringList($data['artifacts'] ?? []),
            approval: $approval,
            lastResult: $lastResult,
            verifiedAt: self::nullableString($data['verified_at'] ?? null),
            created: self::nullableString($data['created'] ?? null),
            updated: self::nullableString($data['updated'] ?? null),
            outcome: (string) ($data['outcome'] ?? ''),
            scopeIn: self::stringList($scope['in'] ?? []),
            scopeOut: self::stringList($scope['out'] ?? []),
            acceptance: self::stringList($data['acceptance'] ?? []),
            body: $body,
            extra: array_diff_key($data, array_flip(self::KNOWN_KEYS)),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $known = [
            'id' => $this->id,
            'kind' => $this->kind->value,
            'parent' => $this->parent,
            'title' => $this->title,
            'status' => $this->status->value,
            'phase' => $this->phase->value,
            'priority' => $this->priority,
            'depends_on' => array_values($this->dependsOn),
            'owns' => array_values($this->owns),
            'artifacts' => array_values($this->artifacts),
            'approval' => $this->approval?->toArray(),
            'last_result' => $this->lastResult,
            'verified_at' => $this->verifiedAt,
            'created' => $this->created,
            'updated' => $this->updated,
            'outcome' => $this->outcome,
            'scope' => [
                'in' => array_values($this->scopeIn),
                'out' => array_values($this->scopeOut),
            ],
            'acceptance' => array_values($this->acceptance),
        ];

        return array_merge($this->extra, $known);
    }

    public function scopeHash(): string
    {
        $depends = array_values(array_unique($this->dependsOn));
        sort($depends, SORT_STRING);

        $scopeIn = array_values(array_unique($this->scopeIn));
        sort($scopeIn, SORT_STRING);

        $scopeOut = array_values(array_unique($this->scopeOut));
        sort($scopeOut, SORT_STRING);

        $payload = [
            'kind' => $this->kind->value,
            'parent' => $this->parent,
            'title' => $this->title,
            'outcome' => $this->outcome,
            'scope' => ['in' => $scopeIn, 'out' => $scopeOut],
            'acceptance' => array_values($this->acceptance),
            'depends_on' => $depends,
        ];

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    public function isApproved(): bool
    {
        return $this->approval !== null && hash_equals($this->approval->scopeHash, $this->scopeHash());
    }

    public function hasStaleApproval(): bool
    {
        return $this->approval !== null && !$this->isApproved();
    }

    public function addArtifacts(array $paths): void
    {
        foreach ($paths as $path) {
            $path = trim((string) $path);
            if ($path !== '' && !in_array($path, $this->artifacts, true)) {
                $this->artifacts[] = $path;
            }
        }
    }

    public function section(string $name): string
    {
        if (!preg_match('/^##[ \t]+'.preg_quote($name, '/').'[ \t]*$/m', $this->body, $match, PREG_OFFSET_CAPTURE)) {
            return '';
        }

        $start = $match[0][1] + strlen($match[0][0]);
        $rest = substr($this->body, $start);

        if (preg_match('/'.self::sectionBoundary($name).'/m', $rest, $next, PREG_OFFSET_CAPTURE)) {
            $rest = substr($rest, 0, $next[0][1]);
        }

        return trim($rest);
    }

    /**
     * A section ends at the next known top-level heading other than itself.
     * A repeated heading of the same name is therefore part of the section,
     * so one replacement can collapse a duplicated section.
     */
    public static function sectionBoundary(string $name): string
    {
        $others = array_values(array_filter(
            self::SECTIONS,
            static fn (string $section): bool => $section !== $name,
        ));

        if ($others === []) {
            return '^##[ \t]+';
        }

        return '^##[ \t]+(?:'.implode('|', array_map(
            static fn (string $section): string => preg_quote($section, '/'),
            $others,
        )).')[ \t]*$';
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $list = [];
        foreach ($value as $entry) {
            if (is_scalar($entry) || $entry === null) {
                $text = trim((string) $entry);
                if ($text !== '') {
                    $list[] = $text;
                }
            }
        }

        return $list;
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
