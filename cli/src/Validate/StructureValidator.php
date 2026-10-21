<?php

declare(strict_types=1);

namespace Workflow\Validate;

use Workflow\Domain\Item;
use Workflow\Domain\Kind;

/** Pure record/graph checks, without workflow or filesystem policy. */
final class StructureValidator
{
    /** @param array<array-key, Item> $items */
    public function validate(array $items): ValidationResult
    {
        $result = new ValidationResult();
        $graph = [];
        foreach ($items as $item) {
            $this->validateIdentity($item, $result);
            $graph[$item->id] = $item;
        }
        foreach ($graph as $item) {
            $this->validateParent($item, $graph, $result);
        }
        $this->validateDependencies($graph, $result);

        return $result;
    }

    /**
     * Only the candidate's own structure, changed reverse parent relationships,
     * and cycles involving the candidate can be affected by this write.
     *
     * @param array<string, Item> $items The graph before the write.
     */
    public function validateCandidate(Item $candidate, array $items): ValidationResult
    {
        $previous = $items[$candidate->id] ?? null;
        $items[$candidate->id] = $candidate;
        $result = new ValidationResult();
        $this->validateIdentity($candidate, $result);
        $this->validateParent($candidate, $items, $result);

        if ($previous === null || $previous->kind !== $candidate->kind) {
            foreach ($items as $child) {
                if ($child->id !== $candidate->id && $child->parent === $candidate->id) {
                    $this->validateParent($child, $items, $result);
                }
            }
        }
        $this->validateDependencies($items, $result, $candidate->id);

        return $result;
    }

    private function validateIdentity(Item $item, ValidationResult $result): void
    {
        if (preg_match('/\A'.preg_quote($item->kind->prefix(), '/').'-\d+\z/', $item->id) !== 1) {
            $result->error(sprintf('%s: id does not match kind %s.', $item->id, $item->kind->value));
        }
    }

    /** @param array<string, Item> $items */
    private function validateParent(Item $item, array $items, ValidationResult $result): void
    {
        if ($item->kind === Kind::Epic) {
            if ($item->parent !== null) {
                $result->error(sprintf('%s: an epic cannot have a parent.', $item->id));
            }

            return;
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

            return;
        }

        if ($item->parent === $item->id) {
            $result->error(sprintf('%s: item cannot be its own parent.', $item->id));

            return;
        }

        $parent = $items[$item->parent] ?? null;
        if ($parent === null) {
            $result->error(sprintf('%s: parent %s does not exist.', $item->id, $item->parent));

            return;
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

    /** @param array<string, Item> $items */
    private function validateDependencies(array $items, ValidationResult $result, ?string $candidateId = null): void
    {
        $graph = [];
        foreach ($items as $id => $item) {
            // PHP converts numeric-string array keys to integers, including
            // malformed legacy IDs that still need diagnostics, not a crash.
            $id = (string) $id;
            $graph[$id] = [];
            foreach ($item->dependsOn as $dependency) {
                if ($dependency === $id) {
                    if ($candidateId === null || $candidateId === $id) {
                        $result->error(sprintf('%s: item depends on itself.', $id));
                    }
                    continue;
                }
                if (!isset($items[$dependency])) {
                    if ($candidateId === null || $candidateId === $id) {
                        $result->error(sprintf('%s: dependency %s does not exist.', $id, $dependency));
                    }
                    continue;
                }
                $graph[$id][] = $dependency;
            }
        }

        $state = [];
        $stack = [];
        $reported = [];
        $visit = function (string $id) use (&$visit, &$state, &$stack, &$reported, $graph, $result, $candidateId): void {
            if (($state[$id] ?? 0) === 2) {
                return;
            }
            if (($state[$id] ?? 0) === 1) {
                $position = array_search($id, $stack, true);
                $cycle = array_slice($stack, $position === false ? 0 : $position);
                if ($candidateId !== null && !in_array($candidateId, $cycle, true)) {
                    return;
                }
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

        // Starting at the candidate keeps it on the stack throughout traversal,
        // so every path back to it is detected even with other legacy cycles.
        foreach ($candidateId === null ? array_keys($graph) : [$candidateId] as $id) {
            $visit((string) $id);
        }
    }
}
