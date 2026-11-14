<?php

declare(strict_types=1);

namespace Workflow\Tests\Store;

use Workflow\Domain\Run;
use Workflow\Domain\WorkflowException;
use Workflow\Store\ItemStore;
use Workflow\Store\RunStore;
use Workflow\Tests\WorkflowTestCase;

final class RunStoreTest extends WorkflowTestCase
{
    private function record(): array
    {
        return ['version' => 1, 'id' => 'R-'.str_repeat('a', 32), 'autonomous' => true,
            'goal' => 'Goal', 'success_criteria' => ['criterion'],
            'permissions' => ['actions' => ['decide'], 'roots' => ['E-001']],
            'limits' => ['expires_at' => '2099-01-01T00:00:00Z', 'max_roots' => 1],
            'status' => 'active', 'status_reason' => null, 'recovery' => null,
            'created_at' => '2020-01-01T00:00:00Z', 'updated_at' => '2020-01-01T00:00:00Z'];
    }

    private function rootItem(): void
    {
        mkdir($this->root.'/.workflow/items/E-001', 0777, true);
        file_put_contents($this->root.'/.workflow/items/E-001/item.md', "---\nid: E-001\nkind: epic\nparent: null\n---\n");
    }

    public function testPersistenceAuthorizationAndDuplicateJson(): void
    {
        $this->rootItem();
        $store = new RunStore($this->root);
        $run = Run::fromArray($this->record());
        $store->create($run);
        self::assertSame($run->toArray(), $store->load($this->record()['id'])->toArray());
        $store->loadForUpdate($this->record()['id'])->assertAuthorized('decide', ['S-001', 'T-001', 'E-001'], new \DateTimeImmutable('2026-01-01Z'));
        $path = $this->root.'/.workflow/runs/'.$this->record()['id'].'.json';
        $raw = file_get_contents($path);
        file_put_contents($path, preg_replace('/"version": 1/', '"version": 1, "\\u0076ersion": 1', $raw, 1));
        $this->expectException(WorkflowException::class);
        $store->load($this->record()['id']);
    }

    public function testExpiryAtEqualityAndMalformedAuthority(): void
    {
        $run = Run::fromArray($this->record());
        self::assertSame('expired', $run->effectiveStatus(new \DateTimeImmutable('2099-01-01T00:00:00Z')));
        $this->expectException(WorkflowException::class);
        $run->assertAuthorized('decide', ['E-001'], new \DateTimeImmutable('2099-01-01T00:00:00Z'));
    }

    public function testLockContentionAndCollision(): void
    {
        $this->rootItem();
        $store = new RunStore($this->root);
        $run = Run::fromArray($this->record());
        (new ItemStore($this->root))->withLock(function () use ($store, $run): void {
            try { $store->create($run); self::fail('Expected contention'); }
            catch (WorkflowException $e) { self::assertStringContainsString('retry', $e->getMessage()); }
        });
        $store->create($run);
        $this->expectException(WorkflowException::class);
        $store->create($run);
    }

    public function testMissingRootIsRejected(): void
    {
        mkdir($this->root.'/.workflow', 0777, true);
        $this->expectException(WorkflowException::class);
        (new RunStore($this->root))->create(Run::fromArray($this->record()));
    }

    public function testInvalidFieldsAndAuthorityAreDenied(): void
    {
        $record = $this->record();
        foreach ([
            ['autonomous', false], ['goal', '  '], ['success_criteria', []], ['id', '../bad'],
            ['version', 2], ['status', 'success'], ['status_reason', 'unexpected'],
            ['updated_at', '2019-01-01T00:00:00Z'],
        ] as [$field, $value]) {
            $bad = $record; $bad[$field] = $value;
            try { Run::fromArray($bad); self::fail('Accepted invalid '.$field); }
            catch (WorkflowException) { self::assertTrue(true); }
        }
        $bad = $record; $bad['permissions']['actions'] = ['decide', 'decide'];
        try { Run::fromArray($bad); self::fail('Accepted duplicate actions'); } catch (WorkflowException) { self::assertTrue(true); }
        $bad = $record; $bad['limits']['expires_at'] = '2026-02-30T00:00:00Z';
        try { Run::fromArray($bad); self::fail('Accepted invalid date'); } catch (WorkflowException) { self::assertTrue(true); }
        $run = Run::fromArray($record);
        foreach ([['decide', ['T-999']], ['scope-approve', ['E-001']], ['decide', ['E-001', 'E-001']], ['decide', []]] as [$action, $ancestry]) {
            try { $run->assertAuthorized($action, $ancestry, new \DateTimeImmutable('2026-01-01Z')); self::fail('Accepted invalid authority'); }
            catch (WorkflowException) { self::assertTrue(true); }
        }
    }

