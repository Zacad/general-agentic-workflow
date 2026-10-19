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
        $rendered = FrontMatter::render(['depends_on' => [], 'scope' => ['in' => [], 'out' => []]], '');

        self::assertStringContainsString('depends_on: []', $rendered);
        self::assertStringContainsString('in: []', $rendered);
        self::assertStringContainsString('out: []', $rendered);
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
}
