<?php

declare(strict_types=1);

namespace Workflow\Tests\Console;

use Workflow\Tests\WorkflowTestCase;

final class RunCommandTest extends WorkflowTestCase
{
    private function cli(array $args): array
    {
        $command = array_merge([PHP_BINARY, dirname(__DIR__, 2).'/bin/workflow', '--project', $this->root], $args);
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
        $error = stream_get_contents($pipes[2]); fclose($pipes[2]);
        return [proc_close($process), $output, $error];
    }

    public function testFreshProcessesAndRecovery(): void
    {
        mkdir($this->root.'/.workflow/items/E-001', 0777, true);
        file_put_contents($this->root.'/.workflow/items/E-001/item.md', "---\nid: E-001\nkind: epic\nparent: null\n---\n");
        $args = ['run', 'create', '--goal', '<test> goal', '--criterion', 'verifiable', '--action', 'decide', '--root', 'E-001', '--expires-at', '2099-01-01T00:00:00Z', '--max-roots', '1'];
        self::assertNotSame(0, $this->cli($args)[0]);
        [$exit, $out, $err] = $this->cli([...$args, '--authorize-autonomous']);
        self::assertSame(0, $exit, $err);
        $id = trim($out);
        self::assertMatchesRegularExpression('/\AR-[0-9a-f]{32}\z/', $id);
        [$exit, $out] = $this->cli(['run', 'show', $id, '--json']);
        self::assertSame(0, $exit);
        $data = json_decode($out, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('<test> goal', $data['goal']);
        self::assertTrue($data['authority_usable']);
        self::assertSame(0, $this->cli(['run', 'report', $id, '--status', 'interrupted', '--reason', 'unknown effect', '--recovery', 'inspect effect'])[0]);
        [, $out] = $this->cli(['run', 'show', $id, '--json']);
        self::assertFalse(json_decode($out, true)['authority_usable']);
        self::assertNotSame(0, $this->cli(['run', 'resume', $id])[0]);
        self::assertSame(0, $this->cli(['run', 'resume', $id, '--recovery', 'effect reconciled'])[0]);
        self::assertSame(0, $this->cli(['run', 'revoke', $id, '--reason', 'finished'])[0]);
        self::assertNotSame(0, $this->cli(['run', 'resume', $id, '--recovery', 'again'])[0]);
        [, $out] = $this->cli(['run', 'show', $id, '--json']);
        self::assertSame('revoked', json_decode($out, true)['effective_status']);
    }

    public function testProcessLockContentionUsesBarrier(): void
    {
        mkdir($this->root.'/.workflow/items/E-001', 0777, true);
        file_put_contents($this->root.'/.workflow/items/E-001/item.md', "---\nid: E-001\nkind: epic\nparent: null\n---\n");
        [$exit, $out] = $this->cli(['run', 'create', '--authorize-autonomous', '--goal', 'goal', '--criterion', 'criterion', '--action', 'decide', '--root', 'E-001', '--expires-at', '2099-01-01T00:00:00Z', '--max-roots', '1']);
        self::assertSame(0, $exit); $id = trim($out);
        $script = 'require $argv[1]; (new Workflow\\Store\\ItemStore($argv[2]))->withLock(function () { echo "locked\n"; flush(); fgets(STDIN); });';
        $process = proc_open([PHP_BINARY, '-r', $script, dirname(__DIR__, 2).'/vendor/autoload.php', $this->root], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertSame("locked\n", fgets($pipes[1]));
        try {
            [$exit, , $err] = $this->cli(['run', 'revoke', $id, '--reason', 'test']);
            self::assertNotSame(0, $exit);
            self::assertStringContainsString('retry', $err);
        } finally {
            fwrite($pipes[0], "release\n"); fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
            proc_close($process);
        }
        self::assertSame(0, $this->cli(['run', 'revoke', $id, '--reason', 'test'])[0]);
    }

    public function testCreateOptionsAndAllFieldsAcrossProcesses(): void
    {
        mkdir($this->root.'/.workflow/items/E-001', 0777, true);
        file_put_contents($this->root.'/.workflow/items/E-001/item.md', "---\nid: E-001\nkind: epic\nparent: null\n---\n");
        $options = ['--authorize-autonomous' => null, '--goal' => "<info>literal</info>\nline", '--criterion' => 'criterion', '--action' => 'decide', '--root' => 'E-001', '--expires-at' => '2099-01-01T00:00:00Z', '--max-roots' => '1'];
        $args = static function (array $opts): array { $result = ['run', 'create']; foreach ($opts as $key => $value) { $result[] = $key; if ($value !== null) $result[] = $value; } return $result; };
        foreach (array_keys($options) as $missing) {
            $bad = $options; unset($bad[$missing]);
            self::assertNotSame(0, $this->cli($args($bad))[0], 'accepted missing '.$missing);
        }
        foreach (['0', '01', '+1', '257', '999999999999999999999'] as $value) {
            $bad = $options; $bad['--max-roots'] = $value;
            self::assertNotSame(0, $this->cli($args($bad))[0], $value);
        }
        foreach (['--goal' => ' ', '--criterion' => ' ', '--action' => 'shell', '--root' => '../E-001', '--expires-at' => '2000-01-01T00:00:00Z'] as $key => $value) {
            $bad = $options; $bad[$key] = $value;
            self::assertNotSame(0, $this->cli($args($bad))[0], $key);
        }
        [$exit, $out, $err] = $this->cli($args($options)); self::assertSame(0, $exit, $err); $id = trim($out);
        [, $json] = $this->cli(['run', 'show', $id, '--json']);
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $stored = json_decode(file_get_contents($this->root.'/.workflow/runs/'.$id.'.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($stored + ['effective_status' => 'active', 'authority_usable' => true], $data);
        [, $text] = $this->cli(['run', 'show', $id]);
        foreach ($data as $key => $value) self::assertStringContainsString($key.': '.json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), $text);
        self::assertArrayNotHasKey('cost', $data['limits']);
        self::assertArrayNotHasKey('shell', $data['permissions']);
        self::assertNotSame(0, $this->cli(['run', 'show', $id, '--reason', 'not allowed'])[0]);
    }

    public function testAllReportsExpiredRecoveryAndIllegalTransitions(): void
    {
        mkdir($this->root.'/.workflow/items/E-001', 0777, true);
        file_put_contents($this->root.'/.workflow/items/E-001/item.md', "---\nid: E-001\nkind: epic\nparent: null\n---\n");
        [, $out] = $this->cli(['run', 'create', '--authorize-autonomous', '--goal', 'goal', '--criterion', 'criterion', '--action', 'decide', '--root', 'E-001', '--expires-at', '2099-01-01T00:00:00Z', '--max-roots', '1']); $id = trim($out);
        self::assertNotSame(0, $this->cli(['run', 'resume', $id, '--recovery', 'not blocked'])[0]);
        foreach (['blocked', 'interrupted', 'limit-exceeded'] as $status) {
            self::assertNotSame(0, $this->cli(['run', 'report', $id, '--status', $status, '--reason', 'reason'])[0]);
            self::assertSame(0, $this->cli(['run', 'report', $id, '--status', $status, '--reason', 'reason '.$status, '--recovery', 'inspect'])[0]);
            [, $out] = $this->cli(['run', 'show', $id, '--json']); $data = json_decode($out, true);
            self::assertSame($status, $data['effective_status']); self::assertFalse($data['authority_usable']);
            self::assertSame('reason '.$status, $data['status_reason']);
            self::assertStringNotContainsString('success', $data['effective_status']);
            self::assertSame(0, $this->cli(['run', 'resume', $id, '--recovery', 'reconciled'])[0]);
        }
        $path = $this->root.'/.workflow/runs/'.$id.'.json';
        $original = file_get_contents($path);
        file_put_contents($path, '{"status": "interrupted"');
        foreach ([['run', 'show', $id], ['run', 'resume', $id, '--recovery', 'reconciled'], ['run', 'report', $id, '--status', 'blocked', '--reason', 'x', '--recovery', 'x']] as $command) {
            [$exit, $output, $error] = $this->cli($command);
            self::assertNotSame(0, $exit); self::assertSame('', $output); self::assertStringContainsString($path, preg_replace('/\s+/', '', $error));
            self::assertSame('{"status": "interrupted"', file_get_contents($path));
        }
        file_put_contents($path, $original);
        $data = json_decode(file_get_contents($path), true);
        $data['created_at'] = '2000-01-01T00:00:00Z'; $data['updated_at'] = '2000-01-01T00:00:00Z'; $data['limits']['expires_at'] = '2001-01-01T00:00:00Z';
        file_put_contents($path, json_encode($data));
        self::assertSame(0, $this->cli(['run', 'report', $id, '--status', 'blocked', '--reason', 'expired', '--recovery', 'inspect'])[0]);
        [, $out] = $this->cli(['run', 'show', $id, '--json']); self::assertSame('expired', json_decode($out, true)['effective_status']);
        self::assertNotSame(0, $this->cli(['run', 'resume', $id, '--recovery', 'reconciled'])[0]);
        self::assertSame(0, $this->cli(['run', 'revoke', $id, '--reason', 'stop'])[0]);
        self::assertNotSame(0, $this->cli(['run', 'revoke', $id, '--reason', 'again'])[0]);
        self::assertNotSame(0, $this->cli(['run', 'report', $id, '--status', 'blocked', '--reason', 'x', '--recovery', 'x'])[0]);
        [, $out] = $this->cli(['run', 'show', $id, '--json']); self::assertSame('revoked', json_decode($out, true)['effective_status']);
    }

    public function testRevokeOrderingAcrossProcessesWithBarriers(): void
    {
        mkdir($this->root.'/.workflow/items/E-001', 0777, true);
        file_put_contents($this->root.'/.workflow/items/E-001/item.md', "---\nid: E-001\nkind: epic\nparent: null\n---\n");
        $create = ['run', 'create', '--authorize-autonomous', '--goal', 'goal', '--criterion', 'criterion', '--action', 'decide', '--root', 'E-001', '--expires-at', '2099-01-01T00:00:00Z', '--max-roots', '1'];
        [, $out] = $this->cli($create); $first = trim($out);
        $script = <<<'PHP'
require $argv[1];
echo "ready\n"; flush(); fgets(STDIN);
try {
    (new Workflow\Store\ItemStore($argv[2]))->withLock(function () use ($argv) {
        (new Workflow\Store\RunStore($argv[2]))->loadForUpdate($argv[3])->assertAuthorized('decide', ['E-001'], new DateTimeImmutable('now'));
        echo "authorized\n"; flush(); fgets(STDIN);
        file_put_contents($argv[2].'/simulated-commit', 'committed');
        echo "committed\n"; flush();
    });
} catch (Workflow\Domain\WorkflowException $e) { echo "denied\n"; }
PHP;
        $spawn = fn (string $id, &$pipes) => proc_open([PHP_BINARY, '-r', $script, dirname(__DIR__, 2).'/vendor/autoload.php', $this->root, $id], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $proc = $spawn($first, $pipes); self::assertSame("ready\n", fgets($pipes[1]));
        self::assertSame(0, $this->cli(['run', 'revoke', $first, '--reason', 'first'])[0]);
        fwrite($pipes[0], "check\n"); self::assertSame("denied\n", fgets($pipes[1]));
        fclose($pipes[0]); fclose($pipes[1]); $err = stream_get_contents($pipes[2]); fclose($pipes[2]); self::assertSame(0, proc_close($proc), $err);
        self::assertFileDoesNotExist($this->root.'/simulated-commit');

        [, $out] = $this->cli($create); $second = trim($out);
        $proc = $spawn($second, $pipes); self::assertSame("ready\n", fgets($pipes[1]));
        fwrite($pipes[0], "check\n"); self::assertSame("authorized\n", fgets($pipes[1]));
        [$exit, , $err] = $this->cli(['run', 'revoke', $second, '--reason', 'contended']);
        self::assertNotSame(0, $exit); self::assertStringContainsString('retry', $err);
        fwrite($pipes[0], "commit\n"); self::assertSame("committed\n", fgets($pipes[1]));
        fclose($pipes[0]); fclose($pipes[1]); $err = stream_get_contents($pipes[2]); fclose($pipes[2]); self::assertSame(0, proc_close($proc), $err);
        self::assertSame('committed', file_get_contents($this->root.'/simulated-commit'));
        self::assertSame(0, $this->cli(['run', 'revoke', $second, '--reason', 'after'])[0]);
        self::assertSame('committed', file_get_contents($this->root.'/simulated-commit'));
    }
}
