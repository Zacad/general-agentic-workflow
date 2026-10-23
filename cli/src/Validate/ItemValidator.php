<?php

declare(strict_types=1);

namespace Workflow\Validate;

use Workflow\Domain\FrontMatter;
use Workflow\Domain\InvalidItemException;
use Workflow\Domain\Item;
use Workflow\Domain\ItemLifecycle;
use Workflow\Domain\Kind;
use Workflow\Read\ProjectDocuments;
use Workflow\Store\ItemStore;

final class ItemValidator
{
    public function __construct(private readonly ItemStore $store)
    {
    }

    public function validate(): ValidationResult
    {
        $result = new ValidationResult();
        $workflow = $this->store->workflowDir();

        if (!is_dir($workflow)) {
            $result->error('No .workflow directory found. Run "workflow init".');

            return $result;
        }

        foreach (['PRD.md', 'Architecture.md'] as $document) {
            if (!is_file($workflow.'/'.$document)) {
                $result->warning(sprintf('.workflow/%s is missing.', $document));
            }
        }

        $items = [];
        $records = [];
        foreach ($this->store->ids() as $directoryId) {
            $itemDirectory = $this->store->itemsDir().'/'.$directoryId;
            $this->validateDirectory($itemDirectory, $directoryId, $result);

            $path = $itemDirectory.'/item.md';
            if (!is_file($path)) {
                $result->error(sprintf('%s: item.md is missing.', $directoryId));
                continue;
            }

            $raw = file_get_contents($path);
            if ($raw === false) {
                $result->error(sprintf('%s: item.md is unreadable.', $directoryId));
                continue;
            }

            try {
                $parsed = FrontMatter::parse($raw);
                $item = Item::fromArray($parsed['data'], $parsed['body']);
            } catch (InvalidItemException $exception) {
                $result->error(sprintf('%s: %s', $directoryId, $exception->getMessage()));
                continue;
            }

            if ($item->id !== $directoryId) {
                $result->error(sprintf('%s: front matter id is %s.', $directoryId, $item->id));
            }

            $items[$item->id] = $item;
            $records[] = $item;
        }

        $structure = (new StructureValidator())->validate($records);
        $result->errors = array_merge($result->errors, $structure->errors);
        $result->warnings = array_merge($result->warnings, $structure->warnings);
        $this->validateApprovals($items, $result);
        $this->validateDesignApprovals($items, $result);
        $this->validatePlans($items, $result);
        $this->validateDesigns($items, $result);
        $this->validateParentDesigns($items, $result);
        $this->validateDiscoveryOrder($items, $result);
        $this->validateProjectDocuments($items, $result);
        $this->validateSections($items, $result);
        $this->validateArtifacts($items, $result);
        $this->validateArtifactOwnership($items, $result);
        $this->validateOwnership($items, $result);

        return $result;
    }

    private function validateDirectory(string $directory, string $id, ValidationResult $result): void
    {
        $entries = @scandir($directory);
        if ($entries === false) {
            $result->error(sprintf('%s: item directory is unreadable.', $id));

            return;
        }

        foreach ($entries as $entry) {
            if (in_array($entry, ['.', '..', 'item.md', 'outputs'], true)) {
                continue;
            }
            $result->error(sprintf('%s: unexpected entry "%s" (only item.md and outputs/ are allowed).', $id, $entry));
        }

        if (is_file($directory.'/outputs')) {
            $result->error(sprintf('%s: outputs must be a directory, not a file.', $id));
        }
    }

    /**
     * @param array<string, Item> $items
     */
    private function validateApprovals(array $items, ValidationResult $result): void
    {
        foreach ($items as $item) {
            if ($item->approval === null) {
                if ($item->status->value === 'in-progress' || $item->status->value === 'done') {
                    $result->error(sprintf('%s: %s without approval.', $item->id, $item->status->value));
                } elseif ($item->status->value === 'todo') {
                    $result->warning(sprintf('%s: not approved yet.', $item->id));
                }
            } elseif ($item->hasStaleApproval()) {
                $result->error(sprintf('%s: approval is stale (scope changed); run "workflow approve %s".', $item->id, $item->id));
            }

            if ($item->approval !== null && $item->status->isOpen()
                && trim((string) ($item->approval->confirmation ?? '')) === '') {
                $result->warning(sprintf(
                    '%s: approval has no recorded confirmation quote — approval must be explicit user confirmation.',
                    $item->id,
                ));
            }

            if ($item->status->value === 'done' && $item->verifiedAt === null) {
                $result->warning(sprintf('%s: done without a verifier receipt for the current target.', $item->id));
            } elseif ($item->verifiedAt !== null && !ItemLifecycle::hasCurrentVerification($item)) {
                $result->warning(sprintf('%s: verification is stale or lacks a matching successful verifier receipt.', $item->id));
            } elseif ($item->status->isOpen() && $item->verifiedAt === null
                && ($item->lastResult['role'] ?? null) === 'verifier' && ($item->lastResult['status'] ?? null) === 'done') {
                $result->warning(sprintf('%s: no current verification despite an earlier verifier receipt; verify the current target again.', $item->id));
            }
        }

        foreach ($items as $item) {
            if ($item->status->value !== 'done') {
                continue;
            }

            $openChildren = [];
            foreach ($items as $child) {
                if ($child->parent === $item->id && in_array($child->status->value, ['draft', 'todo', 'in-progress'], true)) {
                    $openChildren[] = $child->id;
                }
            }

            if ($openChildren !== []) {
                $result->warning(sprintf(
                    '%s: done while children are open: %s',
                    $item->id,
                    implode(', ', $openChildren),
                ));
            }
        }
    }

