<?php

declare(strict_types=1);

namespace Workflow\Tests\Store;

use Workflow\Domain\Item;
use Workflow\Store\ItemStore;
use Workflow\Tests\WorkflowTestCase;

final class ItemAtomicWriteTest extends WorkflowTestCase
{
    public function testWriteFailuresLeaveExistingItemAndUnrelatedTemporaryFileUntouched(): void
    {
        $store = new ItemStore($this->root);
        $store->create(Item::fromArray(['id' => 'E-001', 'kind' => 'epic', 'title' => 'Original'], ''));
        $path = $store->itemPath('E-001');
        $original = file_get_contents($path);
        $orphan = dirname($path).'/.item.md.orphan.tmp';
        file_put_contents($orphan, 'unrelated');
        $script = <<<'PHP'
namespace Workflow\Store {
    function fwrite($h, $data) {
        static $partial = false;
        if ($GLOBALS['fault'] === 'write') {
            if (!$partial) { $partial = true; return \fwrite($h, substr($data, 0, 5)); }
            return false;
        }
        return \fwrite($h, $data);
    }
    function fflush($h) { return $GLOBALS['fault'] === 'flush' ? false : \fflush($h); }
    function fsync($h) { return $GLOBALS['fault'] === 'fsync' ? false : \fsync($h); }
    function fclose($h) { $result = \fclose($h); return $GLOBALS['fault'] === 'close' ? false : $result; }
    function rename($from, $to) { return $GLOBALS['fault'] === 'rename' ? false : \rename($from, $to); }
}
namespace {
    require $argv[1]; $GLOBALS['fault'] = $argv[3];
    try {
        (new Workflow\Store\ItemStore($argv[2]))->mutate('E-001', function ($item) { $item->title = 'Changed'; });
        exit(2);
    } catch (Workflow\Domain\WorkflowException $e) { echo $e->getMessage(); }
}
PHP;
        foreach (['write', 'flush', 'fsync', 'close', 'rename'] as $fault) {
            $process = proc_open([PHP_BINARY, '-r', $script, dirname(__DIR__, 2).'/vendor/autoload.php', $this->root, $fault], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            fclose($pipes[0]);
            $out = stream_get_contents($pipes[1]); fclose($pipes[1]);
            $err = stream_get_contents($pipes[2]); fclose($pipes[2]);
            self::assertSame(0, proc_close($process), $fault.': '.$out.' '.$err);
            self::assertStringContainsString('Cannot ', $out);
            self::assertSame($original, file_get_contents($path), $fault);
            self::assertSame('unrelated', file_get_contents($orphan));
            self::assertSame([$orphan], glob(dirname($path).'/.item.md.*.tmp'));
        }
    }
}