    public function testRevocationReloadAndImmutableAuthority(): void
    {
        $this->rootItem();
        $store = new RunStore($this->root);
        $run = Run::fromArray($this->record());
        $id = $run->toArray()['id'];
        $store->create($run);
        try {
            $store->mutate($id, function (Run $r): Run { $data = $r->toArray(); $data['goal'] = 'different'; $data['status'] = 'revoked'; $data['status_reason'] = 'reason'; return Run::fromArray($data); });
            self::fail('Changed immutable authority');
        } catch (WorkflowException) { self::assertSame('active', $store->load($id)->toArray()['status']); }
        $store->mutate($id, function (Run $r): Run { $data = $r->toArray(); $data['status'] = 'revoked'; $data['status_reason'] = 'reason'; return Run::fromArray($data); });
        (new ItemStore($this->root))->withLock(function () use ($store, $id): void {
            try { $store->loadForUpdate($id)->assertAuthorized('decide', ['E-001'], new \DateTimeImmutable('2026-01-01Z')); self::fail('Revoked run authorized'); }
            catch (WorkflowException) { self::assertTrue(true); }
        });
    }

    public function testMalformedAndOversizedFilesFailClosed(): void
    {
        $this->rootItem();
        $store = new RunStore($this->root);
        $record = $this->record();
        $store->create(Run::fromArray($record));
        $path = $this->root.'/.workflow/runs/'.$record['id'].'.json';
        foreach (['{"version":', str_repeat('x', 1048577), json_encode($record + ['extra' => 1])] as $invalid) {
            file_put_contents($path, $invalid);
            try { $store->load($record['id']); self::fail('Loaded invalid run'); }
            catch (WorkflowException $e) { self::assertStringContainsString($path, $e->getMessage()); }
        }
        self::assertFileExists($path);
    }

    public function testSchemaBoundsAndEveryRequiredField(): void
    {
        $valid = $this->record();
        foreach (array_keys($valid) as $key) {
            $bad = $valid; unset($bad[$key]);
            try { Run::fromArray($bad); self::fail('Missing '.$key); } catch (WorkflowException) { self::assertTrue(true); }
        }
        foreach (['permissions' => ['actions', 'roots'], 'limits' => ['expires_at', 'max_roots']] as $object => $fields) {
            foreach ($fields as $field) {
                $bad = $valid; unset($bad[$object][$field]);
                try { Run::fromArray($bad); self::fail('Missing '.$object.'.'.$field); } catch (WorkflowException) { self::assertTrue(true); }
            }
            $bad = $valid; $bad[$object]['extra'] = true;
            try { Run::fromArray($bad); self::fail('Unknown nested key'); } catch (WorkflowException) { self::assertTrue(true); }
        }
        $cases = [];
        foreach ([null, '', ' ', str_repeat('x', 16385), "\xff"] as $goal) { $r = $valid; $r['goal'] = $goal; $cases[] = $r; }
        foreach ([[], [''], [null], array_fill(0, 257, 'x')] as $criteria) { $r = $valid; $r['success_criteria'] = $criteria; $cases[] = $r; }
        foreach ([[], ['unknown'], ['decide', 'decide']] as $actions) { $r = $valid; $r['permissions']['actions'] = $actions; $cases[] = $r; }
        foreach ([[], ['../E-001'], ['E-001', 'E-001'], ['E-001', 'T-001']] as $roots) { $r = $valid; $r['permissions']['roots'] = $roots; $cases[] = $r; }
        foreach ([0, 257, '1', 1.0, null] as $max) { $r = $valid; $r['limits']['max_roots'] = $max; $cases[] = $r; }
        foreach (['2026-02-30T00:00:00Z', '2099-01-01T00:00:00+00:00', '2020-01-01T00:00:00Z', null] as $expiry) { $r = $valid; $r['limits']['expires_at'] = $expiry; $cases[] = $r; }
        foreach ($cases as $index => $bad) {
            try { Run::fromArray($bad); self::fail('Accepted invalid case '.$index); } catch (WorkflowException) { self::assertTrue(true); }
        }
        $valid['goal'] = str_repeat('x', 16384);
        $valid['success_criteria'] = array_map(static fn ($i) => 'criterion '.$i, range(1, 256));
        $valid['permissions']['actions'] = ['scope-approve', 'design-approve', 'decide', 'accept-review'];
        $valid['permissions']['roots'] = array_map(static fn ($i) => 'E-'.$i, range(1, 256));
        $valid['limits']['max_roots'] = 256;
        self::assertSame($valid, Run::fromArray($valid)->toArray());
    }