    /**
     * Design-approval diagnostics mirror the scope-approval ones, scoped to
     * tasks and subtasks: done records are historical and stay fine without a
     * block, drafts are not gated yet.
     *
     * @param array<string, Item> $items
     */
    private function validateDesignApprovals(array $items, ValidationResult $result): void
    {
        foreach ($items as $item) {
            if (!in_array($item->kind, [Kind::Task, Kind::Subtask], true) || $item->status->value === 'done') {
                continue;
            }

            if ($item->designApproval === null) {
                if ($item->status->value === 'in-progress') {
                    $result->error(sprintf('%s: %s without design approval.', $item->id, $item->status->value));
                } elseif ($item->status->value === 'todo') {
                    $result->warning(sprintf('%s: design not approved yet.', $item->id));
                }
            } elseif ($item->hasStaleDesignApproval()) {
                $result->error(sprintf(
                    '%s: design approval is stale (design changed); run "workflow design %s --approve".',
                    $item->id,
                    $item->id,
                ));
            }

            if ($item->designApproval !== null && $item->status->isOpen()
                && trim((string) ($item->designApproval->confirmation ?? '')) === '') {
                $result->warning(sprintf(
                    '%s: design approval has no recorded confirmation quote — approval must be explicit user confirmation.',
                    $item->id,
                ));
            }
        }
    }

    /**
     * @param array<string, Item> $items
     */
    private function validatePlans(array $items, ValidationResult $result): void
    {
        foreach ($items as $item) {
            if (!in_array($item->status->value, ['todo', 'in-progress'], true)) {
                continue;
            }
            if (!in_array($item->phase->value, ['explore', 'design', 'implement', 'verify'], true)) {
                continue;
            }
            if (trim($item->section('Plan')) !== '') {
                continue;
            }

            $result->warning(sprintf(
                '%s: %s without a plan; run "workflow plan %s".',
                $item->id,
                $item->phase->value,
                $item->id,
            ));
        }
    }

    /**
     * @param array<string, Item> $items
     */
    private function validateDesigns(array $items, ValidationResult $result): void
    {
        foreach ($items as $item) {
            if (!in_array($item->status->value, ['todo', 'in-progress'], true)) {
                continue;
            }
            if (!in_array($item->phase->value, ['implement', 'verify'], true)) {
                continue;
            }
            if (trim($item->section('Design')) !== '') {
                continue;
            }

            $result->warning(sprintf(
                '%s: %s without a design; run "workflow design %s".',
                $item->id,
                $item->phase->value,
                $item->id,
            ));
        }
    }

    /**
     * Children approved or dispatched without a parent scope map lose the
     * canonical decomposition record; advisory only.
     *
     * @param array<string, Item> $items
     */
    private function validateParentDesigns(array $items, ValidationResult $result): void
    {
        foreach ($items as $item) {
            if (!in_array($item->status->value, ['todo', 'in-progress'], true) || $item->parent === null) {
                continue;
            }

            $parent = $items[$item->parent] ?? null;
            if ($parent !== null && trim($parent->section('Design')) === '') {
                $result->warning(sprintf(
                    '%s: parent %s has no recorded Design (canonical scope map).',
                    $item->id,
                    $item->parent,
                ));
            }
        }
    }

