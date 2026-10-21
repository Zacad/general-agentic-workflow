<?php

declare(strict_types=1);

namespace Workflow\Store;

use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Workflow\Domain\FrontMatter;
use Workflow\Domain\InvalidItemException;
use Workflow\Domain\Item;
use Workflow\Domain\ItemLifecycle;
use Workflow\Domain\Kind;
use Workflow\Domain\Status;
use Workflow\Domain\WorkflowException;
use Workflow\Validate\StructureValidator;

final class ItemStore
{
    public function __construct(private readonly string $root)
    {
    }

    public function root(): string
    {
        return $this->root;
    }

    public function workflowDir(): string
    {
        return $this->root.'/.workflow';
    }

    public function itemsDir(): string
    {
        return $this->workflowDir().'/items';
    }

    public function itemPath(string $id): string
    {
        return $this->itemsDir().'/'.$id.'/item.md';
    }

    public function exists(string $id): bool
    {
        return is_file($this->itemPath($id));
    }

    /**
     * @return list<string>
     */
    public function ids(): array
    {
        $ids = [];
        foreach (glob($this->itemsDir().'/*', GLOB_ONLYDIR) ?: [] as $directory) {
            $ids[] = basename($directory);
        }

        sort($ids, SORT_NATURAL);

        return $ids;
    }

    /**
     * @return list<Item>
     */
    public function all(): array
    {
        $items = [];
        foreach ($this->ids() as $id) {
            $items[] = $this->load($id);
        }

        return $items;
    }

    public function load(string $id): Item
    {
        $path = $this->itemPath($id);
        if (!is_file($path)) {
            throw new WorkflowException(sprintf('Item not found: %s', $id));
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new WorkflowException(sprintf('Cannot read item: %s', $path));
        }

        $parsed = FrontMatter::parse($raw);
        $item = Item::fromArray($parsed['data'], $parsed['body']);

        if ($item->id !== $id) {
            throw new InvalidItemException(sprintf('Item %s declares id %s.', $id, $item->id));
        }

        return $item;
    }

    public function save(Item $item): void
    {
        $this->withLock(function () use ($item): void {
            $before = $this->exists($item->id) ? $this->load($item->id) : null;
            $this->persist($item, $before);
        });
    }

    public function create(Item $item): void
    {
        $this->withLock(function () use ($item): void {
            if ($this->exists($item->id)) {
                throw new WorkflowException(sprintf('Item already exists: %s', $item->id));
            }
            $this->persist($item);
        });
    }

    /**
     * @template T
     *
     * @param callable(Item): T $callback
     *
     * @return T
     */
    public function mutate(string $id, callable $callback): mixed
    {
        return $this->mutateWithIntent($id, $callback);
    }

    /**
     * Record explicit receipt intent, including identical receipts in one second.
     *
     * @param array<string, mixed> $receipt
     * @param list<string> $artifacts
     */
    public function recordReceipt(string $id, array $receipt, array $artifacts, string $progress): void
    {
        $this->mutateWithIntent($id, function (Item $item) use ($receipt, $artifacts, $progress): void {
            $item->lastResult = $receipt;
            $item->addArtifacts($artifacts);
            $this->appendProgress($item, $progress);
        }, $receipt);
    }

    /** The sole explicit unverified-completion exception; never a generic save flag. */
    public function close(string $id, Status $status, string $reason, string $at, bool $force = false): bool
    {
        if (!in_array($status, [Status::Done, Status::Cancelled], true)) {
            throw new WorkflowException('Close requires status done or cancelled.');
        }

        $closed = $this->mutateWithIntent($id, function (Item $item) use ($status, $reason, $at): Item {
            $item->status = $status;
            $entry = sprintf('- %s · closed as %s', $at, $status->value);
            if ($reason !== '') {
                $entry .= "\n  Reason: ".$reason;
            }
            $this->appendProgress($item, $entry);

            return $item;
        }, forceClose: $force);

        return ItemLifecycle::hasCurrentVerification($closed);
    }

    /** @param array<string, mixed>|null $receipt */
    private function mutateWithIntent(string $id, callable $callback, ?array $receipt = null, bool $forceClose = false): mixed
    {
        return $this->withLock(function () use ($id, $callback, $receipt, $forceClose): mixed {
            $item = $this->load($id);
            $before = clone $item;
            $result = $callback($item);
            if ($item->id !== $id) {
                throw new InvalidItemException(sprintf('Cannot change item id %s to %s.', $id, $item->id));
            }
            $this->persist($item, $before, $receipt, $forceClose);

            return $result;
        });
    }