    public function testRootAncestryValidationAndAmbiguity(): void
    {
        $this->rootItem();
        $items = $this->root.'/.workflow/items';
        mkdir($items.'/T-001');
        file_put_contents($items.'/T-001/item.md', "---\nid: T-001\nkind: task\nparent: E-001\n---\n");
        mkdir($items.'/S-001');
        file_put_contents($items.'/S-001/item.md', "---\nid: S-001\nkind: subtask\nparent: T-001\n---\n");
        mkdir($items.'/T-002');
        file_put_contents($items.'/T-002/item.md', "---\nid: T-002\nkind: task\nparent: null\n---\n");
        $store = new RunStore($this->root);
        foreach (['S-001', 'T-002'] as $i => $root) {
            $record = $this->record(); $record['permissions']['roots'] = [$root]; $record['id'] = 'R-'.str_repeat((string) ($i + 1), 32);
            $store->create(Run::fromArray($record));
            self::assertSame([$root], $store->load($record['id'])->toArray()['permissions']['roots']);
        }
        $record = $this->record(); $record['permissions']['roots'] = ['S-001'];
        foreach ([
            "---\nid: T-001\nkind: task\nparent: T-001\n---\n",
            "---\nid: T-999\nkind: task\nparent: E-001\n---\n",
            "---\nid: T-001\nkind: task\nparent: E-999\n---\n",
            "---\nid: T-001\nkind: subtask\nparent: E-001\n---\n",
            "---\nid: [broken\n---\n",
        ] as $raw) {
            file_put_contents($items.'/T-001/item.md', $raw);
            try { $store->create(Run::fromArray($record)); self::fail('Accepted malformed ancestry'); } catch (\Throwable $e) { self::assertNotInstanceOf(\PHPUnit\Framework\AssertionFailedError::class, $e); }
        }
        file_put_contents($items.'/T-001/item.md', "---\nid: T-001\nkind: task\nparent: E-001\n---\n");
        mkdir($items.'/E-001/T-001', 0777, true);
        copy($items.'/T-001/item.md', $items.'/E-001/T-001/item.md');
        try { $store->create(Run::fromArray($record)); self::fail('Accepted ambiguous ancestry'); } catch (WorkflowException) { self::assertTrue(true); }
    }

    public function testMalformedJsonAndClockFailClosed(): void
    {
        $this->rootItem(); $store = new RunStore($this->root); $record = $this->record();
        $store->create(Run::fromArray($record));
        $path = $this->root.'/.workflow/runs/'.$record['id'].'.json';
        $json = file_get_contents($path);
        $objectList = $record; $objectList['permissions']['actions'] = (object) ['0' => 'decide'];
        foreach ([$json.'x', str_replace('"goal": "Goal"', '"goal": null', $json), str_replace('"actions": [', '"extra": 1, "actions": [', $json), str_replace('"actions": [', '"actions": ["decide"], "\\u0061ctions": [', $json), json_encode($objectList), str_replace('"goal": "Goal"', '"goal": "'."\xff".'"', $json), str_replace($record['id'], 'R-'.str_repeat('b', 32), $json)] as $bad) {
            file_put_contents($path, $bad);
            try { $store->load($record['id']); self::fail('Loaded malformed record'); } catch (WorkflowException $e) { self::assertStringContainsString($path, $e->getMessage()); }
        }
        $future = $record; $future['updated_at'] = '2098-01-01T00:00:00Z';
        file_put_contents($path, json_encode($future));
        try {
            $store->mutate($record['id'], static function (Run $run): Run { $data = $run->toArray(); $data['status'] = 'revoked'; $data['status_reason'] = 'reason'; return Run::fromArray($data); });
            self::fail('Accepted backward clock');
        } catch (WorkflowException $e) { self::assertStringContainsString('clock', $e->getMessage()); }
        self::assertSame($future, json_decode(file_get_contents($path), true));
    }

