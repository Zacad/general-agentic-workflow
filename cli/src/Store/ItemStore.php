<?php

declare(strict_types=1);

namespace Workflow\Store;

use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Workflow\Domain\FrontMatter;
use Workflow\Domain\Approval;
use Workflow\Domain\AutomatedEvent;
use Workflow\Domain\InvalidItemException;
use Workflow\Domain\Item;
use Workflow\Domain\ItemLifecycle;
use Workflow\Domain\Kind;
use Workflow\Domain\Status;
use Workflow\Domain\WorkflowException;
use Workflow\Validate\StructureValidator;
use Workflow\Support\ChangeDetector;

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
        // First try nested path
        $nestedPath = $this->itemsDir().'/'.$this->relativeItemPath($id);
        if (is_file($nestedPath)) {
            return $nestedPath;
        }

        // Fallback to flat path (for backward compatibility before migration)
        $flatPath = $this->itemsDir().'/'.$id.'/item.md';
        if (is_file($flatPath)) {
            return $flatPath;
        }

        // Return nested path as default (for new items)
        return $nestedPath;
    }

    /**
     * Compute the relative path for an item based on its parent hierarchy.
     * This traverses the parent chain to build the nested structure.
     */
    public function relativeItemPath(string $id): string
    {
        $chain = $this->parentChain($id);
        $chain[] = $id;

        return implode('/', $chain).'/item.md';
    }

    /**
     * Build the parent chain for an item (ancestors only, not including the item itself).
     *
     * @return list<string>
     */
    private function parentChain(string $id): array
    {
        $chain = [];
        $current = $id;
        $visited = [$id => true];

        while (true) {
            // Find the item file (could be flat or nested)
            $itemPath = $this->findItemFile($current);
            if ($itemPath === null) {
                // Item doesn't exist
                break;
            }

            $raw = file_get_contents($itemPath);
            if ($raw === false) {
                break;
            }

            try {
                $parsed = FrontMatter::parse($raw);
                $parent = $parsed['data']['parent'] ?? null;
            } catch (\Exception) {
                // Malformed item, stop traversing
                break;
            }

            if ($parent === null || !is_string($parent) || $parent === '') {
                break;
            }

            if (isset($visited[$parent])) {
                // Circular reference detected
                break;
            }

            $visited[$parent] = true;
            array_unshift($chain, $parent);
            $current = $parent;
        }

        return $chain;
    }

    /**
     * Find the item.md file for a given ID, searching both flat and nested structures.
     */
    private function findItemFile(string $id): ?string
    {
        // Try flat path first (fastest)
        $flatPath = $this->itemsDir().'/'.$id.'/item.md';
        if (is_file($flatPath)) {
            return $flatPath;
        }

        // Search recursively in nested structure
        $found = null;
        $this->findItemRecursive($this->itemsDir(), $id, $found);
        
        return $found;
    }

    /**
     * Recursively search for an item file.
     */
    private function findItemRecursive(string $directory, string $targetId, ?string &$found): void
    {
        if ($found !== null) {
            return; // Already found
        }

        foreach (glob($directory.'/*', GLOB_ONLYDIR) ?: [] as $subdir) {
            $basename = basename($subdir);
            $itemFile = $subdir.'/item.md';

            if ($basename === $targetId && is_file($itemFile)) {
                $found = $itemFile;
                return;
            }

            // Recurse into subdirectories
            $this->findItemRecursive($subdir, $targetId, $found);
        }
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
        $this->collectIds($this->itemsDir(), $ids);
        sort($ids, SORT_NATURAL);

        return $ids;
    }

    /**
     * Recursively collect item IDs from nested directory structure.
     *
     * @param list<string> $ids
     */
    private function collectIds(string $directory, array &$ids): void
    {
        foreach (glob($directory.'/*', GLOB_ONLYDIR) ?: [] as $subdir) {
            $basename = basename($subdir);
            $itemFile = $subdir.'/item.md';

            if (is_file($itemFile)) {
                $ids[] = $basename;
            }

            // Recurse into subdirectories (for nested items)
            $this->collectIds($subdir, $ids);
        }
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
     * Record a scope approval under current run authority. No caller-supplied
     * mutation is accepted here: the run grants this operation only.
     *
     * @return array<string, mixed> The committed approval and scope snapshot.
     */
    public function approveScopeForRun(string $id, string $runId, bool $delta = false): array
    {
        return $this->authorizedItemMutation($id, $runId, 'scope-approve', function (Item $item) use ($id, $runId, $delta): array {
            if ($item->status === Status::Done) {
                throw new WorkflowException($id.': completed work is historical; create an approved follow-up item.');
            }
            if ($delta && (new ChangeDetector())->detect($item, $item->lastApprovedScope)['has_material']) {
                throw new WorkflowException('Cannot use --delta: material changes detected.');
            }
            $item->approval = new Approval(gmdate('c'), 'automated', $item->scopeHash(), null, 'scope_hash', ['kind' => 'automated', 'run_id' => $runId]);
            $item->lastApprovedScope = $item->snapshotScope();
            if ($item->status === Status::Draft) {
                $item->status = Status::Todo;
            }

            return ['item' => $item, 'approval' => $item->approval->toArray()];
        });
    }

    /** Replace exactly the Design section and approve the resulting hash. */
    public function approveDesignForRun(string $id, string $runId, string $content): array
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        if (preg_match('/^ {0,3}##(?!#)(?:[ \t]|$)/m', $content)) {
            throw new WorkflowException('Design content must not contain top-level headings.');
        }
        return $this->authorizedItemMutation($id, $runId, 'design-approve', function (Item $item) use ($id, $runId, $content): array {
            if (!in_array($item->kind, [Kind::Task, Kind::Subtask], true)
                || !in_array($item->status, [Status::Draft, Status::Todo, Status::InProgress], true)
                || !$item->isApproved()) {
                throw new WorkflowException('Automated design approval requires an open task or subtask with current scope approval.');
            }
            $body = str_replace(["\r\n", "\r"], "\n", $item->body);
            preg_match_all('/^ {0,3}##(?!#)(?:[ \t]|$).*$/m', $body, $headings);
            $seen = [];
            foreach ($headings[0] as $heading) {
                if (!preg_match('/^##[ \t]+(Plan|Design|Decisions|Progress)[ \t]*$/', $heading, $match) || isset($seen[$match[1]])) {
                    throw new WorkflowException('Ambiguous item section layout.');
                }
                $seen[$match[1]] = true;
            }
            $before = clone $item;
            $before->body = $body;
            $item->body = self::replaceSection($body, 'Design', trim($content));
            foreach (['Plan', 'Decisions', 'Progress'] as $section) {
                if ($before->section($section) !== $item->section($section)) {
                    throw new WorkflowException('Design replacement would change another section.');
                }
            }
            $item->designApproval = new Approval(gmdate('c'), 'automated', $item->designHash(), null, 'design_hash', ['kind' => 'automated', 'run_id' => $runId]);
            $old = $before->toArray(); $new = $item->toArray();
            unset($old['design_approval'], $new['design_approval']);
            if ($old !== $new) {
                throw new WorkflowException('Design replacement would change unrelated fields.');
            }
            return ['item' => $item, 'approval' => $item->designApproval->toArray()];
        });
    }

    public function decideForRun(string $id, string $runId, string $text): array
    {
        return $this->authorizedItemMutation($id, $runId, 'decide', static function (Item $item) use ($runId, $text): array {
            if ($item->status === Status::Done) {
                throw new WorkflowException('Completed work is historical; create an approved follow-up item.');
            }
            $event = AutomatedEvent::create('decide', $runId, $text);
            $item->automatedEvents ??= [];
            $item->automatedEvents[] = $event;
            return $event;
        });
    }

    public function acceptReviewForRun(string $id, string $runId, string $text): array
    {
        return $this->authorizedItemMutation($id, $runId, 'accept-review', static function (Item $item) use ($runId, $text): array {
            if (!in_array($item->kind, [Kind::Task, Kind::Subtask], true) || $item->status !== Status::Review
                || !ItemLifecycle::hasCurrentVerification($item)) {
                throw new WorkflowException('Automated acceptance requires a task or subtask in review with current verification.');
            }
            $event = AutomatedEvent::create('accept-review', $runId, $text);
            $item->automatedEvents ??= [];
            $item->automatedEvents[] = $event;
            $item->status = Status::Done;
            return $event;
        });
    }

    /** Shared lock-held path; only fixed internal operations may supply a callback. */
    private function authorizedItemMutation(string $id, string $runId, string $action, callable $change): mixed
    {
        return $this->withLock(function () use ($id, $runId, $action, $change): mixed {
            [$path, $chain, $item] = $this->strictAncestry($id);
            $runs = new RunStore($this->root);
            $check = function () use ($runs, $runId, $id, $chain, $path, $action): void {
                [$currentPath, $currentChain] = $this->strictAncestry($id);
                if ($currentPath !== $path || $currentChain !== $chain) {
                    throw new WorkflowException('Item ancestry changed during authorization.');
                }
                $runs->loadForUpdate($runId)->assertAuthorized($action, $currentChain, new \DateTimeImmutable('@'.time()));
            };
            $check();
            $before = clone $item;
            $result = $change($item);
            if ($item->id !== $before->id || $item->kind !== $before->kind || $item->parent !== $before->parent) {
                throw new WorkflowException('Run authority does not permit changing identity or ancestry.');
            }
            $eventIntent = in_array($action, ['decide', 'accept-review'], true) ? ['action' => $action, 'run_id' => $runId] : null;
            $this->persist($item, $before, targetPath: $path, beforeRename: $check, eventIntent: $eventIntent);

            return $result;
        });
    }

    /**
     * Resolve every ancestor by unique physical item path, not by inferred
     * layout. Caller holds the project write lock.
     * @return array{string, list<string>, Item}
     */
    private function strictAncestry(string $target): array
    {
        $id = $target;
        $visited = [];
        $childKind = null;
        $targetPath = null;
        $targetItem = null;
        while ($id !== null) {
            if (!preg_match('/\A[ETS]-[0-9]+\z/', $id) || isset($visited[$id])) {
                throw new WorkflowException('Invalid or cyclic item ancestry.');
            }
            $visited[$id] = true;
            $paths = [];
            if (is_dir($this->itemsDir())) {
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->itemsDir(), \FilesystemIterator::SKIP_DOTS));
                foreach ($iterator as $file) {
                    if ($file->getFilename() === 'item.md' && basename($file->getPath()) === $id && $file->isFile()) {
                        $paths[] = $file->getPathname();
                    }
                }
            }
            if (count($paths) !== 1) {
                throw new WorkflowException('Missing or ambiguous item ancestry: '.$id);
            }
            $raw = @file_get_contents($paths[0]);
            if ($raw === false) {
                throw new WorkflowException('Cannot read item ancestry: '.$id);
            }
            $parsed = FrontMatter::parse($raw);
            $data = $parsed['data'];
            $kinds = ['E' => 'epic', 'T' => 'task', 'S' => 'subtask'];
            if (($data['id'] ?? null) !== $id || ($data['kind'] ?? null) !== $kinds[$id[0]] || !array_key_exists('parent', $data)) {
                throw new WorkflowException('Malformed item ancestry: '.$id);
            }
            $kind = $data['kind'];
            if ($childKind !== null && !(($childKind === 'task' && $kind === 'epic') || ($childKind === 'subtask' && $kind === 'task'))) {
                throw new WorkflowException('Invalid parent kind.');
            }
            $parent = $data['parent'];
            if (($parent !== null && (!is_string($parent) || $kind === 'epic')) || ($parent === null && $kind === 'subtask')) {
                throw new WorkflowException('Invalid parent.');
            }
            $item = Item::fromArray($data, $parsed['body']);
            if ($targetItem === null) {
                $targetPath = $paths[0];
                $targetItem = $item;
            }
            $childKind = $kind;
            $id = $parent;
        }

        return [$targetPath, array_keys($visited), $targetItem];
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
    public function close(string $id, Status $status, string $reason, string $at, bool $force = false, ?callable $prepareReason = null): bool
    {
        if (!in_array($status, [Status::Done, Status::Cancelled], true)) {
            throw new WorkflowException('Close requires status done or cancelled.');
        }

        $closed = $this->mutateWithIntent($id, function (Item $item) use ($status, $reason, $at, $prepareReason): Item {
            if ($prepareReason !== null) {
                $reason = $prepareReason($item, $reason);
            }
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
    private function persist(Item $item, ?Item $before = null, ?array $receipt = null, bool $forceClose = false, ?string $targetPath = null, ?callable $beforeRename = null, ?array $eventIntent = null): void
    {
        if (array_key_exists('automated_events', $item->extra)) {
            throw new InvalidItemException('automated_events cannot be supplied as extra metadata.');
        }
        if ($item->automatedEvents !== null) {
            AutomatedEvent::validate($item->automatedEvents);
        }
        $oldEvents = $before?->automatedEvents;
        if ($eventIntent === null) {
            if ($oldEvents !== $item->automatedEvents) {
                throw new WorkflowException('Automated events cannot be changed by this operation.');
            }
        } else {
            $old = $oldEvents ?? [];
            $new = $item->automatedEvents ?? [];
            $event = end($new);
            if (count($new) !== count($old) + 1 || array_slice($new, 0, -1) !== $old
                || !is_array($event) || $event['action'] !== $eventIntent['action'] || $event['run_id'] !== $eventIntent['run_id']) {
                throw new WorkflowException('Invalid automated event mutation.');
            }
        }
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
        $this->atomicWrite($targetPath ?? $this->itemPath($item->id), $contents, $beforeRename);
    }

    private function atomicWrite(string $path, string $contents, ?callable $beforeRename = null): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new WorkflowException(sprintf('Cannot create directory: %s', $directory));
        }

        $temporary = $directory.'/.'.basename($path).'.'.bin2hex(random_bytes(16)).'.tmp';
        $handle = @fopen($temporary, 'xb');
        if ($handle === false) {
            throw new WorkflowException(sprintf('Cannot write temporary file: %s', $temporary));
        }

        try {
            $offset = 0;
            $length = strlen($contents);
            while ($offset < $length) {
                $written = @fwrite($handle, substr($contents, $offset));
                if ($written === false || $written === 0) {
                    throw new WorkflowException(sprintf('Cannot write temporary file: %s', $temporary));
                }
                $offset += $written;
            }
            if (!@fflush($handle) || !@fsync($handle)) {
                throw new WorkflowException(sprintf('Cannot flush temporary file: %s', $temporary));
            }
            if (!@fclose($handle)) {
                $handle = null;
                throw new WorkflowException(sprintf('Cannot close temporary file: %s', $temporary));
            }
            $handle = null;
            if ($beforeRename !== null) {
                $beforeRename();
            }
            if (!@rename($temporary, $path)) {
                throw new WorkflowException(sprintf('Cannot replace file: %s; inspect before retrying.', $path));
            }
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
            if (is_file($temporary)) {
                @unlink($temporary);
            }
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
