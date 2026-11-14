<?php

declare(strict_types=1);

namespace Workflow\Store;

use Workflow\Domain\FrontMatter;
use Workflow\Domain\Item;
use Workflow\Domain\Run;
use Workflow\Domain\WorkflowException;

final class RunStore
{
    private readonly ItemStore $items;

    public function __construct(private readonly string $root)
    {
        $this->items = new ItemStore($root);
    }

    public function load(string $id): Run
    {
        $path = $this->path($id);
        $raw = @file_get_contents($path, false, null, 0, 1048577);
        if ($raw === false || strlen($raw) > 1048576) throw new WorkflowException('Cannot read valid run file: '.$path);
        try {
            $decoded = json_decode($raw, false, 512, JSON_THROW_ON_ERROR);
            if (!$decoded instanceof \stdClass) throw new WorkflowException('Run must be an object.');
            if (!($decoded->permissions ?? null) instanceof \stdClass || !($decoded->limits ?? null) instanceof \stdClass
                || !is_array($decoded->success_criteria ?? null) || !is_array($decoded->permissions->actions ?? null) || !is_array($decoded->permissions->roots ?? null)) throw new WorkflowException('Invalid JSON object or list type.');
            $offset = 0;
            self::scanJson($raw, $offset);
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            $run = Run::fromArray($data);
            if ($data['id'] !== $id) throw new WorkflowException('Run ID/path mismatch.');
            return $run;
        } catch (\Throwable $e) {
            throw new WorkflowException('Invalid run file '.$path.': '.$e->getMessage(), 0, $e);
        }
    }

    /** Caller must hold ItemStore::withLock; this method cannot enforce that precondition. */
    public function loadForUpdate(string $id): Run { return $this->load($id); }

    public function create(Run $run): void
    {
        if (!is_dir($this->root.'/.workflow')) throw new WorkflowException('Project has no .workflow directory.');
        $this->items->withLock(function () use ($run): void {
            $data = $run->toArray();
            $now = gmdate('Y-m-d\TH:i:s\Z');
            if ($data['status'] !== 'active' || $data['status_reason'] !== null || $data['recovery'] !== null || $data['limits']['expires_at'] <= $now || $data['created_at'] !== $data['updated_at'] || $data['created_at'] > $now) throw new WorkflowException('Invalid initial run state or expiry.');
            foreach ($data['permissions']['roots'] as $root) $this->validateRoot($root);
            $path = $this->path($data['id']);
            if (file_exists($path) || is_link($path)) throw new WorkflowException('Run ID collision: '.$path);
            $this->write($path, $run);
        });
    }

    /** Callback returns the replacement Run; all authority fields are immutable. */
    public function mutate(string $id, callable $change): Run
    {
        return $this->items->withLock(function () use ($id, $change): Run {
            $before = $this->loadForUpdate($id)->toArray();
            $after = $change(Run::fromArray($before));
            if (!$after instanceof Run) throw new WorkflowException('Run mutation must return a Run.');
            $data = $after->toArray();
            foreach (['version', 'id', 'autonomous', 'goal', 'success_criteria', 'permissions', 'limits', 'created_at'] as $key) {
                if ($data[$key] !== $before[$key]) throw new WorkflowException('Run authority is immutable.');
            }
            $now = gmdate('Y-m-d\TH:i:s\Z');
            if ($before['status'] === 'revoked' || $now < $before['updated_at'] || $data['updated_at'] < $before['updated_at'] || $data['updated_at'] > $now) throw new WorkflowException('Invalid transition or clock moved backwards.');
            $status = $data['status'];
            if (!in_array($status, ['active', 'blocked', 'interrupted', 'limit-exceeded', 'revoked'], true)
                || ($status === 'active' && ($before['status'] === 'active' || $data['recovery'] === null || $data['limits']['expires_at'] <= $now))
                || ($status === 'revoked' && $data['recovery'] !== $before['recovery'])) throw new WorkflowException('Invalid run transition.');
            $this->write($this->path($id), $after);
            return $after;
        });
    }

    private function path(string $id): string
    {
        if (!preg_match('/\AR-[0-9a-f]{32}\z/', $id)) throw new WorkflowException('Invalid run ID: '.$id);
        return $this->root.'/.workflow/runs/'.$id.'.json';
    }

