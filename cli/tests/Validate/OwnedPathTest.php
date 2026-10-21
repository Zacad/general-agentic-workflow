<?php

declare(strict_types=1);

namespace Workflow\Tests\Validate;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workflow\Console\Application;
use Workflow\Domain\Approval;
use Workflow\Domain\FrontMatter;
use Workflow\Domain\Item;
use Workflow\Read\ProjectView;
use Workflow\Store\ItemStore;
use Workflow\Tests\WorkflowTestCase;
use Workflow\Validate\ItemValidator;

final class OwnedPathTest extends WorkflowTestCase
{
    private const AT = '2026-10-21T00:00:00+00:00';

    public static function paths(): iterable
    {
        yield 'equal' => ['src/a', 'src/a', true];
        yield 'ancestor' => ['src/a', 'src/a/future.php', true];
        yield 'trailing separator' => ['src/a/', 'src/a', true];
        yield 'leading separator alias' => ['/src/a/', 'src/a', true];
        yield 'dot prefix' => ['./src/a', 'src/a', true];
        yield 'repeated dot prefix' => ['././src/a', 'src/a/future.php', true];
        yield 'repeated separators' => ['src///a//file.php', 'src/a/file.php', true];
        yield 'backslashes' => ['src\\a\\file.php', 'src/a/file.php', true];
        yield 'mixed aliases' => ['.\\src//a/./', './src/a/file.php', true];
        yield 'middle dot' => ['src/./a/./file.php', 'src/a/file.php', true];
        yield 'in-root parent' => ['src/a/../b/file.php', 'src/b', true];
        yield 'multiple in-root parents' => ['src/a/deep/../../b', 'src/b/file.php', true];
        yield 'parent then child' => ['src/a/../a', 'src/a/file.php', true];
        yield 'dot root' => ['.', 'src/future.php', true];
        yield 'slash root' => ['/', 'src/future.php', true];
        yield 'backslash root' => ['\\', 'src/future.php', true];
        yield 'repeated slash root' => ['////', '.', true];
        yield 'dot slash root' => ['./', 'src/future.php', true];
        yield 'normalized root' => ['src/..', 'future/path.php', true];
        yield 'deep normalized root' => ['src/a/../..', '.', true];
        yield 'root aliases' => ['a/.././', '././', true];
        yield 'empty pair' => ['', '', false];
        yield 'empty is not root' => ['', '.', false];
        yield 'empty does not own descendants' => ['', 'src/a', false];
        yield 'empty is not slash root' => ['', '/', false];
        yield 'component boundary' => ['src/a', 'src/ab', false];
        yield 'component hyphen boundary' => ['src/a', 'src/a-b/file.php', false];
        yield 'extension boundary' => ['src/a.php', 'src/a.php.bak', false];
        yield 'normalized boundary' => ['./src/x/../a', 'src/ab/file.php', false];
        yield 'siblings' => ['src/a', 'src/b', false];
        yield 'case is literal' => ['src/A', 'src/a', false];
        yield 'spaces in component' => ['new dir/a', './new dir/a/file.php', true];
        yield 'unicode is literal' => ['źródła/a', './źródła/a/file.php', true];
        yield 'dot in filename' => ['src/a..', 'src/a../file.php', true];
        yield 'three dots are literal' => ['...', '...', true];
        yield 'three dots are not root' => ['...', 'src', false];
        yield 'glob is not expanded' => ['src/*', 'src/a', false];
        yield 'recursive glob is not expanded' => ['src/**/*.php', 'src/deep/a.php', false];
        yield 'question mark is not expanded' => ['src/?.php', 'src/a.php', false];
        yield 'brackets are not expanded' => ['src/[ab]', 'src/a', false];
        yield 'braces are not expanded' => ['src/{a,b}', 'src/a', false];
        yield 'literal star directory' => ['src/*', 'src/*/file.php', true];
        yield 'tilde is not expanded' => ['~/src', 'src', false];
        yield 'environment is not expanded' => ['$HOME/src', 'src', false];
        yield 'escaping parent is not clamped' => ['../src', 'src', false];
        yield 'escaping after cancellation is not clamped' => ['a/../../src', 'src', false];
        yield 'root excludes escaping paths' => ['.', '../outside', false];
        yield 'root excludes parent' => ['/', '..', false];
        yield 'unresolved parents retain lexical identity' => ['../outside', '../outside/file.php', true];
        yield 'dotdot prefix is a normal name' => ['.', '..cache/file.php', true];
    }

    #[DataProvider('paths')]
    public function testLexicalEqualityAndComponentAncestryAreSymmetric(string $left, string $right, bool $overlap): void
    {
        self::assertSame($overlap, ItemValidator::pathsOverlap($left, $right));
        self::assertSame($overlap, ItemValidator::pathsOverlap($right, $left));
    }

    private function put(string $id, string $status, array $owns, array $depends = []): void
    {
        $data = [
            'id' => $id, 'kind' => 'task', 'status' => $status, 'title' => 'Item '.$id,
            'owns' => $owns, 'depends_on' => $depends, 'standalone_reason' => 'Independent fixture.',
        ];
        $data['approval'] = (new Approval(self::AT, 'user', Item::fromArray($data, '')->scopeHash()))->toArray();
        if ($status === 'done') {
            $data['last_result'] = ['at' => self::AT, 'role' => 'verifier', 'status' => 'done', 'finding' => 'Checked.'];
            $data['verified_at'] = self::AT;
        }
        $directory = $this->root.'/.workflow/items/'.$id;
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        file_put_contents($directory.'/item.md', FrontMatter::render($data, ''));
    }