    /**
     * Pre-approval discovery is legitimate, but evidence recorded as an
     * explorer receipt after the approval timestamp suggests the packet was
     * approved before the evidence existed.
     *
     * @param array<string, Item> $items
     */
    private function validateDiscoveryOrder(array $items, ValidationResult $result): void
    {
        foreach ($items as $item) {
            $approvedAt = self::isoTimestamp($item->approval?->at);
            if ($approvedAt === null) {
                continue;
            }

            if (preg_match_all('/^- ([^ \n]+) · explorer · ([a-z-]+)$/m', $item->section('Progress'), $matches) === 0) {
                continue;
            }

            foreach ($matches[1] as $index => $timestamp) {
                $receiptAt = self::isoTimestamp($timestamp);
                if ($receiptAt === null) {
                    continue;
                }
                if ($receiptAt < $approvedAt) {
                    $result->warning(sprintf(
                        '%s: explorer receipt predates approval (%s); discovery ran before the epic was approved.',
                        $item->id,
                        $timestamp,
                    ));
                }
            }
        }
    }

    /**
     * Template-state project documents with recorded epics: advisory only.
     * Missing documents keep their pre-existing unconditional warning.
     *
     * @param array<string, Item> $items
     */
    private function validateProjectDocuments(array $items, ValidationResult $result): void
    {
        $epics = [];
        foreach ($items as $item) {
            if ($item->kind->value === 'epic' && $item->status->value !== 'cancelled') {
                $epics[] = $item->id;
            }
        }

        if ($epics === []) {
            return;
        }

        $documents = new ProjectDocuments($this->store->root());
        foreach ([
            'PRD.md' => "before the first epic's scope approval",
            'Architecture.md' => 'at epic design',
        ] as $document => $moment) {
            if ($documents->state($document) === 'template') {
                $result->warning(sprintf(
                    '.workflow/%s still contains template placeholders; record product truth there (fill moment: %s; epics: %s).',
                    $document,
                    $moment,
                    implode(', ', $epics),
                ));
            }
        }
    }

    /**
     * A worker receipt naming artifacts outside the item's owns paths: the
     * written deliverable may be untracked ownership. Advisory only; missing
     * artifacts stay with validateArtifacts.
     *
     * @param array<string, Item> $items
     */
    private function validateArtifactOwnership(array $items, ValidationResult $result): void
    {
        foreach ($items as $item) {
            if (!in_array($item->status->value, ['todo', 'in-progress'], true)) {
                continue;
            }
            if (($item->lastResult['role'] ?? null) !== 'worker') {
                continue;
            }

            foreach ($item->artifacts as $artifact) {
                $relative = $this->projectArtifact($artifact, $item);
                if ($relative === null) {
                    continue;
                }

                $covered = false;
                foreach ($item->owns as $owned) {
                    if (self::pathsOverlap($relative, $owned)) {
                        $covered = true;
                        break;
                    }
                }

                if (!$covered) {
                    $result->warning(sprintf(
                        '%s: artifact %s is outside owns (was it written? add to owns via set, or correct the artifact).',
                        $item->id,
                        $artifact,
                    ));
                }
            }
        }
    }

    /**
     * Resolve a non-URL artifact to a project-root-relative path it actually
     * names, excluding .workflow content (outputs and records are internal).
     * Unresolvable paths return null; existence is a separate diagnostic.
     */
    private function projectArtifact(string $artifact, Item $item): ?string
    {
        if ($artifact === ''
            || str_starts_with($artifact, 'http://')
            || str_starts_with($artifact, 'https://')) {
            return null;
        }

        $root = rtrim($this->store->root(), '/').'/';
        $normalized = ltrim($artifact, '/');
        $candidates = [
            $root.$normalized,
            rtrim($this->store->workflowDir(), '/').'/'.$normalized,
            rtrim($this->store->itemsDir().'/'.$item->id, '/').'/'.$normalized,
        ];

        foreach ($candidates as $candidate) {
            if (!file_exists($candidate) || !str_starts_with($candidate, $root)) {
                continue;
            }

            $relative = substr($candidate, strlen($root));
            if ($relative === '' || $relative === '.workflow' || str_starts_with($relative, '.workflow/')) {
                return null;
            }

            return $relative;
        }

        return null;
    }

