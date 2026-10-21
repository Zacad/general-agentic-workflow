<?php

declare(strict_types=1);

namespace Workflow\Tests\Domain;

use PHPUnit\Framework\TestCase;
use Workflow\Domain\FrontMatter;
use Workflow\Domain\InvalidItemException;

final class FrontMatterTest extends TestCase
{
    public function testRoundTripPreservesDataAndBody(): void
    {
        $data = [
            'id' => 'T-001',
            'kind' => 'task',
            'parent' => null,
            'title' => 'Parse: front matter',
            'depends_on' => [],
            'acceptance' => ['one', 'two'],
            'scope' => ['in' => ['a'], 'out' => []],
        ];
        $body = "## Plan\n\nDo the thing.\n\n## Progress\n";

        $rendered = FrontMatter::render($data, $body);
        $parsed = FrontMatter::parse($rendered);

        self::assertSame($data, $parsed['data']);
        self::assertSame($body, $parsed['body']);
    }

    public function testEmptyArraysRenderAsSequences(): void
    {
        $data = ['depends_on' => [], 'scope' => ['in' => [], 'out' => []]];
        $rendered = FrontMatter::render($data, '');

        self::assertStringContainsString('depends_on: []', $rendered);
        self::assertStringContainsString('in: []', $rendered);
        self::assertStringContainsString('out: []', $rendered);
        self::assertSame($data, FrontMatter::parse($rendered)['data']);
    }

    public function testEmptyDataKeepsClosingDelimiterOnItsOwnLine(): void
    {
        $rendered = FrontMatter::render([], 'body');

        self::assertSame("---\n[]\n---\n\nbody\n", $rendered);
        self::assertSame(['data' => [], 'body' => "body\n"], FrontMatter::parse($rendered));
    }

    public function testBraceShapedLiteralTextIsNotRewritten(): void
    {
        $data = [
            'title' => 'JSON: {}',
            'spaced' => 'JSON: { }',
            'literal' => '{}',
            'key JSON: {}' => 'value',
            'multiline' => "JSON: {}\nsecond: { }\n",
        ];

        $rendered = FrontMatter::render($data, '');

        self::assertStringContainsString('JSON: {}', $rendered);
        self::assertSame($data, FrontMatter::parse($rendered)['data']);
    }

    public function testFinalMultilineValuesRetainExactTerminalNewlines(): void
    {
        $values = [
            'none' => "first\nsecond",
            'one' => "first\nsecond\n",
            'many' => "first\nsecond\n\n\n",
            'one blank line' => "\n",
            'many blank lines' => "\n\n\n",
        ];

        foreach ($values as $ending => $value) {
            $variants = [
                'scalar' => ['custom' => $value],
                'nested mapping' => ['custom' => ['text' => $value]],
                'nested sequence' => ['custom' => [$value]],
            ];

            foreach ($variants as $shape => $data) {
                $message = $shape.' with '.$ending.' terminal newlines';
                $rendered = FrontMatter::render($data, 'body');
                $parsed = FrontMatter::parse($rendered);

                self::assertStringContainsString("\n---\n\nbody\n", $rendered, $message);
                self::assertSame($data, $parsed['data'], $message);
                self::assertSame("body\n", $parsed['body'], $message);
                self::assertSame($parsed, FrontMatter::parse(FrontMatter::render($parsed['data'], $parsed['body'])), $message);
            }
        }
    }

    public function testParsePreservesBlockScalarBoundaryNewline(): void
    {
        $blocks = [
            ["|-\n  first\n  second\n", "first\nsecond"],
            ["|\n  first\n  second\n", "first\nsecond\n"],
            ["|+\n  first\n  second\n\n\n", "first\nsecond\n\n\n"],
        ];

        foreach ($blocks as [$block, $value]) {
            foreach (["---\n\nbody\n", "---\n", '---'] as $closing) {
                $parsed = FrontMatter::parse("---\ncustom: ".$block.$closing);

                self::assertSame(['custom' => $value], $parsed['data']);
                self::assertSame(str_contains($closing, 'body') ? "body\n" : '', $parsed['body']);
            }
        }
    }

    public function testNestedUnknownValuesAndDelimiterTextRoundTrip(): void
    {
        $data = [
            'custom' => [
                'unicode' => 'Zażółć gęślą jaźń — 東京 🧪',
                'colon' => 'label: value',
                'delimiters' => ['---', '...', "first\n---\n...\nlast\n\n"],
                'empty' => [],
                'nested' => [
                    ['json' => 'JSON: {}', 'empty' => []],
                    [],
                    ['bool' => false, 'null' => null, 'int' => 7, 'float' => 2.5, 'string' => '001'],
                ],
            ],
        ];
        $body = "## Plan\n\n---\n\nBody delimiter stays in Markdown.\n";

        self::assertSame(['data' => $data, 'body' => $body], FrontMatter::parse(FrontMatter::render($data, $body)));
    }

    public function testRenderKeepsExistingBodyNormalization(): void
    {
        $body = "\r\n\r\n## Plan\r\n\r\nKeep internal spaces.  \r\nLast line. \r\n \t\r\n\r\n";
        $rendered = FrontMatter::render(['title' => 'Body'], $body);

        self::assertSame("---\ntitle: Body\n---\n\n## Plan\n\nKeep internal spaces.  \nLast line.\n", $rendered);
        self::assertSame("## Plan\n\nKeep internal spaces.  \nLast line.\n", FrontMatter::parse($rendered)['body']);
    }

    public function testParseKeepsExistingBodyNormalization(): void
    {
        $parsed = FrontMatter::parse("---\r\ntitle: Body\r\n---\r\n\r\n\r\nbody  \r\n\r\n");

        self::assertSame(['title' => 'Body'], $parsed['data']);
        self::assertSame("body  \n\n", $parsed['body']);
    }

    public function testUnknownKeysArePreserved(): void
    {
        $parsed = FrontMatter::parse("---\nid: T-001\ncustom: keep me\n---\nbody\n");

        self::assertSame('keep me', $parsed['data']['custom']);
        self::assertSame("body\n", $parsed['body']);
    }

    public function testMissingDelimiterIsRejected(): void
    {
        $this->expectException(InvalidItemException::class);
        FrontMatter::parse("id: T-001\nbody\n");
    }

    public function testInvalidYamlIsRejected(): void
    {
        $this->expectException(InvalidItemException::class);
        FrontMatter::parse("---\n: bad\n---\nbody\n");
    }

    public function testUnclosedYamlSequenceIsRejected(): void
    {
        $this->expectException(InvalidItemException::class);
        $this->expectExceptionMessage('Invalid YAML front matter:');
        FrontMatter::parse("---\ncustom: [one, two\n---\nbody\n");
    }
}
