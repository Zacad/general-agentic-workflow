<?php

declare(strict_types=1);

namespace Workflow\Support;

use Workflow\Domain\Item;

final class ChangeDetector
{
    /**
     * Detect changes between current item and last approved state.
     *
     * @param array{outcome: string, scope_in: list<string>, scope_out: list<string>, acceptance: list<string>, depends_on: list<string>, owns: list<string>, title: string, parent: ?string}|null $approved
     *
     * @return array{
     *     material: list<string>,
     *     non_material: list<string>,
     *     has_material: bool,
     *     summary: string
     * }
     */
    public function detect(Item $current, ?array $approved): array
    {
        if ($approved === null) {
            return [
                'material' => [],
                'non_material' => [],
                'has_material' => false,
                'summary' => 'No previous approval found. Full approval required.',
            ];
        }

        $material = [];
        $nonMaterial = [];

        // Check outcome changes (material)
        if ($current->outcome !== $approved['outcome']) {
            $material[] = 'outcome changed';
        }

        // Check acceptance criteria changes
        $acceptanceRemoved = array_diff($approved['acceptance'], $current->acceptance);
        $acceptanceAdded = array_diff($current->acceptance, $approved['acceptance']);
        if ($acceptanceRemoved !== []) {
            $material[] = sprintf('removed %d acceptance criterion/criteria', count($acceptanceRemoved));
        }
        if ($acceptanceAdded !== []) {
            $nonMaterial[] = sprintf('added %d acceptance criterion/criteria', count($acceptanceAdded));
        }

        // Check scope changes
        $scopeInRemoved = array_diff($approved['scope_in'], $current->scopeIn);
        $scopeInAdded = array_diff($current->scopeIn, $approved['scope_in']);
        $scopeOutRemoved = array_diff($approved['scope_out'], $current->scopeOut);
        $scopeOutAdded = array_diff($current->scopeOut, $approved['scope_out']);

        if ($scopeInRemoved !== [] || $scopeOutRemoved !== []) {
            $material[] = 'scope reduced (removed in-scope or added out-of-scope items)';
        }
        if ($scopeInAdded !== []) {
            $nonMaterial[] = sprintf('added %d item(s) to scope.in', count($scopeInAdded));
        }
        if ($scopeOutAdded !== []) {
            $nonMaterial[] = sprintf('added %d item(s) to scope.out', count($scopeOutAdded));
        }

        // Check dependency changes
        $depsRemoved = array_diff($approved['depends_on'], $current->dependsOn);
        $depsAdded = array_diff($current->dependsOn, $approved['depends_on']);
        if ($depsRemoved !== []) {
            $material[] = sprintf('removed %d dependency/dependencies', count($depsRemoved));
        }
        if ($depsAdded !== []) {
            $nonMaterial[] = sprintf('added %d dependency/dependencies', count($depsAdded));
        }

        // Check ownership changes (material if conflicts possible)
        $ownsRemoved = array_diff($approved['owns'], $current->owns);
        $ownsAdded = array_diff($current->owns, $approved['owns']);
        if ($ownsRemoved !== []) {
            $material[] = 'removed owned paths';
        }
        if ($ownsAdded !== []) {
            $material[] = 'added owned paths (potential conflicts)';
        }

        // Check title changes (usually material)
        if ($current->title !== $approved['title']) {
            $material[] = 'title changed';
        }

        // Check parent changes (material)
        if ($current->parent !== $approved['parent']) {
            $material[] = 'parent changed';
        }

        $summary = $this->buildSummary($material, $nonMaterial);

        return [
            'material' => $material,
            'non_material' => $nonMaterial,
            'has_material' => $material !== [],
            'summary' => $summary,
        ];
    }

    /**
     * @param list<string> $material
     * @param list<string> $nonMaterial
     */
    private function buildSummary(array $material, array $nonMaterial): string
    {
        $parts = [];

        if ($material !== []) {
            $parts[] = 'Material changes: '.implode('; ', $material);
        }

        if ($nonMaterial !== []) {
            $parts[] = 'Non-material changes: '.implode('; ', $nonMaterial);
        }

        if ($parts === []) {
            return 'No changes detected.';
        }

        return implode('. ', $parts).'.';
    }
}