    private function validateRoot(string $id): void
    {
        $visited = [];
        $childKind = null;
        while ($id !== null) {
            if (!preg_match('/\A[ETS]-[0-9]+\z/', $id) || isset($visited[$id])) throw new WorkflowException('Invalid or cyclic root ancestry.');
            $visited[$id] = true;
            $paths = [];
            $dir = $this->root.'/.workflow/items';
            if (is_dir($dir)) {
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
                foreach ($iterator as $file) {
                    if ($file->getFilename() === 'item.md' && basename($file->getPath()) === $id && $file->isFile()) $paths[] = $file->getPathname();
                }
            }
            if (count($paths) !== 1) throw new WorkflowException('Missing or ambiguous root ancestry item: '.$id);
            $raw = @file_get_contents($paths[0]);
            if ($raw === false) throw new WorkflowException('Cannot read root ancestry item: '.$id);
            $data = FrontMatter::parse($raw)['data'];
            Item::fromArray($data, '');
            $kinds = ['E' => 'epic', 'T' => 'task', 'S' => 'subtask'];
            if (($data['id'] ?? null) !== $id || ($data['kind'] ?? null) !== $kinds[$id[0]] || !array_key_exists('parent', $data)) throw new WorkflowException('Malformed root ancestry item: '.$id);
            $kind = $data['kind'];
            if ($childKind !== null && !(($childKind === 'task' && $kind === 'epic') || ($childKind === 'subtask' && $kind === 'task'))) throw new WorkflowException('Invalid parent kind.');
            $parent = $data['parent'];
            if ($parent !== null && (!is_string($parent) || $kind === 'epic')) throw new WorkflowException('Invalid parent.');
            if ($parent === null && $kind === 'subtask') throw new WorkflowException('Subtask requires a parent.');
            $childKind = $kind;
            $id = $parent;
        }
    }

    private function write(string $path, Run $run): void
    {
        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) throw new WorkflowException('Cannot create run directory: '.$dir);
        $contents = json_encode($run->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        if (strlen($contents) > 1048576) throw new WorkflowException('Run exceeds size limit.');
        $tmp = $dir.'/.'.basename($path).'.'.bin2hex(random_bytes(16)).'.tmp';
        $handle = @fopen($tmp, 'xb');
        if ($handle === false) throw new WorkflowException('Cannot create temporary run file: '.$tmp);
        try {
            $offset = 0;
            while ($offset < strlen($contents)) {
                $written = @fwrite($handle, substr($contents, $offset));
                if ($written === false || $written === 0) throw new WorkflowException('Cannot write run file: '.$tmp);
                $offset += $written;
            }
            if (!@fflush($handle) || !@fsync($handle)) throw new WorkflowException('Cannot flush run file: '.$tmp);
            if (!@fclose($handle)) { $handle = null; throw new WorkflowException('Cannot close run file: '.$tmp); }
            $handle = null;
            if (!@rename($tmp, $path)) throw new WorkflowException('Cannot replace run file: '.$path.'; inspect before retrying.');
        } finally {
            if (is_resource($handle)) fclose($handle);
            if (is_file($tmp)) @unlink($tmp);
        }
    }

    /** Scan JSON already syntax-validated by json_decode, detecting escaped duplicate keys. */
    private static function scanJson(string $json, int &$i): void
    {
        $length = strlen($json);
        while ($i < $length && str_contains(" \t\r\n", $json[$i])) ++$i;
        $char = $json[$i];
        if ($char === '{' || $char === '[') {
            ++$i; $keys = [];
            while (true) {
                while ($i < $length && str_contains(" \t\r\n,", $json[$i])) ++$i;
                if ($json[$i] === ($char === '{' ? '}' : ']')) { ++$i; return; }
                if ($char === '{') {
                    $key = self::scanString($json, $i);
                    if (isset($keys[$key])) throw new WorkflowException('Duplicate JSON object key: '.$key);
                    $keys[$key] = true;
                    while (str_contains(" \t\r\n:", $json[$i])) ++$i;
                }
                self::scanJson($json, $i);
            }
        }
        if ($char === '"') { self::scanString($json, $i); return; }
        while ($i < $length && !str_contains(" \t\r\n,}]", $json[$i])) ++$i;
    }

    private static function scanString(string $json, int &$i): string
    {
        $start = $i++;
        while (true) {
            if ($json[$i] === '\\') { $i += 2; continue; }
            if ($json[$i++] === '"') break;
        }
        return json_decode(substr($json, $start, $i - $start), true, 512, JSON_THROW_ON_ERROR);
    }
}
