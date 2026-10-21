<?php

declare(strict_types=1);

namespace Workflow\Domain;

/** Before/after persistence policy, independent of phase and approval gates. */
final class ItemLifecycle
{
    /**
     * Receipt intent comes from the explicit record-receipt route, never from
     * a supplied timestamp or changed last_result metadata alone. Artifact paths
     * are tracked, not their contents: same-path external edits still require
     * the freeze/receipt convention and a new independent verification.
     *
     * @param array<string, mixed>|null $receipt
     * @return bool Whether persistence must record an explicit unverified exception.
     */
    public static function apply(?Item $before, Item $after, ?array $receipt = null, bool $forceClose = false): bool
    {
        $changed = $before !== null && self::targetChanged($before, $after);
        $newReceipt = $receipt !== null || ($before !== null && $before->lastResult !== $after->lastResult);
        $success = $receipt !== null && $receipt === $after->lastResult && self::successfulReceipt($receipt);

        if ($before?->status === Status::Done) {
            if ($after->status !== Status::Done || $changed || ($newReceipt && !$success)
                || (!$success && $after->verifiedAt !== $before->verifiedAt)) {
                throw new WorkflowException(sprintf(
                    '%s: completed work is historical; create a new follow-up item with "workflow new" and explicitly approve its scope instead of changing or reopening this item.',
                    $after->id,
                ));
            }
        } else {
            // Ordinary store writes may retain existing proof, but cannot mint
            // it by supplying verified_at or copying a verifier-shaped receipt.
            $after->verifiedAt = $before !== null && self::hasCurrentVerification($before)
                && $after->verifiedAt === $before->verifiedAt && !$changed && !$newReceipt
                ? $before->verifiedAt : null;
        }

        if ($success) {
            // Includes any artifact references added by this successful receipt.
            $after->verifiedAt = $receipt['at'];
        }

        if ($after->status === Status::Done && $before?->status !== Status::Done && !self::hasCurrentVerification($after)) {
            if (!$forceClose) {
                throw new WorkflowException(sprintf(
                    '%s: no verifier receipt for the current target; record a successful receipt with "workflow result %s --role verifier --status done ..." or deliberately use "workflow close %s --force".',
                    $after->id,
                    $after->id,
                    $after->id,
                ));
            }

            return true;
        }

        return false;
    }

    public static function hasCurrentVerification(Item $item): bool
    {
        return $item->verifiedAt !== null && self::successfulReceipt($item->lastResult)
            && $item->verifiedAt === $item->lastResult['at'];
    }

    /** @param array<string, mixed>|null $receipt */
    private static function successfulReceipt(?array $receipt): bool
    {
        return ($receipt['role'] ?? null) === 'verifier' && ($receipt['status'] ?? null) === 'done'
            && is_string($receipt['at'] ?? null) && trim($receipt['at']) !== '';
    }

    private static function targetChanged(Item $before, Item $after): bool
    {
        // Compare frozen values directly; scopeHash remains the existing,
        // separately normalized approval contract, not a freshness fingerprint.
        foreach (['id', 'kind', 'parent', 'title', 'outcome', 'scopeIn', 'scopeOut', 'acceptance', 'dependsOn', 'owns', 'artifacts'] as $field) {
            if ($before->{$field} !== $after->{$field}) {
                return true;
            }
        }

        return self::sections($before) !== self::sections($after);
    }

    /** @return array<string, list<string>> */
    private static function sections(Item $item): array
    {
        // Include every occurrence: a second Design after Progress must not
        // escape history protection just because section() returns the first.
        $body = str_replace("\r\n", "\n", $item->body);
        preg_match_all('/^##[ \t]+(Plan|Design|Decisions|Progress)[ \t]*$/m', $body, $matches, PREG_OFFSET_CAPTURE);
        $sections = ['Plan' => [], 'Design' => [], 'Decisions' => []];
        foreach ($matches[0] as $index => [$heading, $offset]) {
            $name = $matches[1][$index][0];
            if ($name === 'Progress') {
                continue;
            }
            $start = $offset + strlen($heading);
            $end = $matches[0][$index + 1][1] ?? strlen($body);
            $sections[$name][] = trim(substr($body, $start, $end - $start));
        }

        return $sections;
    }
}