    private function json(array $input): array
    {
        $application = new Application();
        $application->setAutoExit(false);
        $output = new BufferedOutput();
        $exit = $application->run(new ArrayInput(['--project' => $this->root] + $input + ['--json' => true]), $output);
        $text = $output->fetch();
        self::assertSame(0, $exit, $text);

        return json_decode($text, true, flags: JSON_THROW_ON_ERROR);
    }

    private function bytes(): array
    {
        $bytes = [];
        foreach (glob($this->root.'/.workflow/items/*/item.md') ?: [] as $path) {
            $bytes[$path] = file_get_contents($path);
        }

        return $bytes;
    }

    #[DataProvider('paths')]
    public function testReadyAndValidateShareLiteralComparisonWithoutRewritingPaths(string $left, string $right, bool $overlap): void
    {
        foreach (['in-progress', 'todo'] as $otherStatus) {
            $this->put('T-001', 'todo', [$left]);
            $this->put('T-002', $otherStatus, [$right]);
            $before = $this->bytes();
            $ready = $this->json(['command' => 'ready', '--all' => true]);
            $validation = $this->json(['command' => 'validate']);
            self::assertSame($otherStatus === 'todo' ? 2 : 1, count($ready), 'Ownership is a warning, not a readiness gate.');
            self::assertSame('T-001', $ready[0]['id']);
            self::assertSame($overlap ? ['T-002'] : [], $ready[0]['overlaps']);
            self::assertSame($left === '' ? [] : [$left], $ready[0]['owns']);
            if ($otherStatus === 'todo') {
                self::assertSame($overlap ? ['T-001'] : [], $ready[1]['overlaps']);
            }
            $warnings = implode("\n", $validation['warnings']);
            self::assertSame($overlap, str_contains($warnings, 'parallel overlap'));
            self::assertSame([], $validation['errors']);
            self::assertSame($before, $this->bytes());
        }
    }

    public function testComparisonDoesNotTrimLiteralComponentWhitespace(): void
    {
        self::assertFalse(ItemValidator::pathsOverlap(' src/a', 'src/a'));
        self::assertFalse(ItemValidator::pathsOverlap('src/a ', 'src/a'));
        self::assertTrue(ItemValidator::pathsOverlap('src/a ', 'src/a /file.php'));
    }

    public function testDependencyOrderedOpenWorkIsNotReportedAsParallelInEitherProjection(): void
    {
        foreach ([['T-001'], ['T-002']] as $dependencies) {
            $this->put('T-001', 'todo', ['./future//target/../shared']);
            $this->put('T-002', 'draft', [], ['T-001']);
            $this->put('T-003', 'in-progress', ['future/shared/file.php'], $dependencies);
            $before = $this->bytes();
            $ready = $this->json(['command' => 'ready']);
            $validation = $this->json(['command' => 'validate']);
            self::assertSame(['T-001'], array_column($ready, 'id'));
            self::assertSame([], $ready[0]['overlaps']);
            self::assertStringNotContainsString('parallel overlap', implode("\n", $validation['warnings']));
            self::assertSame($before, $this->bytes());
        }
    }

    public function testDependencyWaitingAndTerminalOwnershipBehaviorIsPreserved(): void
    {
        $this->put('T-001', 'todo', ['.']);
        $this->put('T-002', 'todo', ['future/file.php'], ['T-001']);
        $this->put('T-003', 'cancelled', ['.']);
        $ready = $this->json(['command' => 'ready']);
        self::assertSame(['T-001'], array_column($ready, 'id'));
        self::assertSame([], $ready[0]['overlaps']);
        $view = new ProjectView(new ItemStore($this->root));
        $items = $view->items();
        self::assertSame('waits on T-001 (todo)', $view->blockReason($items['T-002'], $items));
        self::assertStringNotContainsString('parallel overlap', implode("\n", $this->json(['command' => 'validate'])['warnings']));

        $this->put('T-001', 'done', ['.']);
        $ready = $this->json(['command' => 'ready']);
        self::assertSame(['T-002'], array_column($ready, 'id'));
        self::assertSame([], $ready[0]['overlaps']);
        self::assertStringNotContainsString('parallel overlap', implode("\n", $this->json(['command' => 'validate'])['warnings']));
    }

    public function testUnrelatedLegacyDependencyCycleDoesNotLoopOrHideParallelOwnership(): void
    {
        $left = Item::fromArray(['id' => 'T-001', 'kind' => 'task'], '');
        $right = Item::fromArray(['id' => 'T-002', 'kind' => 'task', 'depends_on' => ['T-003']], '');
        $other = Item::fromArray(['id' => 'T-003', 'kind' => 'task', 'depends_on' => ['T-002']], '');
        self::assertFalse(ItemValidator::dependencyOrdered($left, $right, [
            $left->id => $left, $right->id => $right, $other->id => $other,
        ]));
    }

    public function testExistingSymlinkAliasesRemainLexicallyDifferent(): void
    {
        mkdir($this->root.'/actual');
        file_put_contents($this->root.'/actual/file.php', 'Target');
        self::assertTrue(symlink($this->root.'/actual', $this->root.'/alias'));
        $cwd = getcwd();
        try {
            self::assertTrue(chdir($this->root));
            self::assertFalse(ItemValidator::pathsOverlap('actual/file.php', 'alias/file.php'));
            self::assertTrue(ItemValidator::pathsOverlap('./alias/../actual/file.php', 'actual/file.php'));
        } finally {
            chdir($cwd);
            unlink($this->root.'/alias');
        }
    }
}