    public function nextId(Kind $kind): string
    {
        $prefix = $kind->prefix();
        $max = 0;

        foreach ($this->ids() as $id) {
            if (preg_match('/^'.preg_quote($prefix, '/').'-(\d+)$/', $id, $match) === 1) {
                $max = max($max, (int) $match[1]);
            }
        }

        return sprintf('%s-%03d', $prefix, $max + 1);
    }

    public function appendProgress(Item $item, string $entry): void
    {
        $item->body = self::appendToSection($item->body, 'Progress', $entry);
    }

    public static function replaceSection(string $body, string $heading, string $content): string
    {
        $pattern = '/(^##[ \t]+'.preg_quote($heading, '/').'[ \t]*$\n)(.*?)(?='.Item::sectionBoundary($heading).'|\z)/ms';

        if (preg_match($pattern, $body) === 1) {
            return (string) preg_replace_callback(
                $pattern,
                static fn (array $match): string => $match[1]."\n".rtrim($content)."\n\n",
                $body,
                1,
            );
        }

        return rtrim($body)."\n\n## ".$heading."\n\n".rtrim($content)."\n";
    }

    public function withLock(callable $callback): mixed
    {
        $lockDirectory = $this->workflowDir().'/.locks';
        if (!is_dir($lockDirectory) && !mkdir($lockDirectory, 0777, true) && !is_dir($lockDirectory)) {
            throw new WorkflowException(sprintf('Cannot create lock directory: %s', $lockDirectory));
        }

        $factory = new LockFactory(new FlockStore($lockDirectory));
        $lock = $factory->createLock('workflow-write');

        if (!$lock->acquire(false)) {
            throw new WorkflowException('Another workflow write is in progress; retry.');
        }

        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }

    /** @param array<string, mixed>|null $receipt */
    private function persist(Item $item, ?Item $before = null, ?array $receipt = null, bool $forceClose = false): void
    {
        $unverifiedException = ItemLifecycle::apply($before, $item, $receipt, $forceClose);
        $items = [];
        foreach ($this->ids() as $id) {
            $path = $this->itemPath($id);
            if (!is_file($path)) {
                continue;
            }
            $raw = file_get_contents($path);
            if ($raw === false) {
                continue;
            }
            try {
                $data = FrontMatter::parse($raw)['data'];
                // References resolve by directory ID. Keep structural edges
                // even when declared IDs or nonstructural metadata are broken;
                // this projection is never written back as an automatic repair.
                $items[$id] = Item::fromArray([
                    'id' => $id,
                    'kind' => $data['kind'] ?? null,
                    'parent' => $data['parent'] ?? null,
                    'depends_on' => $data['depends_on'] ?? [],
                ], '');
            } catch (InvalidItemException) {
                // Unparseable structure remains a full-validate diagnostic,
                // not a gate on edits to unrelated records.
            }
        }
        $result = (new StructureValidator())->validateCandidate($item, $items);
        if (!$result->ok()) {
            throw new InvalidItemException(implode("\n", $result->errors));
        }

        if ($unverifiedException) {
            $this->appendProgress($item, '- Verification exception: explicit --force; closed without a current successful verifier receipt.');
        }
        $item->updated = gmdate('c');
        $contents = FrontMatter::render($item->toArray(), $item->body);
        $this->atomicWrite($this->itemPath($item->id), $contents);
    }

    private function atomicWrite(string $path, string $contents): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new WorkflowException(sprintf('Cannot create directory: %s', $directory));
        }

        $temporary = $directory.'/.'.basename($path).'.'.bin2hex(random_bytes(6)).'.tmp';
        $handle = fopen($temporary, 'wb');
        if ($handle === false) {
            throw new WorkflowException(sprintf('Cannot write temporary file: %s', $temporary));
        }

        try {
            fwrite($handle, $contents);
            fflush($handle);
            fsync($handle);
        } finally {
            fclose($handle);
        }

        if (!rename($temporary, $path)) {
            @unlink($temporary);
            throw new WorkflowException(sprintf('Cannot replace file: %s', $path));
        }
    }

    public static function appendToSection(string $body, string $heading, string $entry): string
    {
        $entry = trim($entry);
        if ($entry === '') {
            return $body;
        }

        $pattern = '/(^##[ \t]+'.preg_quote($heading, '/').'[ \t]*$\n)(.*?)(?=^##[ \t]+|\z)/ms';
        if (preg_match($pattern, $body) === 1) {
            return preg_replace_callback(
                $pattern,
                static function (array $match) use ($entry): string {
                    $content = rtrim($match[2]);

                    return $match[1].($content !== '' ? $content."\n\n" : "\n").$entry."\n\n";
                },
                $body,
                1,
            );
        }

        return rtrim($body)."\n\n## ".$heading."\n\n".$entry."\n";
    }
}