    /**
     * Strict ISO-8601 timestamps only; malformed input compares never.
     *
     * @return int|null Unix timestamp, or null when the value is not ISO.
     */
    private static function isoTimestamp(?string $value): ?int
    {
        if ($value === null || preg_match(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/',
            trim($value),
        ) !== 1) {
            return null;
        }

        try {
            return (int) (new \DateTimeImmutable(trim($value)))->format('U');
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @param array<string, Item> $items
     */
    private function validateSections(array $items, ValidationResult $result): void
    {
        foreach ($items as $item) {
            preg_match_all('/^##[ \t]+(.+?)[ \t]*$/m', $item->body, $matches);
            $headings = array_map(
                static fn (string $heading): string => trim($heading),
                $matches[1] ?? [],
            );
            $counts = array_count_values($headings);

            foreach (Item::SECTIONS as $known) {
                $count = $counts[$known] ?? 0;
                if ($count > 1) {
                    $result->warning(sprintf(
                        '%s: ## %s appears %d times; expected one section. Re-run the command with the merged content.',
                        $item->id,
                        $known,
                        $count,
                    ));
                }
            }

            foreach (array_keys($counts) as $heading) {
                if (!in_array($heading, Item::SECTIONS, true)) {
                    $result->warning(sprintf(
                        '%s: unexpected top-level heading "## %s"; use ### for subsections.',
                        $item->id,
                        $heading,
                    ));
                }
            }
        }
    }

    /**
     * @param array<string, Item> $items
     */
    private function validateArtifacts(array $items, ValidationResult $result): void
    {
        foreach ($items as $item) {
            $itemDirectory = $this->store->itemsDir().'/'.$item->id;
            foreach ($item->artifacts as $artifact) {
                if (str_starts_with($artifact, 'http://') || str_starts_with($artifact, 'https://')) {
                    continue;
                }

                $candidates = [
                    $this->store->root().'/'.ltrim($artifact, '/'),
                    $this->store->workflowDir().'/'.ltrim($artifact, '/'),
                    $itemDirectory.'/'.ltrim($artifact, '/'),
                ];

                $found = false;
                foreach ($candidates as $candidate) {
                    if (file_exists($candidate)) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $result->warning(sprintf('%s: artifact not found: %s', $item->id, $artifact));
                }
            }
        }
    }

    /**
     * @param array<string, Item> $items
     */
    private function validateOwnership(array $items, ValidationResult $result): void
    {
        $open = array_filter(
            $items,
            static fn (Item $item): bool => in_array($item->status->value, ['in-progress', 'todo'], true),
        );

        $ids = array_keys($open);
        $count = count($ids);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $left = $open[$ids[$i]];
                $right = $open[$ids[$j]];
                if (self::dependencyOrdered($left, $right, $items)) {
                    continue;
                }
                $shared = $this->sharedOwnedPaths($left, $right);
                if ($shared !== []) {
                    $result->warning(sprintf(
                        '%s and %s both own %s (parallel overlap).',
                        $left->id,
                        $right->id,
                        implode(', ', $shared),
                    ));
                }
            }
        }
    }

    /**
     * Dependency-ordered pairs are not parallel ownership conflicts.
     *
     * @param array<string, Item> $items
     */
    public static function dependencyOrdered(Item $left, Item $right, array $items): bool
    {
        return self::hasDependencyPath($left, $right, $items) || self::hasDependencyPath($right, $left, $items);
    }

    /**
     * @param array<string, Item> $items
     * @param list<string>       $visited
     */
    private static function hasDependencyPath(Item $from, Item $target, array $items, array $visited = []): bool
    {
        if (in_array($from->id, $visited, true)) {
            return false;
        }
        $visited[] = $from->id;

        foreach ($from->dependsOn as $dependencyId) {
            if ($dependencyId === $target->id) {
                return true;
            }
            $dependency = $items[$dependencyId] ?? null;
            if ($dependency !== null && self::hasDependencyPath($dependency, $target, $items, $visited)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function sharedOwnedPaths(Item $left, Item $right): array
    {
        $shared = [];
        foreach ($left->owns as $leftPath) {
            foreach ($right->owns as $rightPath) {
                if (self::pathsOverlap($leftPath, $rightPath)) {
                    $shared[] = $leftPath;
                    break;
                }
            }
        }

        return array_values(array_unique($shared));
    }

    public static function pathsOverlap(string $left, string $right): bool
    {
        $left = self::ownedPathComponents($left);
        $right = self::ownedPathComponents($right);

        if ($left === null || $right === null) {
            return false;
        }

        // A nonempty root spelling owns in-project descendants, not paths
        // escaping above it. Empty input, unlike "." or "/", owns nothing.
        if ($left === []) {
            return ($right[0] ?? null) !== '..';
        }
        if ($right === []) {
            return ($left[0] ?? null) !== '..';
        }

        $length = min(count($left), count($right));

        return array_slice($left, 0, $length) === array_slice($right, 0, $length);
    }

    /** @return list<string>|null Empty list is root; null is absent ownership. */
    private static function ownedPathComponents(string $path): ?array
    {
        if ($path === '') {
            return null;
        }

        $components = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $component) {
            if ($component === '' || $component === '.') {
                continue;
            }
            if ($component === '..' && $components !== [] && end($components) !== '..') {
                array_pop($components);
            } else {
                $components[] = $component;
            }
        }

        return $components;
    }
}
