<?php

declare(strict_types=1);

namespace Workflow\Validate;

use Workflow\Domain\FrontMatter;
use Workflow\Domain\InvalidItemException;
use Workflow\Domain\Item;
use Workflow\Domain\Kind;
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

            if (preg_match('/^'.preg_quote($item->kind->prefix(), '/').'-\d+$/', $item->id) !== 1) {
                $result->error(sprintf('%s: id does not match kind %s.', $item->id, $item->kind->value));
            }

            $items[$item->id] = $item;
        }

        $this->validateParents($items, $result);
        $this->validateDependencies($items, $result);
        $this->validateApprovals($items, $result);
        $this->validatePlans($items, $result);
        $this->validateDesigns($items, $result);
        $this->validateArtifacts($items, $result);
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
    private function validateParents(array $items, ValidationResult $result): void
    {
        foreach ($items as $item) {
            if ($item->kind === Kind::Epic) {
                if ($item->parent !== null) {
                    $result->error(sprintf('%s: an epic cannot have a parent.', $item->id));
                }
                continue;
            }

            if ($item->parent === null) {
                if ($item->kind === Kind::Subtask) {
                    $result->error(sprintf('%s: a subtask requires a task parent.', $item->id));
                } else {
                    $reason = $item->extra['standalone_reason'] ?? '';
                    if (!is_string($reason) || trim($reason) === '') {
                        $result->warning(sprintf('%s: standalone task without standalone_reason.', $item->id));
                    }
                }
                continue;
            }

            if ($item->parent === $item->id) {
                $result->error(sprintf('%s: item cannot be its own parent.', $item->id));
                continue;
            }

            $parent = $items[$item->parent] ?? null;
            if ($parent === null) {
                $result->error(sprintf('%s: parent %s does not exist.', $item->id, $item->parent));
                continue;
            }

            $expected = $item->kind === Kind::Subtask ? Kind::Task : Kind::Epic;
            if ($parent->kind !== $expected) {
                $result->error(sprintf(
                    '%s: %s parent must be a %s, got %s.',
                    $item->id,
                    $item->kind->value,
                    $expected->value,
                    $parent->kind->value,
                ));
            }
        }
    }

    /**
     * @param array<string, Item> $items
     */
    private function validateDependencies(array $items, ValidationResult $result): void
    {
        $graph = [];
        foreach ($items as $id => $item) {
            $graph[$id] = [];
            foreach ($item->dependsOn as $dependency) {
                if ($dependency === $id) {
                    $result->error(sprintf('%s: item depends on itself.', $id));
                    continue;
                }
                if (!isset($items[$dependency])) {
                    $result->error(sprintf('%s: dependency %s does not exist.', $id, $dependency));
                    continue;
                }
                $graph[$id][] = $dependency;
            }
        }

        $state = [];
        $stack = [];
        $reported = [];

        $visit = function (string $id) use (&$visit, &$state, &$stack, &$reported, $graph, $result): void {
            if (($state[$id] ?? 0) === 2) {
                return;
            }

            if (($state[$id] ?? 0) === 1) {
                $position = array_search($id, $stack, true);
                $cycle = array_slice($stack, $position === false ? 0 : $position);
                $cycle[] = $id;
                $key = implode('>', $cycle);
                if (!isset($reported[$key])) {
                    $reported[$key] = true;
                    $result->error('Dependency cycle: '.implode(' -> ', $cycle));
                }

                return;
            }

            $state[$id] = 1;
            $stack[] = $id;
            foreach ($graph[$id] as $dependency) {
                $visit($dependency);
            }
            array_pop($stack);
            $state[$id] = 2;
        };

        foreach (array_keys($graph) as $id) {
            $visit($id);
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

            if ($item->status->value === 'done' && $item->verifiedAt === null) {
                $result->warning(sprintf('%s: done without a verifier receipt.', $item->id));
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
                if ($this->hasDependencyPath($left, $right, $items) || $this->hasDependencyPath($right, $left, $items)) {
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
     * @param array<string, Item> $items
     * @param list<string>       $visited
     */
    private function hasDependencyPath(Item $from, Item $target, array $items, array $visited = []): bool
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
            if ($dependency !== null && $this->hasDependencyPath($dependency, $target, $items, $visited)) {
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
        $left = trim(str_replace('\\', '/', $left), '/');
        $right = trim(str_replace('\\', '/', $right), '/');

        if ($left === '' || $right === '') {
            return false;
        }

        return $left === $right
            || str_starts_with($left, $right.'/')
            || str_starts_with($right, $left.'/');
    }
}