    public function testWriteFaultsPreserveRecordAndOrphanIsIgnored(): void
    {
        $this->rootItem(); $store = new RunStore($this->root); $record = $this->record();
        $store->create(Run::fromArray($record));
        $dir = $this->root.'/.workflow/runs'; $path = $dir.'/'.$record['id'].'.json';
        $original = file_get_contents($path);
        $orphan = $dir.'/.'.$record['id'].'.json.orphan.tmp';
        file_put_contents($orphan, '{"incomplete":');
        $script = <<<'PHP'
namespace Workflow\Store {
    function fwrite($handle, $data) {
        static $partial = false;
        if ($GLOBALS['fault'] === 'write') {
            if (!$partial) { $partial = true; return \fwrite($handle, substr($data, 0, 5)); }
            return false;
        }
        return \fwrite($handle, $data);
    }
    function fflush($handle) { return $GLOBALS['fault'] === 'flush' ? false : \fflush($handle); }
    function fsync($handle) { return $GLOBALS['fault'] === 'fsync' ? false : \fsync($handle); }
    function rename($from, $to) { return $GLOBALS['fault'] === 'rename' ? false : \rename($from, $to); }
}
namespace {
    require $argv[1]; $GLOBALS['fault'] = $argv[4];
    try {
        (new Workflow\Store\RunStore($argv[2]))->mutate($argv[3], function ($run) {
            $d = $run->toArray(); $d['status'] = 'interrupted'; $d['status_reason'] = 'unknown'; $d['recovery'] = 'inspect';
            return Workflow\Domain\Run::fromArray($d);
        });
        exit(2);
    } catch (Workflow\Domain\WorkflowException $e) { echo $e->getMessage(); }
}
PHP;
        foreach (['write', 'flush', 'fsync', 'rename'] as $fault) {
            $proc = proc_open([PHP_BINARY, '-r', $script, dirname(__DIR__, 2).'/vendor/autoload.php', $this->root, $record['id'], $fault], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            fclose($pipes[0]); $out = stream_get_contents($pipes[1]); fclose($pipes[1]); $err = stream_get_contents($pipes[2]); fclose($pipes[2]);
            self::assertSame(0, proc_close($proc), $fault.': '.$out.' '.$err);
            self::assertStringContainsString('Cannot ', $out);
            self::assertSame($original, file_get_contents($path), $fault);
            self::assertSame($record, $store->load($record['id'])->toArray());
            self::assertSame([$orphan], glob($dir.'/*.tmp', GLOB_NOSORT) ?: glob($dir.'/.*.tmp', GLOB_NOSORT));
        }
        self::assertSame('{"incomplete":', file_get_contents($orphan));
    }

    public function testRecordSizeBoundaryAndAncestryPredicateLimits(): void
    {
        $this->rootItem(); $store = new RunStore($this->root); $record = $this->record();
        $store->create(Run::fromArray($record));
        $path = $this->root.'/.workflow/runs/'.$record['id'].'.json';
        $raw = file_get_contents($path);
        file_put_contents($path, $raw.str_repeat(' ', 1048576 - strlen($raw)));
        self::assertSame($record, $store->load($record['id'])->toArray());
        file_put_contents($path, $raw.str_repeat(' ', 1048577 - strlen($raw)));
        try { $store->load($record['id']); self::fail('Oversized record accepted'); } catch (WorkflowException) { self::assertTrue(true); }
        $huge = $record;
        $huge['id'] = 'R-'.str_repeat('b', 32);
        $huge['success_criteria'] = array_map(static fn ($i) => str_pad((string) $i, 16384, 'x'), range(1, 65));
        try { $store->create(Run::fromArray($huge)); self::fail('Oversized generated record accepted'); } catch (WorkflowException) { self::assertFileDoesNotExist($this->root.'/.workflow/runs/'.$huge['id'].'.json'); }

        $run = Run::fromArray($record);
        foreach ([['E-001'], ['T-100', 'E-001'], ['S-100', 'T-100', 'E-001']] as $ancestry) {
            $run->assertAuthorized('decide', $ancestry, new \DateTimeImmutable('2098-12-31T23:59:59Z'));
            self::assertTrue(true);
        }
        foreach ([['E-002'], ['T-002'], ['S-002', 'T-002', 'E-002'], ['../E-001'], ['S-001', 'S-001', 'E-001']] as $ancestry) {
            try { $run->assertAuthorized('decide', $ancestry, new \DateTimeImmutable('2026-01-01Z')); self::fail('Unauthorized ancestry'); } catch (WorkflowException) { self::assertTrue(true); }
        }
        // Structural authenticity is the locked caller's obligation, not this predicate's.
        $run->assertAuthorized('decide', ['S-999', 'T-999', 'E-001'], new \DateTimeImmutable('2026-01-01Z'));
        self::assertTrue(true);
    }
}
