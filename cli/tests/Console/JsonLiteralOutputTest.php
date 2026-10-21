<?php

declare(strict_types=1);

namespace Workflow\Tests\Console;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Workflow\Console\Application;
use Workflow\Console\Command\BaseCommand;
use Workflow\Domain\FrontMatter;
use Workflow\Domain\Item;
use Workflow\Tests\WorkflowTestCase;

final class JsonLiteralOutputTest extends WorkflowTestCase
{
    private const LITERAL = '<info>pociągi</info> <error>🚆 błędy</error> <comment>źródła</comment> '
        .'<fg=green;options=bold>Żółć</> <href=https://example.test/a/b>link</>';

    public static function decorationModes(): iterable
    {
        yield 'undecorated' => [false];
        yield 'decorated' => [true];
    }

    #[DataProvider('decorationModes')]
    public function testSharedJsonWriterPreservesLiteralNestedValues(bool $decorated): void
    {
        $command = new class extends BaseCommand {
            public function __construct()
            {
                parent::__construct('literal-test');
            }

            public function emit(OutputInterface $output, mixed $data): void
            {
                $this->writeJson($output, $data);
            }
        };
        $data = [
            'literal' => self::LITERAL,
            'nested' => ['lines' => [self::LITERAL."\n\"quote\" \\path", '', null, []]],
            self::LITERAL => 'Unicode: 你好, zażółć, 🚆',
        ];
        $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, $decorated);
        $command->emit($output, $data);
        $json = $output->fetch();

        self::assertSame($data, json_decode($json, true, flags: JSON_THROW_ON_ERROR));
        self::assertStringContainsString(self::LITERAL, $json);
        self::assertStringNotContainsString("\033[", $json);
    }

    public static function rolesAndDecorationModes(): iterable
    {
        foreach (['orchestrator', 'explorer', 'worker', 'verifier'] as $role) {
            yield $role.' undecorated' => [$role, false];
            yield $role.' decorated' => [$role, true];
        }
    }

    #[DataProvider('rolesAndDecorationModes')]
    public function testBriefJsonPreservesExactLiteralValuesAcrossThePacket(string $role, bool $decorated): void
    {
        $this->put(['id' => 'E-001', 'kind' => 'epic', 'title' => 'Parent'], "## Design\n\n".self::LITERAL);
        $this->put([
            'id' => 'T-001', 'kind' => 'task', 'parent' => 'E-001',
            'title' => 'Dependency', 'artifacts' => [self::LITERAL],
        ]);
        $data = [
            'id' => 'T-002', 'kind' => 'task', 'parent' => 'E-001',
            'title' => self::LITERAL, 'outcome' => self::LITERAL."\nSecond line.",
            'depends_on' => ['T-001'], 'owns' => [self::LITERAL], 'artifacts' => [self::LITERAL],
            'scope' => ['in' => [self::LITERAL, 'a, b'], 'out' => ['a', 'b', self::LITERAL]],
            'acceptance' => [self::LITERAL],
            'last_result' => ['role' => 'worker', 'status' => 'done', 'finding' => '<info>Żółć 🚆</info>'],
        ];
        $body = "## Plan\n\n".self::LITERAL."\n\n## Design\n\n".self::LITERAL
            ."\n\n## Decisions\n\n".self::LITERAL;
        $data['approval'] = [
            'by' => self::LITERAL, 'at' => '2026-10-21T00:00:00+00:00',
            'scope_hash' => Item::fromArray($data, $body)->scopeHash(),
        ];
        $this->put($data, $body);

        $application = new Application();
        $application->setAutoExit(false);
        $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, $decorated);
        $exit = $application->run(new ArrayInput([
            '--project' => $this->root, 'command' => 'brief', 'id' => 'T-002',
            '--for' => $role, '--json' => true,
        ]), $output);
        $json = $output->fetch();
        self::assertSame(0, $exit, $json);
        self::assertStringNotContainsString("\033[", $json);
        $packet = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        foreach (['title', 'outcome', 'scope', 'acceptance', 'owns', 'artifacts', 'depends_on'] as $field) {
            self::assertSame($data[$field], $packet['item'][$field], $field);
        }
        foreach (['plan', 'design', 'parent_design', 'decisions'] as $field) {
            self::assertSame(self::LITERAL, $packet[$field], $field);
        }
        self::assertSame('current ('.self::LITERAL.', 2026-10-21T00:00:00+00:00)', $packet['approval']);
        self::assertSame(['T-001' => [self::LITERAL]], $packet['upstream_artifacts']);
        self::assertSame([self::LITERAL], $packet['deliverable_paths']);
        if ($role !== 'verifier') {
            self::assertSame($data['last_result'], $packet['item']['last_result']);
        } else {
            self::assertArrayNotHasKey('last_result', $packet['item']);
        }
    }

    private function put(array $data, string $body = ''): void
    {
        $directory = $this->root.'/.workflow/items/'.$data['id'];
        mkdir($directory, 0777, true);
        file_put_contents($directory.'/item.md', FrontMatter::render($data, $body));
    }
}
