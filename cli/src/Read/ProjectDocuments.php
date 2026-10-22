<?php

declare(strict_types=1);

namespace Workflow\Read;

/**
 * Classification of the project-level documents (.workflow/PRD.md and
 * .workflow/Architecture.md). Purely advisory: template-state documents
 * produce warnings, never gates.
 */
final class ProjectDocuments
{
    /** Machine-detectable first-line marker of an unfilled scaffold copy. */
    public const MARKER = '<!-- workflow:unfilled-template -->';

    /** Templates installed before the marker existed are still recognizable. */
    private const LEGACY_PLACEHOLDER = 'Replace the placeholders';

    public function __construct(private readonly string $root)
    {
    }

    /** @return 'missing'|'template'|'filled' */
    public function state(string $document): string
    {
        $contents = @file_get_contents($this->root.'/.workflow/'.$document);
        if ($contents === false) {
            return 'missing';
        }

        $firstLine = trim((string) strtok($contents, "\n"));
        if ($firstLine === self::MARKER || str_contains($contents, self::LEGACY_PLACEHOLDER)) {
            return 'template';
        }

        return 'filled';
    }

    /**
     * Bounded PRD excerpt: only the Goals and Non-goals sections, with the
     * document state. Goals/non-goals stay empty when absent.
     *
     * @return array{state: string, goals: string, non_goals: string}
     */
    public function prdExcerpt(): array
    {
        $state = $this->state('PRD.md');
        if ($state === 'missing') {
            return ['state' => 'missing', 'goals' => '', 'non_goals' => ''];
        }

        $contents = (string) file_get_contents($this->root.'/.workflow/PRD.md');

        return [
            'state' => $state,
            'goals' => self::extractSection($contents, 'Goals'),
            'non_goals' => self::extractSection($contents, 'Non-goals'),
        ];
    }

    private static function extractSection(string $contents, string $heading): string
    {
        if (preg_match(
            '/^##[ \t]+'.preg_quote($heading, '/').'[ \t]*$\n(.*?)(?=^##[ \t]+|\z)/ms',
            $contents,
            $match,
        ) !== 1) {
            return '';
        }

        return trim($match[1]);
    }
}
