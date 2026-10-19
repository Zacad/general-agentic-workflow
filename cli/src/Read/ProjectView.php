<?php

declare(strict_types=1);

namespace Workflow\Read;

use Workflow\Domain\Item;
use Workflow\Domain\Status;
use Workflow\Store\ItemStore;
use Workflow\Validate\ItemValidator;

final class ProjectView
{
    public function __construct(private readonly ItemStore $store)
    {
    }

    /**
     * @return array<string, Item>
     */
    public function items(): array
    {
        $items = [];
        foreach ($this->store->all() as $item) {
            $items[$item->id] = $item;
        }

        return $items;
    }

    /**
     * @param array<string, Item> $items
     */
    public function isReady(Item $item, array $items): bool
    {
        return $this->blockReason($item, $items) === null && $item->status === Status::Todo;
    }

    /**
     * @param array<string, Item> $items
     */
    public function blockReason(Item $item, array $items): ?string
    {
        if ($item->status !== Status::Todo) {
            return null;
        }

        if ($item->approval === null) {
            return 'not approved';
        }

        if ($item->hasStaleApproval()) {
            return 'stale approval';
        }

        $waiting = [];
        foreach ($item->dependsOn as $dependencyId) {
            $dependency = $items[$dependencyId] ?? null;
            if ($dependency === null) {
                $waiting[] = $dependencyId.' (missing)';
            } elseif ($dependency->status !== Status::Done) {
                $waiting[] = $dependencyId.' ('.$dependency->status->value.')';
            }
        }

        if ($waiting !== []) {
            return 'waits on '.implode(', ', $waiting);
        }

        return null;
    }

    /**
     * Items that can start, with owned-path overlaps against active or other ready work.
     *
     * @param array<string, Item> $items
     *
     * @return list<array{item: Item, overlaps: list<string>}>
     */
    public function ready(array $items): array
    {
        $ready = [];
        foreach ($items as $item) {
            if ($this->isReady($item, $items)) {
                $ready[$item->id] = $item;
            }
        }

        $rows = [];
        foreach ($ready as $item) {
            $overlaps = $this->overlapsWithActive($item, $items);
            foreach ($ready as $other) {
                if ($other->id === $item->id) {
                    continue;
                }
                if ($this->sharesOwnedPath($item, $other)) {
                    $overlaps[] = $other->id;
                }
            }
            $overlaps = array_values(array_unique($overlaps));
            sort($overlaps, SORT_NATURAL);
            $rows[] = ['item' => $item, 'overlaps' => $overlaps];
        }

        usort($rows, static fn (array $a, array $b): int => $a['item']->id <=> $b['item']->id);

        return $rows;
    }

    /**
     * @param array<string, Item> $items
     *
     * @return list<string>
     */
    public function overlapsWithActive(Item $item, array $items): array
    {
        $overlaps = [];
        foreach ($items as $other) {
            if ($other->id === $item->id || $other->status !== Status::InProgress) {
                continue;
            }
            if ($this->sharesOwnedPath($item, $other)) {
                $overlaps[] = $other->id;
            }
        }

        sort($overlaps, SORT_NATURAL);

        return array_values(array_unique($overlaps));
    }

    private function sharesOwnedPath(Item $left, Item $right): bool
    {
        foreach ($left->owns as $leftPath) {
            foreach ($right->owns as $rightPath) {
                if (ItemValidator::pathsOverlap($leftPath, $rightPath)) {
                    return true;
                }
            }
        }

        return false;
    }
}
